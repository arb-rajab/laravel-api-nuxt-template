# CLAUDE.md

Repo-specific notes for coding sessions on this Laravel API + Nuxt template.
Concrete lessons only; update this file when a session learns something that
would have saved a round trip.

## Layout in one breath

Laravel 13 API in the repo root, Nuxt 4 app in `frontend/`. Optional modules
live in `app/Tenancy/` (multi-tenancy + RLS) and `app/Queue/RabbitMq/`, each
registered by one provider line in `bootstrap/providers.php`. Tests for each
module live in their own folder (`tests/Tenancy`, `tests/RabbitMq`).

## Fresh-container setup (nothing persists between sessions)

- PHP must be 8.4+ (Pest 5 requires it). `php -v` first.
- Postgres roles and databases do not exist yet. Run the bootstrap SQL as the
  `postgres` OS user: `sudo -u postgres psql -v ON_ERROR_STOP=1 -f
  docker/postgres/init/01-roles-and-database.sql`. Never use `psql -h
  127.0.0.1 -U postgres`: with no password configured it hangs on a prompt
  forever instead of failing.
- `cp .env.example .env && php artisan key:generate` (the example ships an
  empty `APP_KEY`; a missing key only shows up as `MissingAppKeyException` on
  the first real HTTP request, not in migrations or Pest). Then `php artisan
  migrate --database=pgsql_migrator`.
- `frontend/node_modules` is absent too: run `npm ci` in `frontend/` before
  `npx vue-tsc`, or `npx` fetches an unpinned copy that fails on a TypeScript
  mismatch.
- RabbitMQ (only for `composer test:queue-broker`): `apt-get install -y
  rabbitmq-server`, then `service rabbitmq-server start` (the package does not
  start it), then `rabbitmqctl add_user starter_app
  starter_app_rabbitmq_local_only`, `set_permissions -p / starter_app ".*"
  ".*" ".*"`, `set_user_tags starter_app administrator`.
- Table grants: `ALTER DEFAULT PRIVILEGES` in the bootstrap SQL covers tables
  the migrator creates later. If you see `SQLSTATE[42501] permission denied
  for table X` against a database created some other way, it is a missing
  grant, not an RLS bug.

## Composer in a sandbox

- Anonymous GitHub API zipball downloads get rate-limited: use `composer
  install --prefer-source`. Run `-vvv` and read the last `[403]` URL, not the
  last "Syncing ..." line, to see which package really failed.
- `phpstan/phpstan` has no `source` in its Packagist metadata, so
  `--prefer-source` cannot help it. Either skip `larastan/larastan` locally
  (remove it from `require-dev`, run your tests, then `git checkout --
  composer.json composer.lock`; CI installs it fine), or download the pinned
  phar from the phpstan GitHub releases and point a temporary `path`
  repository at it. Never commit that workaround.
