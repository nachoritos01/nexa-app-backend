# Current Development Context

**Last Updated:** 2026-07-04
**Branch:** feature/retire-public-v1-api (API surface pruning — waves 1+2 done, pending PR → develop). Also open: feature/sanctum-token-expiration (tokens expire after 7 days, committed, pending PR).
**Latest Release:** v1.2.0
**Tests:** 366 tests, 994 assertions — ALL PASSING (0 failures, 0 skips)
**PHPStan:** Level 5, 0 errors (baseline: removed stale V1/PaymentController entry)

## API Surface Pruning (2026-07-04) — branch `feature/retire-public-v1-api`

Executed `docs/auditoria-y-plan-api-agency.md` (Parte B, corrected 2026-07-04):
- **Retired:** public surface (`/api` root, `/api/health`, public quotes, content), the whole
  `/api/v1/*` group, `POST /auth/push-token`, `EnsureApiAccess`/`api.pro`.
- **Kept (critical):** `/api/auth/login|me|logout` — the panel's login — moved OUT of the
  `hasModule('api')` gate so `MODULE_API=false` can never switch it off. `AuthController` +
  `LoginRequest` stay. Models, `App\Services\PdfGenerator`, Filament, portal, DB untouched
  (zero migrations).
- Tests: V1/health/push-token tests deleted; token/isolation coverage repointed from
  `/api/v1/orders` to `/api/agency/clients` (`ApiTokenTest`, `TenantIsolationTest`).
- Health checks: native `/up` only (Railway `railway.json` already pointed there).
- Verified live: `/up` 200, login 422-validates, agency 401 w/o token, Filament 200,
  portal `/my-account` 302, retired routes 404.

## Project State

