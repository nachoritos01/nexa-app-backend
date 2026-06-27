# QA: SaaS Fase 6 PR1 — Core Retention (6.1-6.3)

**Date:** 2026-02-20
**Branch:** feature/saas-phase6-retention-pr1

## Test Results

```
composer test
Tests: 186 passed (466 assertions)
Duration: ~17s
```

### New Tests Added (15)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/HealthScoreTest.php | 5 | ~12 | PASS |
| tests/Feature/RetentionAlertsTest.php | 5 | ~10 | PASS |
| tests/Feature/FeatureUsageTest.php | 3 | ~6 | PASS |
| tests/Feature/CancellationSurveyTest.php | 2 | ~7 | PASS |

### Existing Tests (171) — No Regressions

| Test File | Tests | Status |
|-----------|-------|--------|
| tests/Unit/Models/*.php | 27 | PASS |
| tests/Unit/Services/PricingCalculatorTest.php | 8 | PASS |
| tests/Unit/TenantSeedServiceTest.php | 5 | PASS |
| tests/Feature/Api/*.php | 22 | PASS |
| tests/Feature/Livewire/QuoteCalculatorTest.php | 8 | PASS |
| tests/Feature/RbacTest.php | 10 | PASS |
| tests/Feature/TenantIsolationTest.php | 5 | PASS |
| tests/Feature/RegistrationTest.php | 5 | PASS |
| tests/Feature/OnboardingTest.php | 10 | PASS |
| tests/Feature/BillingTest.php | 5 | PASS |
| tests/Feature/PlanLimitsTest.php | 16 | PASS |
| tests/Feature/TrialExpiryTest.php | 12 | PASS |
| tests/Feature/ConversionUiTest.php | 14 | PASS |
| tests/Feature/SuperAdminTest.php | 9 | PASS |
| tests/Feature/ExportTest.php | 5 | PASS |
| tests/Feature/SaasTemplatePagesTest.php | 6 | PASS |
| tests/Feature/ExampleTest.php | 1 | PASS |
| tests/Unit/ExampleTest.php | 1 | PASS |

## Manual Verification Checklist

### Super-Admin Dashboard (/super-admin)
- [x] Widget "Health Score Distribution" doughnut con 4 segmentos (Saludable, En riesgo, Critico, Sin datos)
- [x] Widget "Cancellation Reasons" doughnut con datos reales ("Precio muy alto" tras exit survey)
- [x] Ambos widgets posicionados correctamente ($sort = 3 y 4)

### Super-Admin Tenants (/super-admin/tenants)
- [x] Column "Health" con badges numericos coloreados (rojo 0, amarillo 30)
- [x] Column es sortable (click en header ordena)
- [x] Filtro "Health" con opciones: Saludable (70+), En riesgo (50-69), Critico (<50)

### Billing Page (/admin/billing)
- [x] Seccion "Gestionar suscripcion" visible para tenants suscritos con stripe_id
- [x] Boton "Gestionar payment" (gris) linkea a billing.portal
- [x] Boton "Cancelar suscripcion" (rojo) abre modal
- [x] Modal muestra titulo "Cancelar suscripcion"
- [x] Modal muestra 5 radio buttons: Precio, Faltan funcionalidades, Cerro negocio, Competencia, Otro
- [x] Modal muestra textarea "Cuentanos mas (optional)..."
- [x] Modal muestra botones "Volver" y "Continuar con cancelacion"

### Login + Tenant Isolation
- [x] Login con user nuevo (test@saas-template.com) muestra dashboard de su tenant (Products Test)
- [x] Datos aislados: 0 orders, 0 customers, plan Starter, trial 13 dias
- [x] Super-admin (/super-admin) muestra 403 para user no super-admin

### Exit Survey E2E
- [x] Submit con reason="price" y details="Demasiado caro para mi negocio"
- [x] CancellationSurvey guardada en DB (verificado via tinker)
- [x] Redirect a billing/portal (500 esperado: sin Stripe API key en local)
- [x] Widget "Cancellation Reasons" actualizado con "Precio muy alto"

### Health Filter
- [x] Filtro "Critico (<50)" muestra 5 de 7 tenants (excluye null)
- [x] Badge "Filtros activos" visible con opcion de quitar filtro
- [x] URL refleja filtro: `?tableFilters[health_status][value]=critical`

### Commands
- [x] `php artisan saas:health-score` — calcula scores, output en consola con name y score
- [x] `php artisan saas:retention-alerts` — output de reminders y alerts enviados

## Issues Found and Fixed

1. **diffInDays retorna signed con strings del DB** — `now()->diffInDays($maxCreatedAt)` retornaba valores negativos cuando la fecha era del pasado (string crudo de `max()`), causando que la condicion `< 14` siempre fuera true y nunca enviara reminders. Fix: usar `Carbon::parse($string)` con `absolute: true` para obtener siempre valores positivos.

2. **Exit survey modal no visible en primer click** — Chrome DevTools MCP no detectaba el modal como texto en el snapshot despues del primer click. Investigacion mostro que el DOM si contenia el modal (`display: flex`, `visibility: visible`). Issue fue del timing de Livewire morphing con la herramienta de snapshot. El modal funciona correctamente en el browser.

## Files Changed

### Created (12)
- `database/migrations/2026_02_20_230001_create_feature_usages_table.php`
- `database/migrations/2026_02_20_230002_add_health_score_to_tenants_table.php`
- `database/migrations/2026_02_20_230003_add_last_login_at_to_users_table.php`
- `database/migrations/2026_02_20_230004_create_cancellation_surveys_table.php`
- `app/Models/FeatureUsage.php`
- `app/Models/CancellationSurvey.php`
- `app/Console/Commands/CalculateHealthScore.php`
- `app/Console/Commands/SendRetentionAlerts.php`
- `app/Notifications/InactivityReminderNotification.php`
- `app/Notifications/LowHealthScoreAlert.php`
- `app/Filament/SuperAdmin/Widgets/HealthScoreDistribution.php`
- `app/Filament/SuperAdmin/Widgets/ChurnReasonsChart.php`

### Modified (10)
- `app/Models/Tenant.php` — health_score fillable/casts, relationships, healthColor/healthLabel helpers
- `app/Models/User.php` — last_login_at fillable/cast
- `config/saas.php` — seccion retention con weights, thresholds, tracked_features, reasons
- `app/Providers/AppServiceProvider.php` — Login event listener para last_login_at
- `app/Filament/Resources/OrderResource/Pages/CreateOrder.php` — FeatureUsage::track (order + payment)
- `app/Filament/Resources/CustomerResource/Pages/CreateCustomer.php` — FeatureUsage::track
- `app/Filament/Resources/ProductResource/Pages/CreateProduct.php` — FeatureUsage::track
- `app/Services/PdfGenerator.php` — FeatureUsage::track
- `app/Http/Controllers/ExportController.php` — FeatureUsage::track
- `app/Filament/Pages/Billing.php` — Cancel modal properties y methods
- `resources/views/filament/pages/billing.blade.php` — 2 botones + modal UI
- `app/Filament/SuperAdmin/Resources/TenantResource.php` — Health column + filter
- `routes/console.php` — Schedule 2 nuevos commands

### Tests Created (4)
- `tests/Feature/HealthScoreTest.php`
- `tests/Feature/RetentionAlertsTest.php`
- `tests/Feature/FeatureUsageTest.php`
- `tests/Feature/CancellationSurveyTest.php`

### Documentation Updated
- `docs/saas/11-SAAS_EVOLUTION_ROADMAP.md` — 6.1, 6.2, 6.3 marcados completados
- `.claude/context/current.md` — estado actualizado con Fase 6 PR1
- `docs/qa/fase6-retention-pr1/` — este directorio