- As root, `composer <script>` aborts ("no plugin should be loaded if running
  as super user"). Call `./vendor/bin/pest`, `./vendor/bin/pint` directly, or
  set `COMPOSER_ALLOW_SUPERUSER=1`.

## Test suite shape

- `composer test:fast` is `pest --exclude-group=queue-broker`: no Redis or
  RabbitMQ needed (`.env.testing` uses `sync` queue and `array` cache and
  session). Cheap enough to run whole after any backend change.
- While iterating on one endpoint run just that file: `./vendor/bin/pest
  tests/Feature/AuthTest.php`. Tests are independent.
- Never reach for SQLite. RLS, uuid defaults and the role split are
  Postgres-only.
- Laravel skips CSRF verification during any Pest run. To prove CSRF is
  enforced call `disableConsoleCsrfBypass()` (tests/Support/AuthTestHelpers.php)
  and use `loginAndCaptureXsrf()` for the real csrf-cookie -> login flow.
- Run `./vendor/bin/pint` once at the end of a batch of edits rather than
  formatting file by file.

## Gotchas that only show up in a real browser or a real process

- `config/cors.php` `allowed_headers` must stay an explicit list. With
  `supports_credentials = true`, browsers treat `'*'` literally and silently
  block the preflight; no non-browser test can catch it.
- Use `http://localhost:PORT` for both frontend and API everywhere
  (`playwright.config.ts`, `.env`, `nuxt.config.ts`). `127.0.0.1` is a
  different cookie hostname and breaks Sanctum's cookie flow in a way that
  looks like a CSRF bug. Start manual dev servers with `--host=localhost`.
- `frontend/app/app.vue` must wrap `<NuxtPage />` in `<NuxtLayout>`, or a page
  using a named layout renders blank with no error from `vue-tsc` or the build.
- SSR pages hydrate after load: a Playwright click before hydration submits a
  form natively. Navigate with `waitUntil: 'networkidle'` on pages with forms
  (see `tests/e2e/auth.spec.ts`).
- Playwright specs are ES modules: `__dirname` is undefined and throws at
  import time (the whole file's tests vanish). Use
  `path.dirname(fileURLToPath(import.meta.url))`.
- Re-seed (`php artisan migrate:fresh --seed --database=pgsql_migrator`)
  right before the one Playwright run you intend to trust. Specs that create
  data and locate rows with `.first()` bind to stale rows on a second run.
- The Playwright `webServer` runs `php artisan serve`, which reads `.env`, not
  `.env.testing`. Keep `.env` pointed at the dev database.
- If the environment's Chromium does not match Playwright's expected revision,
  set `PLAYWRIGHT_CHROMIUM_PATH` rather than running `playwright install`.
- Vitest: never wait with a fixed `setTimeout` after a network-backed action.
  Under worker contention it flakes. Poll with `flushUntil()`
  (`tests/unit/support/waitFor.ts`).
- After a mutating action that reloads its own state, a mock that returns the
  same fixture for the reload cannot catch bugs that only appear once the state
  changes. Make the mock reflect the mutation.

## Tenancy module rules (read before touching `app/Tenancy`)

- `auth.tenant` wraps the **whole request in one database transaction**. A
  route whose controller calls an external service (payments, third-party
  APIs) must use `auth.tenant.external` and open short `TenantContext::run()`
  calls itself; never hold a transaction across a network call.
- Tenant users cannot use plain `auth` middleware: `users` has an RLS policy,
  so with no GUC the session re-hydration query finds nothing. The tenant id
  lives in the session and `auth.tenant` sets context before the user lookup.
  Do not "simplify" it into separate middleware: Laravel's middleware priority
  list reorders `Authenticate` ahead of custom middleware regardless of route
  order. `tests/Tenancy/SequentialProcessReauthTest.php` guards this with
  separate OS processes, because in-process tests share a connection whose GUC
  was set by fixture setup and so can never reproduce the bug.
- Route-model binding runs before the tenant context exists on some routes; use
  `{id}` and `findOrFail()` inside the controller.
- Every new tenant-scoped table needs: a `tenant_id` column, an entry in
  `config/tenancy.php`, an RLS policy migration, the `BelongsToTenant` trait on
  its model, and a row in `tests/Tenancy/Support/TenantFixture.php`. The
  manifest tests fail loudly if any is missing.
- `NULLIF(current_setting(...), '')` in the policies is deliberate: after a
  connection's first tenant transaction the GUC is an empty string, not NULL.
- Tests that need "no tenant context" must call `TenantContext::clear()`;
  merely not calling `run()` is not enough inside Pest's outer transaction.

## RabbitMQ module notes

- Without a broker, run the rest with `composer test:fast`. The broker suite
  uses its own random queue names and a real `queue:work --once` subprocess.
- Delayed delivery is TTL + dead-letter exchange; a short delay published after
  a long one will not jump ahead of it (head-of-queue expiry). Retry attempts
  ride on the `x-starter-app-attempt` header, not `x-death`.
- `TenantScopedJob::$tenantId` is intentionally not `readonly`
  (`SerializesModels` restores it from the subclass scope).

## Static analysis

Larastan level 8 over `app/` and `database/`. Tests are not analysed (level 8
on Pest closures is noise). Fix findings; do not add baseline entries or
`@phpstan-ignore` to silence them.
