# Activate `hasModule()` Feature Flags + Clean Dead Code

**Status:** Done
**Date:** 2026-03-11
**Branch:** feature/activate-module-flags

## Resumen

La infraestructura de modulos fue construida en Phase 22 (`config/modules.php`, helper `hasModule()`, `PaymentGatewayInterface`, 6 domain events) pero `hasModule()` nunca se llama en el codigo. Los 5 flags (payments, customer_portal, locations, api, exports) existen pero no hacen nada. Este plan los conecta en routes, Filament y controllers para que los modulos puedan activarse/desactivarse via `.env`. Tambien limpia CSS y docs de loyalty program (muerto).

## Arquitectura

- **Config** — `config/modules.php` (ya existe, 5 flags: payments, customer_portal, locations, api, exports)
- **Helper** — `hasModule(string $module): bool` en `app/helpers.php` (ya existe)
- **Capas de proteccion** — Routes (primera linea), Filament canAccess/shouldRegisterNavigation (UI), Controllers abort_unless (defense-in-depth)

## Funcionalidades

### Phase 1: Routes

#### 1A. `routes/web.php`
- **payments**: Envolver `/payment/*`, `/billing/*`, `/stripe/webhook` en `if (hasModule('payments'))`
- **customer_portal**: Envolver `/my-account/*` (auth + portal groups)
- **exports**: Envolver `/admin/exports/{type}`

#### 1B. `routes/api.php`
- **api**: Envolver auth group (lines 55-63) + v1 group (lines 68-76)
- **payments**: Dentro de v1, envolver `payments` resource

#### 1C. `bootstrap/app.php` — Fix critico
- Actual: `$middleware->redirectGuestsTo(fn () => route('customer.login'))`
- Cuando `MODULE_CUSTOMER_PORTAL=false`, `route('customer.login')` lanza `RouteNotDefinedException`
- **Fix:** `fn () => hasModule('customer_portal') ? route('customer.login') : route('filament.admin.auth.login')`

### Phase 2: Filament Pages & Resources

Agregar `hasModule()` check a `canAccess()` (early return false):

| File | Module flag |
|------|-------------|
| `Filament/Pages/Billing.php` | `payments` |
| `Filament/Pages/CashRegister.php` | `payments` |
| `Filament/Pages/ApiSettings.php` | `api` |
| `Filament/Pages/WebhookSettings.php` | `api` |

Agregar `shouldRegisterNavigation()` + `canAccess()`:

| File | Module flag |
|------|-------------|
| `Filament/Resources/LocationResource.php` | `locations` |
| `Filament/Resources/CustomerResource.php` | `customer_portal` |

### Phase 3: Filament Widgets

Agregar `hasModule('payments')` a `canView()`:

- `CurrentPlanWidget.php`
- `PlanLimitsBanner.php`
- `TrialBanner.php`
- `RevenueChart.php` (nuevo `canView()`)
- `MonthlyComparison.php`

### Phase 4: OrderResource conditional fields

- Location select field: `->visible(fn () => hasModule('locations'))`
- Location table column: `->visible(fn () => hasModule('locations'))`
- PaymentsRelationManager: conditional en `getRelations()`

### Phase 5: Controller defense-in-depth

`abort_unless(hasModule('x'), 404)` al inicio del primer metodo o constructor:

| Controller | Module |
|-----------|--------|
| `BillingController` | `payments` |
| `PaymentStatusController` | `payments` |
| `StripeWebhookController` | `payments` |
| `CustomerAuthController` | `customer_portal` |
| `CustomerPortalController` | `customer_portal` |
| `CustomerAddressController` | `customer_portal` |
| `ExportController` | `exports` |

### Phase 6: Dead code cleanup

- `resources/css/app.css` — Eliminar lines 78-93 (`.tier-bronze`, `.tier-silver`, `.tier-gold`, `.tier-vip`)
- `docs/qa/loyalty-program/` — Eliminar directorio (3 files)

### Phase 7: Tests

Nuevo archivo `tests/Feature/ModuleFlagsTest.php`:

- `test_all_modules_enabled_by_default`
- `test_unknown_module_returns_false`
- `test_disabled_payments_returns_404_on_billing`
- `test_disabled_customer_portal_returns_404`
- `test_disabled_exports_returns_404`
- `test_disabled_api_returns_404_on_v1`

## Files

### Modificados (22)

**Routes/Bootstrap (3):**
- `routes/web.php`
- `routes/api.php`
- `bootstrap/app.php`

**Filament Pages (4):**
- `app/Filament/Pages/Billing.php`
- `app/Filament/Pages/CashRegister.php`
- `app/Filament/Pages/ApiSettings.php`
- `app/Filament/Pages/WebhookSettings.php`

**Filament Resources (3):**
- `app/Filament/Resources/LocationResource.php`
- `app/Filament/Resources/CustomerResource.php`
- `app/Filament/Resources/OrderResource.php`

**Filament Widgets (5):**
- `app/Filament/Widgets/CurrentPlanWidget.php`
- `app/Filament/Widgets/PlanLimitsBanner.php`
- `app/Filament/Widgets/TrialBanner.php`
- `app/Filament/Widgets/RevenueChart.php`
- `app/Filament/Widgets/MonthlyComparison.php`

**Controllers (7):**
- `app/Http/Controllers/BillingController.php`
- `app/Http/Controllers/PaymentStatusController.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `app/Http/Controllers/CustomerAuthController.php`
- `app/Http/Controllers/CustomerPortalController.php`
- `app/Http/Controllers/CustomerAddressController.php`
- `app/Http/Controllers/ExportController.php`

### Eliminados (4)
- `resources/css/app.css` (edit, no delete)
- `docs/qa/loyalty-program/test-cases.md`
- `docs/qa/loyalty-program/test-plan.md`
- `docs/qa/loyalty-program/test-summary.md`

### Nuevos (1)
- `tests/Feature/ModuleFlagsTest.php`

## Verificacion

1. `composer test` — tests pass (existing + new)
2. `composer analyse` — PHPStan clean
3. Comportamiento default sin cambios (todos los flags default `true`)
4. `MODULE_CUSTOMER_PORTAL=false` → customer routes 404, admin hides CustomerResource
5. `MODULE_PAYMENTS=false` → billing/payment routes 404, widgets hidden
