# Agency Module — Pending Follow-ups

**Status:** pending · **Branch base:** `feature/agency-full-domain` (backend), `feature/connect-panel-to-backend` (panel)

This file captures everything still pending after the panel↔Laravel connection. The connection
plan (Fases 0–3) is **done**; this is the quality/polish backlog. Self-contained so the next
session can execute without re-exploring.

## Done (recap — do NOT redo)

- Backend: tenant-scoped `Agency` module — `agency_*` tables (UUID PK, jsonb embedded arrays),
  models `App\Models\Agency\*` (8 entities + `AgencySetting` singleton), generic
  `AgencyCrudController`/`AgencyFormRequest`/`AgencyResource`, routes `/api/agency/*`
  (`auth:sanctum` + `api.tenant`, no Pro gate), `AgencyDemoSeeder`. PHPStan level 5 = 0.
- Panel: `lib/api.ts` (Bearer + `X-Tenant-ID`, 401→login, safe parse), `lib/auth-context.tsx`,
  `/login`, full-store hydration (allSettled + `hydrated` loading gate), every entity persists to
  the API. `tsc --noEmit` = 0.
- Docs: `docs/api/agency-reference.md` + Scribe (`/docs`, `/docs.openapi`, `/docs.postman`).
- Two code-review rounds + fixes applied (findScoped tenant-safety, null-array coalesce, refresh
  on create, resilient hydration, settings load gate, dead-code removal).

## Pending — prioritized

### 1. Tests for the agency module ✅ DONE (2026-06-29)

Added `tests/Feature/Api/Agency/` — **23 tests, 143 assertions, all green**
(`DB_PORT=5433 php artisan test tests/Feature/Api/Agency`):
- `AgencyTestCase` (shared auth/tenant setup), `ProjectApiTest` (full CRUD,
  full-row-on-create, camelCase↔snake mapping + embedded arrays, validation,
  tenant isolation + header-spoof→403), `ClientApiTest` (Spanish field mapping +
  isolation), `SettingsApiTest` (singleton GET/PUT merge + isolation),
  `AgencyResourcesSmokeTest` (data-provider CRUD+422 over the other 6 resources).
- Note: tenant-isolation tests must create the "victim" row via the model (not a
  prior HTTP request) and make all HTTP calls as one identity — switching tokens
  mid-test hits the auth guard's per-request user cache (gave a false 200).

Original notes (kept for reference):
The repo has 348 tests; the agency module had none. Patterns copied from
`tests/Feature/Api/V1/*` and `tests/Feature/Api/AuthApiTest.php`.

Per-entity (Project, Service, Supplier, TeamMember, Quote, Invoice, Expense) + Client + Settings,
cover:
- **CRUD happy path:** POST creates (201, response is the **full row** incl. `[]` for unset array
  columns, UUID `id`, ISO `createdAt/updatedAt`); GET list/show; PUT partial update; DELETE 204.
- **Validation (422):** missing required field (`name`/`number`/`description`), bad enum/type.
- **Tenant isolation (the critical one):** a resource created for tenant A returns **404** when
  requested with tenant B's token/`X-Tenant-ID` (verifies `findScoped()` + global scope). Also
  assert `index` only returns the current tenant's rows.
- **Auth:** 401 without a token; 403 with a valid token but a `X-Tenant-ID` the user doesn't belong to.
- **Field mapping:** camelCase request (`clientId`, `teamMembers`, `basePrice`) persists to the
  snake_case column and the response echoes camelCase; embedded jsonb arrays (`items`, `tasks`)
  round-trip with inner camelCase keys intact.
- **Settings singleton:** GET returns `{}` when unset; PUT merges (doesn't replace) and is
  idempotent; only one row per tenant.

How to authenticate + set tenant in tests: create a User + Tenant + membership, then either
`Sanctum::actingAs($user)` and pass `X-Tenant-ID` header, OR mint a token via
`$user->createToken(...)`. Mirror exactly how `tests/Feature/Api/V1/*` set up the tenant — read one
first. Run: `composer test -- --filter=Agency` (PostgreSQL on 5433 via phpunit.xml).

### 2. Merge / open PRs (workflow) ✅ DONE (2026-06-30)

- Backend **PR #3** `feature/agency-full-domain` → `develop` — open, CI green (Lint/PHPStan/Tests).
- Panel **PR #1** `feature/connect-panel-to-backend` → `main` — open, build green, MERGEABLE/CLEAN.
- Superseded branches **deleted** from remote+local (fully contained, no PR):
  backend `feature/agency-clients-api`, panel `fix/api-client-hardening`.

Remaining: review + merge the two open PRs (left to the maintainer).

### 3. Optional polish (nice-to-have, not blocking)

- **`/docs` auth:** the Scribe docs route is public. For non-local, add middleware in
  `config/scribe.php` (`laravel.middleware`).
- **Module flag:** add `'agency' => env('MODULE_AGENCY', true)` to `config/modules.php` and gate
  the agency route group with `hasModule('agency')` (consistency with other modules).
- **Pagination:** agency `index` returns ALL rows. Fine now; add pagination if datasets grow.
- **Filament admin UI:** there are no Filament resources for the agency entities (backend admin
  can't view them; only the panel does). Add if the agency data should be visible in `/admin`.
- **Panel auth UX:** the panel picks `tenants[0]` (no multi-tenant selector) and has no
  refresh-token handling (a 401 sends the user to `/login`). Fine for a single-admin tool.
- **Panel cleanup:** add eslint + flat config so `pnpm lint` works (currently a stub).

## Key file map (no need to re-explore)

- Backend: `app/Http/Controllers/Api/Agency/*`, `app/Http/Requests/Api/Agency/*`,
  `app/Http/Resources/Agency/{AgencyResource,ClientResource}.php`, `app/Models/Agency/*`,
  `database/migrations/2026_06_28_22000*_create_agency_*`, `database/seeders/AgencyDemoSeeder.php`,
  routes in `routes/api.php` (search `prefix('agency')`), `config/scribe.php`.
- Test patterns to copy: `tests/Feature/Api/V1/`, `tests/Feature/Api/AuthApiTest.php`.
- Panel: `lib/api.ts`, `lib/auth-context.tsx`, `lib/context.tsx`, `components/layout/main-layout.tsx`.

## Verification (when picking this up)

```bash
# backend
DB_PORT=5433 php artisan migrate          # ensure agency_* tables exist
php artisan db:seed --class=AgencyDemoSeeder
composer test -- --filter=Agency          # the new tests
composer analyse                          # PHPStan level 5
# panel
cd ../panel-administrativo-nexa-digital && npx tsc --noEmit
```
