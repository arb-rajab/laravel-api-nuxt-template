# Dependabot status

_Last updated: 2026-10-10. Starter template extracted from `bookslot`; update when the state changes._

## Configuration

- Ecosystems covered: composer (`/`), npm (`/frontend`), docker (`/frontend`), docker-compose (`/`), github-actions (`/`).
- Grouping: minor and patch updates grouped as `minor-and-patch` for composer, npm and github-actions.
- Schedule: weekly.
- Ignore rules: `typescript` and `@types/node` majors in `/frontend`; docker-compose image majors.

## State at last update

- Open Dependabot PRs: 0 (each merged or closed only after reading its checks).
- Default-branch CI: green at last check.
- Repository settings (read live 2026-10-10): public, `is_template` true, description set, default branch `main` (the only branch kept), secret scanning, push protection and Dependabot security updates enabled, dependency graph on (SBOM endpoint 200). Non-provider secret patterns and validity checks stay off: GitHub does not offer them on this user-owned public repo.
- Set by the repo owner through the API on 2026-10-10 (the session's proxy blocks repository-settings writes) and read back by the session the same day: private vulnerability reporting enabled (PUT 204), `delete_branch_on_merge` on (PATCH 200), repository topics set (PUT 200). Fork-PR workflow approval is `all_external_contributors` (read with the owner's token). Stale branches left over from squash-merged PRs were deleted after a fresh diff against `main` showed their content already on `main`.
- Alerts read 2026-10-10 with the repo owner's token: Dependabot #1 `node-forge` (GHSA-86w9-cpqp-85rv, high, no patched release), #2 `braces` (GHSA-vfj7-8cjw-p6xm, high, no patched release), #3 and #4 `simple-git` (high, fix 4.0.0), #5 `simple-git` (critical, fix 4.0.1) and #6 `@simple-git/argv-parser` (critical, fix 2.0.1), all in `frontend/package-lock.json`. Each matches an `osv-scanner.toml` exemption. node-forge and braces are reached only through build tooling and have no fix. simple-git and argv-parser are reached through `nuxt` and `@nuxt/test-utils` -> `@nuxt/devtools`, used only by the dev server; their fix needs simple-git 4, which only the 4.0.0-beta.4 `@nuxt/devtools` prerelease supports. Dismissed as `tolerable_risk` with a comment by the repo owner on 2026-10-10 (PATCH 200); re-check before 2026-11-09. No code-scanning or secret-scanning alerts. The read-back after the dismissals showed 0 open Dependabot and 0 open code-scanning alerts.

## Time-limited exemptions

- `osv-scanner.toml`, every entry expiring 2026-11-09 (earlier than the source repos' 2026-11-15): `braces` GHSA-vfj7-8cjw-p6xm; `node-forge` GHSA-86w9-cpqp-85rv; `simple-git` GHSA-858h-whjf-mvg5, GHSA-g4wm-2vf7-vfgr and GHSA-x6jw-m9v5-85vh, and `@simple-git/argv-parser` GHSA-v5rq-49vh-5v5c (via `nuxt` and `@nuxt/test-utils` -> `@nuxt/devtools`; the fix needs a stable `@nuxt/devtools` that supports simple-git 4, and the only such line is 4.0.0-beta.4, a prerelease that is not adopted). After that date the scan fails until each entry is renewed or removed. The scan is not a required check, so merges still work, but re-check each advisory for a fix before then.

## Notes

- Merge policy (deliberate choice by the repo owner, the same blanket policy as the application repos): every PR, major-version dependency bumps included, is squash-merged as soon as all of its required checks are green, confirmed per PR. This repo is a code showcase with no business or sensitive dependency, so green checks are the only gate. Red, pending or conflicted PRs are fixed or closed instead.
- `main` protection (2026-10-10, read back): pull request required (0 approvals), enforced for admins, force pushes and deletions blocked, "require branches to be up to date" off (`strict` false) on purpose. Nobody pushes to `main` directly; status-log updates go through a PR too.
- Required contexts: `Backend (lint, static analysis, tests)`, `Frontend (lint, typecheck, unit tests, build)`, `End-to-end (real browser, real API)`, `Secret scanning (gitleaks)`. Nothing else is required on purpose: osv-scanner, npm audit, dependency review and CodeQL depend on a moving advisory database or run only on some events.
- Closed unmerged on 2026-10-10, each with an explanatory comment: #1 (`node` 22 -> 25 in `/frontend`, non-LTS). If Dependabot recreates one, apply the merge policy; the closure is not re-raised. Dependabot does not re-propose a major after its PR is closed, so a later upgrade is a manual change.
- Dependabot security-update jobs for `@simple-git/argv-parser` fail on `main` with `security_update_not_possible` (2026-10-10): `simple-git` 3.36.0 pins `^1.1.0` and the fix is 2.0.1. These runs show as `Dependabot` check runs on `main` and are not CI failures; they stop once the alert is dismissed or the upstream fix lands.

## Deferred (not re-raised each pass)

- Alert reads (Dependabot, code scanning, secret scanning) and Actions settings (fork-PR approval) are blocked for Claude sessions by the proxy; they are read and written with the repo owner's token on their own machine.
- The owner keeps the token's Administration, Contents and Secret scanning permissions and does not rotate it (deliberate, 2026-10-09, reconfirmed 2026-10-10 for the template repos).
- Re-check the exemptions above before 2026-11-09 and drop each once its upstream fix ships.
