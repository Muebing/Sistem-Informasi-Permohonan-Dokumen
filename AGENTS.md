# AGENTS.md

Laravel 13 REST API + Vue 3 + TypeScript SPA ("SIPERDO" — repo dir is `SIPDK`). PostgreSQL, Sanctum (Bearer tokens), Spatie Permission (roles/permissions), PHPUnit 12, PHPStan level 7, Tailwind CSS v4. Fork of the Laravel starter kit; **the backend API is complete and the SPA is being built out client-side** — this is no longer an Inertia/Fortify app.

## Architecture — verify before assuming

- Backend is a REST API in `routes/api.php` (`/api/v1`, Sanctum). `routes/web.php` is only `Route::get('/{any}', fn () => view('app'))` — the SPA shell. There are **no server-rendered pages and no named web routes**; do not add routes to `web.php`.
- All Inertia/Fortify/wayfinder starter-kit code is fully removed (no `@inertiajs/vue3`, no `laravel/wayfinder`). Don't restore it; leftover ignores in `.gitignore`/`eslint.config.js` are harmless.
- Client routing lives in `resources/js/router/index.ts`: hash history (URLs look like `/#/dashboard`), route `meta` fields `requiresAuth`/`guest`/`roles` drive guards; layouts (`AuthLayout`/`AppLayout`) are chosen there, not in `app.ts`.
- Auth is token-based: `resources/js/lib/api.ts` (axios, baseURL `/api/v1`) injects `Authorization: Bearer <token>` from localStorage key `siperdo_token`; a 401 clears it and redirects to `#/login`.
- Roles: `applicant`, `reviewer`. Middleware aliases `permission:`/`role:` are registered in `bootstrap/app.php`; API routes gate on permissions (e.g. `permission:projects.submit`).
- Server emails (password reset, review outcome) build links against `FRONTEND_URL` (`config('app.frontend_url')`), e.g. `/#/reset-password?token=...&email=...`.

## Commands

- `composer dev` — `php artisan serve` + `queue:listen --tries=1` + Vite together. Use this for local dev; the queue worker is required (document-hash job and review emails are `ShouldQueue`).
- `composer setup` — deps, copy `.env`, key:generate, `migrate --force`, npm install, build. **Does NOT seed** — run `php artisan migrate --seed` for roles + demo data.
- `composer test` — `config:clear` + Pint `--test` + PHPStan + `php artisan test`. Requires a local PostgreSQL test DB (see below).
- `composer ci:check` — npm `lint:check` + `format:check` + `types:check` + `composer test`. CI (`.github/workflows/tests.yml`) provisions Postgres 16, PHP 8.3, Node 22.
- Single test: `php artisan test --filter <TestName>` (PHPUnit, not Pest).
- PHP: `composer lint` (Pint fix), `composer lint:check`, `composer types:check` (PHPStan level 7).
- JS: `npm run lint:check`, `npm run format:check`, `npm run types:check` (vue-tsc).

## Database / tests

- **PostgreSQL only — no SQLite.** Local DB is `siperdo`; tests use `siperdo_test` with credentials hardcoded in `phpunit.xml` (`postgres`/`postgres` on `127.0.0.1`). Create `siperdo_test` before running tests locally.
- On this machine `php artisan test` works against the local PostgreSQL 17 service (user `postgres`, password `postgres`) with a pre-created `siperdo_test` database. All static checks (Pint, PHPStan, lint/format/types) and the full PHPUnit suite (75 tests) pass.
- `tests/TestCase.php` seeds `RolePermissionSeeder` and offers `applicant()`/`reviewer()` helpers using `Sanctum::actingAs`; throttling is disabled in tests.

## Frontend conventions

- Path alias `@/*` → `resources/js/*`.
- UI kit: shadcn-vue/reka-ui in `resources/js/components/ui/*`. Prefer composing these; `cn()` from `@/lib/utils`, icons from `@lucide/vue`.
- Tailwind 4: no `tailwind.config.js`; tokens live in `resources/css/app.css`. API status colors (from `app/Support/StatusPresenter.php`) are safelisted there with `@source inline(...)` — add new status colors there or they get purged.
- ESLint (auto-fix via `npm run lint`): `import/order` (alphabetical), type-only imports (`consistent-type-imports`), blank lines around control statements, 1TBS braces + `curly`.
- Prettier: 4-space indent (2 for yml), single quotes, semicolons; runs only on `resources/`.
- Pages live in `resources/js/pages/` (PascalCase files); API payload types in `resources/js/types/`. Toasts via `toast()` (vue-sonner) + `getErrorMessage()` from `@/lib/api`.

## Backend conventions

- Form requests under `app/Http/Requests`, one per action; controllers delegate to `app/Services/*` (auth, project, review, dashboard, export); shared query logic in `app/Repositories/ProjectRepository.php`.
- API responses use Resources (`app/Http/Resources`) with Laravel's default pagination envelope; statuses are formatted for the frontend as `{ value, label, color }` via `StatusPresenter`.
- Exports: maatwebsite/excel (XLSX) + barryvdh/laravel-dompdf (PDF). Needs PHP extensions `zip`, `gd`/`imagick`, `mbstring`.

## Misc

- Queue, cache, and sessions default to the `database` driver.
- Demo accounts from seed: demo applicants & reviewers use `*@example.org` / `password` (e.g. `psaragih@example.org`).
- Detailed API/domain docs (endpoints, ERD, approval workflow) are in `README.md`.