| Track | Status | Detail |
|-------|--------|--------|
| **Generic SaaS Template Conversion** | **COMPLETE** | All 22 phases done |
| **Plugin Marketplace** | **COMPLETE** | Merged to develop |
| **Billing History** | **COMPLETE** | Merged to develop (PR #5) |
| **Loyalty Program** | **COMPLETE** | Merged to develop (PR #6, v1.1.0) |
| **Audit Remediation** | **COMPLETE** | 14/14 findings resolved (PR #7 + PR #8, v1.1.1 + v1.2.0) |
| **SaaS Infrastructure** | Retained | Multi-tenant, billing, onboarding, referrals, RBAC |
| **Impersonate Session Fix** | **COMPLETE** | Fix #45 — already on `develop` (initial commit); `bugfix/fix-impersonate-session` branch never existed. Verified green in PR #3 CI (SuperAdminTest) |
| **Agency API Module** | **NEW (2026-06-28)** | Tenant-scoped `/api/agency/*` for the admin panel (:3001); see note below |

## Audit Findings (from /audit all — 2026-03-16)

All 14 issues resolved and documented in `docs/features/31-44` (all `.done.md`).

### HIGH Priority (PR #7, v1.1.1)

| # | Doc | Area | Status |
|---|-----|------|--------|
| 31 | `31-fix-subscription-fk-constraints.done.md` | Database | **Done** |
| 32 | `32-fix-revenue-chart-n-queries.done.md` | Performance | **Done** |
| 33 | `33-fix-tenant-resource-n-plus-1.done.md` | Performance | **Done** |
| 34 | `34-fix-tenant-growth-chart-queries.done.md` | Performance | **Done** |
| 35 | `35-add-loyalty-service-tests.done.md` | Tests | **Done** |
| 36 | `36-add-customer-portal-tests.done.md` | Tests | **Done** |

### MEDIUM Priority (PR #8, v1.2.0)

| # | Doc | Area | Status |
|---|-----|------|--------|
| 37 | `37-fix-customer-auth-tenant-scope.done.md` | Security | **Done** |
| 38 | `38-fix-personal-access-token-scope.done.md` | Security | **Done** |
| 39 | `39-fix-trial-commands-eager-loading.done.md` | Performance | **Done** |
| 40 | `40-cache-dashboard-widgets.done.md` | Performance | **Done** |
| 41 | `41-add-customer-auth-tests.done.md` | Tests | **Done** |
| 42 | `42-add-webhook-handler-tests.done.md` | Tests | **Done** |
| 43 | `43-extract-billing-service.done.md` | Code | **Done** |
| 44 | `44-extract-health-score-config.done.md` | Code | **Done** |

### Bugfix (current session)

| # | Doc | Area | Status |
|---|-----|------|--------|
| 45 | `45-fix-impersonate-session.done.md` | Bugfix | **Done** |

## Remaining Work

| Item | Priority | Notes |
|------|----------|-------|
| PHPStan baseline | INFO | 88 Larastan false positives (dynamic Eloquent properties, pivot attrs) — not real bugs; regenerated 2026-06-30 to cover `App\Models\Agency\*` |

## Active Branches

| Branch | Purpose | Status |
|--------|---------|--------|
| `develop` | Integration | Stable — all PRs merged, v1.2.0. Fix #45 (impersonate) already landed here in the initial commit |
| `feature/agency-full-domain` | Full agency module (8 entities + settings) + seeder + API docs | **PR #3 open** → `develop`, CI green (Lint/PHPStan/Tests) |

> Superseded branches deleted 2026-06-30 (fully contained in active branches, no PR):
> backend `feature/agency-clients-api` (⊆ `feature/agency-full-domain`),
> panel `fix/api-client-hardening` (⊆ `feature/connect-panel-to-backend`).

> ~~`bugfix/fix-impersonate-session`~~ — removed: this branch never existed. Fix #45 is already on
> `develop` (code + `SuperAdminTest` tests, initial commit `561508b`) and is verified green in PR #3 CI.

## Workspace Note (2026-06-27)

This backend is one of three independent git repos under the `nexa/` workspace. On 2026-06-27 the
two sibling Next.js apps received the same AI context system this repo already uses:
- `../nexa-digital-studio-landing/` (Next.js 16, next-intl) — `tsc --noEmit` green.
- `../panel-administrativo-nexa-digital/` (Next.js 16, client-side reducer + localStorage) — has
  ~360 pre-existing TS errors tracked as their tech debt #1.

No changes were made to this repo's code or its existing context docs; this note is informational.

## Agency API Module (2026-06-28) — branch `feature/agency-full-domain`

Built a **tenant-scoped agency domain** so the admin panel (`../panel-administrativo-nexa-digital`,
:3001) persists to Postgres instead of localStorage. This is **separate** from the e-commerce v1 API.

- **Tables/models:** `agency_*` (UUID PK, `BelongsToTenant`), namespaced `App\Models\Agency\*`:
  Client, Project, Service, Supplier, TeamMember, Quote, Invoice, Expense + a per-tenant
  `AgencySetting` singleton. Embedded collections (tasks, items, teamMembers…) stored as `jsonb`;
  reference ids as strings; money/numbers cast to float.
- **Endpoints:** `Route::prefix('agency')->middleware(['auth:sanctum','api.tenant','throttle:api-tenant'])`
  (no Pro gate) — `apiResource` per entity + `GET/PUT settings`. The panel logs in via
  `POST /api/auth/login` and sends `Authorization: Bearer` + `X-Tenant-ID`.
- **Generic base:** `AgencyCrudController` (index/show/destroy + `create()` which `refresh()`es so
  the response carries the full row), `AgencyFormRequest` (camelCase↔snake_case `mapped()`),
  `AgencyResource` (camelCase output, `[]` for null array columns). Tenant-safe lookups via
  `findScoped()` because `SubstituteBindings` runs **before** `api.tenant` (implicit binding would
  leak cross-tenant).
- **Seeder:** `AgencyDemoSeeder` (idempotent, cross-referenced demo data).
- **Docs:** [`docs/api/agency-reference.md`](../../docs/api/agency-reference.md) + **Scribe**
  (`knuckleswtf/scribe`, dev) configured for `api/agency/*` + `api/auth/*` → interactive docs at
  `/docs`, OpenAPI at `/docs.openapi`, Postman at `/docs.postman`. Rebuild: `php artisan scribe:generate`.
- **Gates:** PHPStan level 5 = 0 on the module. CRUD verified end-to-end from the panel.

---
*Update this file at the start and end of each development session.*
