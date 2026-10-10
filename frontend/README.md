# starter-app frontend

The Nuxt 4 app for the Laravel API in the repository root. It calls the real
API, never a mock.

## Setup

```bash
npm ci
```

The API origin defaults to `http://localhost:8000` (the backend's `php artisan
serve` port). Copy `.env.example` to `.env` only if the API runs elsewhere.
Use `localhost`, not `127.0.0.1`, for both servers: cookies are scoped by
hostname.

## Development

Requires the API running against a migrated, seeded database (see the root
README).

```bash
npm run dev -- --host=localhost
```

Pages: `/` (public), `/login`, `/dashboard` (protected by the `auth` route
middleware).

## Scripts

```bash
npm run lint         # ESLint (@nuxt/eslint)
npm run typecheck    # vue-tsc
npm run test:unit    # Vitest, real Nuxt auto-import context, real local HTTP server as the API
npm run test:e2e     # Playwright: real Chromium + real Laravel API (starts both servers)
npm run build
```

`test:e2e` needs the backend migrated and seeded
(`php artisan migrate:fresh --seed --database=pgsql_migrator`) right before the
run: re-running against data a previous run mutated can fail in ways that look
like regressions. If the environment's Chromium does not match the revision
Playwright expects, set `PLAYWRIGHT_CHROMIUM_PATH` instead of running
`playwright install`.

## Layout

```
app/composables/   useApi (CSRF + typed errors), useFormErrors, useSession
app/middleware/    auth (route middleware)
app/components/    AppButton, AppAlert, FormField
app/layouts/       default
tests/unit/        Vitest (support/mockApi.ts starts a local HTTP server as the API)
tests/e2e/         Playwright specs
```

Set `NUXT_PUBLIC_TENANCY=false` if you removed the backend's tenancy module.
