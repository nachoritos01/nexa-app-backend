# QA: SaaS Fase 3 — Registration y Onboarding

**Date:** 2026-02-20
**Branch:** feature/saas-phase3-registration-onboarding

## Test Results

```
composer test
Tests: 104 passed (316 assertions)
Duration: 9.30s
```

### New Tests Added (20)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/RegistrationTest.php | 5 | 25 | PASS |
| tests/Feature/OnboardingTest.php | 10 | 18 | PASS |
| tests/Unit/TenantSeedServiceTest.php | 5 | 16 | PASS |

### Existing Tests (84) — No Regressions

| Test File | Tests | Status |
|-----------|-------|--------|
| tests/Unit/Models/removedTest.php | 5 | PASS |
| tests/Unit/Models/OrderTest.php | 11 | PASS |
| tests/Unit/Models/PricingRuleTest.php | 6 | PASS |
| tests/Unit/Models/QuoteTest.php | 5 | PASS |
| tests/Unit/Services/PricingCalculatorTest.php | 8 | PASS |
| tests/Feature/Api/*.php | 21 | PASS |
| tests/Feature/Livewire/QuoteCalculatorTest.php | 8 | PASS |
| tests/Feature/RbacTest.php | 10 | PASS |
| tests/Feature/TenantIsolationTest.php | 5 | PASS |
| tests/Feature/ExampleTest.php | 1 | PASS |
| tests/Unit/ExampleTest.php | 1 | PASS |

## Manual Verification Checklist

### Registration
- [x] `/admin/register` — form visible con campos extra (negocio, telefono, ciudad)
- [x] Registration crea User + Tenant + pivot + Spatie role + seed data
- [x] Redirect a `/admin/onboarding` post-registration
- [x] Slug unico: segundo registration con mismo name genera suffix numerico

### Onboarding (2 pasos)
- [x] Step 1 (Location): formulario identico a BranchResource (name, address, city, state, zip, phone, schedule)
- [x] Step 1: pre-fill de city y phone desde datos de registration (tenant.settings)
- [x] Step 1: state usa Select con `EnviaShippingService::MEXICO_STATES` (searchable)
- [x] Step 2 (Primer order): informativo, indica que puede crear orders desde el panel
- [x] Completar onboarding marca `onboarding_completed_at`
- [x] Redirect a dashboard al completar

### Dashboard Widgets
- [x] Trial banner visible con color correcto (verde >7d, amarillo 3-7d, rojo <3d)
- [x] Onboarding banner con progreso X/2 (solo si onboarding incompleto)
- [x] First Product banner verde (solo si onboarding completo + 0 items)
- [x] First Product banner desaparece al crear primer item
- [x] SaaS Template (tenant #1): sin trial banner, sin onboarding banner, sin product banner

### Orden de widgets en dashboard
- [x] Sort -2: Trial banner
- [x] Sort -1: Onboarding banner
- [x] Sort 0: First Product banner

## Iteraciones del Onboarding

El onboarding se simplifico en 3 iteraciones:

| Version | Steps | Cambio |
|---------|-------|--------|
| v1 | 4 pasos (Negocio, Location, Item, Primer order) | Implementacion inicial |
| v2 | 3 pasos (Location, Item, Primer order) | Eliminado paso Negocio (redundante con registration) |
| v3 (actual) | 2 pasos (Location, Primer order) | Eliminado paso Item (formulario incompleto vs ProductResource) |

### Razones de simplificacion

1. **Step Negocio eliminado**: city, phone y address ya se capturan en el registration y en el paso de location. Datos redundantes.
2. **Step Item eliminado**: el formulario del onboarding no incluia campos requeridos de ProductResource (`material`, `description`). Creaba items incompletos. Reemplazado por banner "Crea tu primer item" en dashboard.

## Issues Found and Fixed

1. **Catalog unique constraints** — `sizes.code`, `colors.name`, `item_categories.code`, `dimension_limits.(size,side)` tenian unique constraints sin `tenant_id`. Migration creada para cambiar a composites.
2. **RegistrationResponse return type** — `redirect()->to()` retorna `Livewire\Redirector` en contexto Livewire, no `RedirectResponse`. Union type agregado.
3. **Onboarding branch form mismatch** — El formulario de location en onboarding no tenia state (Select), zip ni schedule. Alineado con BranchResource.
4. **Onboarding product form incomplete** — Missing `material` (requerido) y `description`. Eliminado paso, reemplazado por banner.

## Files Changed

### Created (16)
- `database/migrations/xxxx_add_onboarding_to_tenants_table.php`
- `database/migrations/xxxx_update_catalog_unique_constraints_for_multitenancy.php`
- `config/saas.php`
- `app/Services/TenantSeedService.php`
- `app/Filament/Pages/Auth/Register.php`
- `app/Http/Responses/RegistrationResponse.php`
- `app/Filament/Pages/Onboarding.php`
- `resources/views/filament/pages/onboarding.blade.php`
- `resources/views/filament/pages/onboarding-submit-button.blade.php`
- `app/Filament/Widgets/TrialBanner.php`
- `resources/views/filament/widgets/trial-banner.blade.php`
- `app/Filament/Widgets/OnboardingBanner.php`
- `resources/views/filament/widgets/onboarding-banner.blade.php`
- `app/Filament/Widgets/FirstProductBanner.php`
- `resources/views/filament/widgets/first-product-banner.blade.php`
- `tests/Feature/RegistrationTest.php`
- `tests/Feature/OnboardingTest.php`
- `tests/Unit/TenantSeedServiceTest.php`

### Modified (6)
- `app/Models/Tenant.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Providers/AppServiceProvider.php`
- `database/seeders/DefaultTenantSeeder.php`
- `database/factories/TenantFactory.php`
- `docs/saas/11-SAAS_EVOLUTION_ROADMAP.md`
