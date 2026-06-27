# QA: SaaS Fase 5 PR1 — Trial Expiry + Conversion UI

**Date:** 2026-02-20
**Branch:** feature/saas-phase5-trial-conversion

## Test Results

```
composer test
Tests: 151 passed (388 assertions)
Duration: 14.63s
```

### New Tests Added (26)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/TrialExpiryTest.php | 12 | 40 | PASS |
| tests/Feature/ConversionUiTest.php | 14 | 36 | PASS |

### Existing Tests (125) — No Regressions

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
| tests/Feature/BillingTest.php | 5 | PASS |
| tests/Feature/PlanLimitsTest.php | 16 | PASS |
| tests/Feature/ExampleTest.php | 1 | PASS |
| tests/Unit/ExampleTest.php | 1 | PASS |

## Manual Verification Checklist

### /suspended Page
- [x] Accesible por user con tenant suspendido
- [x] Muestra 3 plan cards (Starter, Growth, Pro)
- [x] Growth tiene badge "Popular" (inline, no clipped)
- [x] Cada plan muestra precio, limites, CTA "Suscribirme a X"
- [x] Links de checkout apuntan a /billing/checkout?plan=X&period=monthly
- [x] Link "Contactar soporte" visible
- [x] Boton "Cerrar sesion" funcional

### Dashboard — CurrentPlanWidget
- [x] SaaS Template (Pro, suscrito): muestra "Pro", badge "Activa" verde, sin boton Upgrade
- [x] Dtf merida (Starter, trial 13d): muestra "Starter", badge "Trial" amarillo, barra progreso, boton Upgrade

### Dashboard — TrialBanner
- [x] Trial 13d: banner verde "13 dias restantes"
- [x] Trial 1d: banner rojo "1 dia restantes"
- [x] Trial expirado en grace: banner rojo "Tu periodo de test ha terminado"
- [x] Sticky CSS removido (no funciona en Filament widget container)

### Dashboard — PlanLimitsBanner (con TestLimitsSeeder)
- [x] Orders 45/50 (90%): banner warning amarillo
- [x] Items 18/20 (90%): banner warning amarillo
- [x] Customers 170/200 (85%): banner warning amarillo
- [x] Locationes 1/1 (100%): banner danger rojo
- [x] Users 2/2 (100%): banner danger rojo

### Middleware — Suspension Redirect
- [x] Tenant suspendido redirige a /suspended
- [x] Rutas billing/checkout permitidas (bypass)
- [x] Ruta /suspended permitida (no loop)
- [x] Logout permitido
- [x] Tenant activo (suscrito o trial) no redirige

## Issues Found and Fixed

1. **diffInDays floor rounding** — `now()->addDays(3)` con `diffInDays` retornaba 2 por diferencia sub-segundo. Fix: usar `whereDate()` + `startOfDay()` para comparaciones de fecha sin hora.

2. **canAccessPanel 403 antes de redirect** — Filament Authenticate middleware corre antes de EnsureTenant. Si tenant suspendido (is_active=false), `canAccessPanel()` retornaba false → 403 sin oportunidad de redirect. Fix: `canAccessPanel()` ahora permite cualquier tenant existente; EnsureTenant maneja el redirect.

3. **"Popular" badge clipped** — Posicionamiento absoluto con `-top-3` era cortado por grid overflow. Fix: badge inline-block dentro del padding del card.

4. **Sticky CSS incash** — `sticky top-0 z-50` no funciona en Filament widget container por stacking context propio. Fix: removido (el banner ya tiene sort=-2 y aparece primero).

5. **TestLimitsSeeder state varchar(5)** — `fake()->state()` generaba names largos ("Indiana") para column varchar(5). Fix: valor hardcoded 'YUC'.

## Files Changed

### Created (10)
- `app/Console/Commands/CheckTrialExpiry.php`
- `app/Notifications/TrialExpiringNotification.php`
- `app/Notifications/TrialExpiredNotification.php`
- `app/Notifications/TenantSuspendedNotification.php`
- `resources/views/suspended.blade.php`
- `app/Filament/Widgets/CurrentPlanWidget.php`
- `resources/views/filament/widgets/current-plan.blade.php`
- `app/Filament/Concerns/NotifiesNearLimit.php`
- `tests/Feature/TrialExpiryTest.php`
- `tests/Feature/ConversionUiTest.php`

### Modified (9)
- `app/Models/Tenant.php` — `isSuspended()`, `suspend()`
- `app/Models/User.php` — `canAccessPanel()` permite any tenant
- `app/Http/Middleware/EnsureTenant.php` — suspension redirect + bypass routes
- `routes/console.php` — `Schedule::command('saas:check-trial-expiry')->daily()`
- `routes/web.php` — ruta `/suspended`
- `resources/views/filament/widgets/trial-banner.blade.php` — sticky removido
- `app/Filament/Resources/OrderResource/Pages/CreateOrder.php` — near-limit check
- `app/Filament/Resources/ProductResource/Pages/CreateProduct.php` — near-limit check
- `app/Filament/Resources/CustomerResource/Pages/CreateCustomer.php` — near-limit check
- `app/Filament/Resources/BranchResource/Pages/CreateBranch.php` — near-limit check

### Testing Utils (no committed)
- `database/seeders/TestLimitsSeeder.php` — seeder para llenar tenant cerca de limites (respaldado en docs/)

## Test Data Seeder

El file `docs/qa/fase5-trial-conversion/test-limits-seeder-reference.md` contiene el codigo del seeder usado para tests de limites. Para recrearlo:

```bash
# Copiar contenido del .md a database/seeders/TestLimitsSeeder.php
php artisan db:seed --class=TestLimitsSeeder
```
