# QA Summary: SaaS Fase 6 PR2 — Win-back, Referidos, Messaging Auto (6.4-6.6)

**Date:** 2026-02-22
**Branch:** feature/saas-phase6-retention-pr2

## Test Results

```
composer test
Tests: 198 passed (490 assertions)
Duration: ~18s
```

### New Tests Added (12)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/WinbackEmailTest.php | 4 | ~10 | PASS |
| tests/Feature/ReferralProgramTest.php | 4 | ~10 | PASS |
| tests/Feature/MessagingAutoNotifyTest.php | 4 | ~8 | PASS |

### Existing Tests (186) — No Regressions

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
| tests/Feature/HealthScoreTest.php | 5 | PASS |
| tests/Feature/RetentionAlertsTest.php | 5 | PASS |
| tests/Feature/FeatureUsageTest.php | 3 | PASS |
| tests/Feature/CancellationSurveyTest.php | 2 | PASS |
| tests/Feature/ExampleTest.php | 1 | PASS |
| tests/Unit/ExampleTest.php | 1 | PASS |

## Manual Verification Checklist

### Messaging Settings (/admin/messaging-settings)
- [x] Pagina accesible desde sidebar (Configuracion > Messaging)
- [x] Toggle "Habilitar notificaciones automaticas" funcional
- [x] 4 checkboxes: Confirmado, En Operations, Listo, Entregado
- [x] Checkboxes deshabilitados (opacity-50) cuando toggle off
- [x] Boton "Guardar configuracion" persiste en DB (tenant.settings)
- [x] Mensaje de confirmacion "Configuracion guardada" al guardar

### Billing Page (/admin/billing) — Referral Section
- [x] Seccion "Programa de Referidos" visible
- [x] Input readonly con URL de referido (http://localhost:8000/admin/register?ref=CODE)
- [x] Boton "Copiar" copia URL al clipboard
- [x] Codigo de referido mostrado debajo del input
- [x] Card "Referidos" con contador numerico
- [x] Card "Convertidos" con contador numerico
- [x] Dark mode: cards con fondo dark:bg-gray-900 visible y contrastado

### Registration with ?ref= param
- [x] GET /admin/register?ref=RTHPYICM muestra formulario de registration
- [x] Registration exitoso crea tenant con referral_code propio
- [x] Referral record creado en DB: referrer_tenant_id=1, referred_tenant_id=8
- [x] Referral stats actualizados en billing page del referrer (1 referido)

### Super-Admin Dashboard (/super-admin)
- [x] Widget ReferralStats visible con 3 metricas
- [x] Total Referidos, Conversiones, Recompensas con datos correctos

### Commands
- [x] `php artisan saas:winback-emails` — ejecuta sin errores, output "Win-back emails sent: 0"

## Issues Found and Fixed

1. **created_at no mass-assignable en CancellationSurvey tests** — `CancellationSurvey::create(['created_at' => ...])` silently ignora created_at porque es un timestamp de Eloquent, no un campo fillable. `updateQuietly(['created_at' => ...])` tambien falla porque `fill()` no asigna timestamps. Fix: asignar propiedad directamente `$survey->created_at = $date; $survey->saveQuietly();`

2. **NOT NULL constraint en customer_phone para Messaging tests** — `customer_phone => null` viola constraint de DB. Fix: usar `customer_phone => ''` (empty string), que es falsy y pasa el guard `if (! $phone)`.

3. **Dark mode stat cards invisibles** — `dark:bg-gray-700/50` (50% opacidad) en las cards de Referidos/Convertidos no proporcionaba suficiente contraste contra el fondo dark:bg-gray-800 del contenedor. Fix: cambiar a `dark:bg-gray-900` (sin opacidad) para un fondo solido mas oscuro.

4. **PHPStan: property access en Order model** — `$order->tenant_id` reportado como propiedad no encontrada por PHPStan. Fix: usar `$order->getAttribute('tenant_id')` con docblock `/** @var int|null $tenantId */`.

5. **PHPStan: return types en relationships** — `$survey->tenant` y `$tenant->owner` retornan `Model|null` en vez de tipo especifico. Fix: agregar docblocks `/** @var Tenant|null */` y `/** @var User|null */` antes de usarlos.

## Files Changed

### Created (12)
- `app/Notifications/WinbackNotification.php`
- `app/Console/Commands/SendWinbackEmails.php`
- `database/migrations/2026_02_22_000001_create_referrals_table.php`
- `database/migrations/2026_02_22_000002_add_referral_code_to_tenants_table.php`
- `app/Models/Referral.php`
- `app/Observers/OrderObserver.php`
- `app/Filament/Pages/MessagingSettings.php`
- `resources/views/filament/pages/messaging-settings.blade.php`
- `app/Filament/SuperAdmin/Widgets/ReferralStats.php`
- `tests/Feature/WinbackEmailTest.php`
- `tests/Feature/ReferralProgramTest.php`
- `tests/Feature/MessagingAutoNotifyTest.php`

### Modified (11)
- `config/saas.php` — secciones winback, referral, messaging_auto
- `routes/console.php` — Schedule saas:winback-emails
- `app/Models/Tenant.php` — referral_code, boot event, relationships
- `app/Filament/Pages/Auth/Register.php` — captura ?ref= param
- `app/Http/Controllers/StripeWebhookController.php` — processReferralReward()
- `app/Filament/Pages/Billing.php` — getReferralData()
- `resources/views/filament/pages/billing.blade.php` — seccion referidos + dark mode fix
- `app/Services/NotificationService.php` — sendSms/hasTwilio publicos
- `app/Providers/AppServiceProvider.php` — OrderObserver registrado
- `docs/saas/11-SAAS_EVOLUTION_ROADMAP.md` — 6.4, 6.5, 6.6 completados
- `.claude/context/current.md` — estado actualizado

### Documentation
- `docs/qa/fase6-retention-pr2/` — este directorio (test-plan, test-cases, test-summary)
