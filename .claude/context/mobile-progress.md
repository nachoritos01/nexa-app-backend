# Mobile App — Progress

**Estado:** Pre-desarrollo — Backend API ready, roadmap creado
**Roadmap:** `docs/mobile/MOBILE_ROADMAP.md`
**Docs:** `docs/mobile/`

---

## Progress

| Fase | Estado | Semanas |
|------|--------|---------|
| PRE-0: Backend API Ready | Completada | — |
| Fase 1: Setup + Auth | Pendiente | 1-2 |
| Fase 2: Dashboard + Navegacion | Pendiente | 3-4 |
| Fase 3: Ordenes | Pendiente | 5-6 |
| Fase 4: Clientes + Cotizaciones | Pendiente | 7-8 |
| Fase 5: Push + Offline | Pendiente | 9-10 |
| Fase 6: Polish + App Stores | Pendiente | 11-12 |

## Mobile API Endpoints (PR #45) — Backend Ready

What was done:
1. **Auth API:** Login (Sanctum token sin tenant_id), logout, me (user + tenants con roles), push-token
2. **Dashboard Stats API:** `DashboardStatsService` compartido con Filament widget, cache 60s, weekly chart
3. **Customer CRUD Expansion:** store (201 + auto tenant_id), update (partial), orders (paginated)
4. **Mobile Roadmap:** 6 fases, ~12 semanas, milestones alpha/beta/launch
5. **Git Flow:** Estandarizado con nomenclatura de ramas, commits, PRs y tags del historial
6. **Tests:** 25 new (286 total, 745 assertions)

## Key Files

- `app/Http/Controllers/Api/AuthController.php` — Auth endpoints (login/logout/me/push-token)
- `app/Services/DashboardStatsService.php` — Reusable stats (API + Filament widget)
- `app/Http/Controllers/Api/V1/DashboardController.php` — Dashboard stats API
- `app/Http/Controllers/Api/V1/CustomerController.php` — Extended with store/update/orders
- `docs/mobile/MOBILE_ROADMAP.md` — Roadmap activo de la app movil
- `docs/git-flow.md` — Convenciones estandarizadas

## Architectural Decisions (Mobile API)

- Login creates Sanctum token WITHOUT tenant_id (mobile selects tenant after login)
- Auth endpoints (`/api/auth/*`) use only `auth:sanctum` — no `api.tenant`/`api.pro` (work on any plan)
- DashboardStatsService extracted from StatsOverview widget (single source of truth)
- Weekly chart uses `toBase()->get()` for PHPStan compatibility (returns stdClass, not Order model)
- push_token stored on users table (simple, one device per user)
