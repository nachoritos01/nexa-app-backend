# QA: SaaS Fase 4 — Stripe + Plans

**Date:** 2026-02-20
**Branch:** feature/saas-phase4-stripe-plans

## Test Results

```
composer test
Tests: 125 passed (348 assertions)
Duration: 12.50s
```

### New Tests Added (21)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/BillingTest.php | 5 | 15 | PASS |
| tests/Feature/PlanLimitsTest.php | 16 | 35 | PASS |

### Existing Tests (104) — No Regressions

| Test File | Tests | Status |
|-----------|-------|--------|
| tests/Unit/Models/removedTest.php | 5 | PASS |
| tests/Unit/Models/OrderTest.php | 11 | PASS |
| tests/Unit/Models/PricingRuleTest.php | 6 | PASS |
| tests/Unit/Models/QuoteTest.php | 5 | PASS |
| tests/Unit/Services/PricingCalculatorTest.php | 8 | PASS |
| tests/Unit/TenantSeedServiceTest.php | 5 | PASS |
| tests/Feature/Api/*.php | 21 | PASS |
| tests/Feature/Livewire/QuoteCalculatorTest.php | 8 | PASS |
| tests/Feature/RbacTest.php | 10 | PASS |
| tests/Feature/TenantIsolationTest.php | 5 | PASS |
| tests/Feature/RegistrationTest.php | 5 | PASS |
| tests/Feature/OnboardingTest.php | 10 | PASS |
| tests/Feature/ExampleTest.php | 1 | PASS |
| tests/Unit/ExampleTest.php | 1 | PASS |

## Manual Verification Checklist

### Billing Page (/admin/billing)
- [x] Accesible por Owner — HTTP 200
- [x] Bloqueada para Admin — HTTP 403
- [x] Bloqueada para Sales — HTTP 403
- [x] Muestra "Plan Pro" con badge "Activa" (verde)
- [x] Muestra "Suscripcion activa" (tenant con subscribed_at)
- [x] Seccion "Uso actual" con 5 recursos y barras de progreso
- [x] Pro: todos los recursos muestran "X / ∞"
- [x] Seccion "Planes available" con 3 cards
- [x] Starter: $299/mes, $239/mes pagando anual
- [x] Growth: $699/mes, $559/mes pagando anual
- [x] Pro: $1,299/mes, $1,039/mes pagando anual
- [x] Plan actual muestra "Plan actual" y badge naranja
- [x] Plan actual boton deshabilitado (disabled)
- [x] Otros planes muestran boton "Cambiar plan"
- [x] Links de checkout apuntan a /billing/checkout?plan=X&period=monthly

### Dashboard Banners
- [x] SaaS Template (Pro suscrito): sin trial banner
- [x] SaaS Template (Pro suscrito): sin plan limits banner
- [x] Link "Plan" visible en sidebar bajo "Configuracion"

### Sidebar Navigation
- [x] Link "Plan" visible para Owner
- [x] Link "Plan" bajo grupo "Configuracion"
- [x] Icono credit-card

### Seeder
- [x] `php artisan db:seed --class=DefaultTenantSeeder` — agrega subscribed_at
- [x] Tenant SaaS Template: plan=pro, subscribed_at!=null

## Issues Found and Fixed

1. **subscribed_at null en tenant existente** — El seeder usa firstOrCreate, asi que tenants creados antes de Fase 4 no tenian subscribed_at. Fix: seeder ahora checa y actualiza si es null (idempotente).

## Files Changed

### Created (8)
- `config/cashier.php`
- `app/Filament/Pages/Billing.php`
- `resources/views/filament/pages/billing.blade.php`
- `app/Http/Controllers/BillingController.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `app/Filament/Widgets/PlanLimitsBanner.php`
- `resources/views/filament/widgets/plan-limits-banner.blade.php`
- `tests/Feature/BillingTest.php`
- `tests/Feature/PlanLimitsTest.php`

### Modified (12+)
- `composer.json` / `composer.lock` — laravel/cashier v16
- `config/saas.php` — precios, price IDs, max_customers, limits
- `app/Models/Tenant.php` — Billable trait + 7 plan helpers
- `app/Providers/AppServiceProvider.php` — Cashier::useCustomerModel(Tenant)
- `routes/web.php` — rutas billing + webhook
- `bootstrap/app.php` — CSRF exception stripe/webhook
- `app/Policies/OrderPolicy.php` — isAtLimit check
- `app/Policies/ProductPolicy.php` — isAtLimit check
- `app/Policies/BranchPolicy.php` — isAtLimit check
- `app/Policies/CustomerPolicy.php` — isAtLimit check
- `database/seeders/DefaultTenantSeeder.php` — subscribed_at
- `database/factories/TenantFactory.php` — subscribed() state
- `resources/views/filament/widgets/trial-banner.blade.php` — boton suscribirse
- `.env.example` — Stripe keys + price IDs
- 5 Cashier migrations

## Pending (requiere Stripe keys)

- [ ] TC-BILL-028: TrialBanner boton "Suscribirse" (requiere tenant en trial)
- [ ] TC-BILL-029: Checkout sin Stripe keys (error controlado)
- [ ] TC-BILL-030: Checkout E2E con Stripe test mode
