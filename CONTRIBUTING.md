# Contributing

## Workflow

- **Branching:** trunk-based. Branch from `main` as `feat/<slug>`, `fix/<slug>`,
  `chore/<slug>` or `sec/<slug>`.
- **Commits:** [Conventional Commits](https://www.conventionalcommits.org/)
  (`feat:`, `fix:`, `docs:`, `test:`, `ci:`, `refactor:`, `chore:`).
- **Pull requests:** pass CI (lint, static analysis, tests, security scans) and
  use the PR template. Open an issue before a large change.

## Development setup

See [`README.md`](README.md#getting-started) for the full local setup (Docker
Compose services, the two-role database credential split, the Nuxt frontend).

Before opening a PR, `composer ci:check` must exit 0. It runs Pint (style),
Larastan/PHPStan (static analysis) and the fast test suite, which includes the
tenant-isolation suite. For frontend changes, `npm run lint`, `npm run
typecheck` and `npm run test:unit` (in `frontend/`) must pass too.

Run `composer lint:fix` once at the end of a batch of edits instead of
formatting by hand.

## Reporting security issues

See [`SECURITY.md`](SECURITY.md). Do not use public issues for vulnerabilities.

## Code of conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md).
