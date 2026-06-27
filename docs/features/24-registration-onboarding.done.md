# Feature: Registration y Onboarding (SaaS Fase 3)

**Status:** Completada
**Date:** 2026-02-20
**Branch:** feature/saas-phase3-registration-onboarding

## Resumen

Nuevas businesss pueden auto-registrarse en `/admin/register` y empezar a usar SaaS Template en <30 segundos. El sistema crea automaticamente: user, tenant, datos template (sizes, colors, models, pricing, limits), asigna rol owner, y redirige al wizard de onboarding.

## Componentes

### 1. Registration Custom (`/admin/register`)
- Extiende `Filament\Pages\Auth\Register`
- Campos: name, email, name del negocio, telefono, ciudad, password
- Override `getPasswordFormComponent()` — quita `dehydrateStateUsing(Hash::make)` para evitar doble hash (el cast `'hashed'` en User se encarga)
- Override `handleRegistration()` — crea User + Tenant + pivot + Spatie role + seed data en `DB::transaction`
- Slug unico auto-generado con suffix numerico si ya existe

### 2. TenantSeedService
- Service reutilizable que crea datos template para un tenant nuevo
- 32 records: 5 sizes, 3 colors, 2 item_categories, 12 pricing_rules, 10 dimension_limits
- Idempotente (`firstOrCreate` con tenant_id en keys)
- Bind/restore de tenant context con try/finally

### 3. Onboarding Wizard (`/admin/onboarding`)
- Filament Page con 4 pasos skippable:
  1. Info del negocio (telefono, ciudad, direccion) → tenant.settings
  2. Primera location → crea Branch
  3. Primer item → crea Product
  4. Primer order → informativo, link al Order Wizard
- Redirect a dashboard si ya completo
- Marca `onboarding_completed_at` al finalizar

### 4. Trial Banner Widget
- Solo visible para tenants en trial o expirados
- Color dinamico: verde (>7d), amarillo (3-7d), rojo (<3d), expirado
- Sort -2 (aparece primero en dashboard)

### 5. Onboarding Banner Widget
- Solo visible si onboarding no completado
- Muestra progreso X/4 con barra y link a `/admin/onboarding`
- Sort -1 (aparece segundo en dashboard)

### 6. config/saas.php
- `trial.days` = 14, `trial.grace_days` = 3
- `plans.default` = starter
- Plan limits: starter (50 orders, 2 users, 1 branch, 20 products), growth (200, 5, 3, 100), pro (unlimited)
- `onboarding.steps` = [business_info, first_branch, first_product, first_order]

### 7. Tenant Model Helpers
- Trial: `isOnTrial()`, `trialDaysRemaining()`, `isTrialExpired()`, `isInGracePeriod()`, `trialColorStatus()`
- Onboarding: `isOnboardingComplete()`, `markOnboardingStep()`, `hasCompletedOnboardingStep()`, `onboardingProgress()`
- Slug: `generateSlug()` static con dedup numerico

### 8. Migrations
- `add_onboarding_to_tenants_table`: `onboarding_steps` (JSON), `onboarding_completed_at` (timestamp)
- `update_catalog_unique_constraints_for_multitenancy`: Cambia unique constraints de catalog tables a composites con tenant_id

## Tests (20 nuevos, 104 total)

### RegistrationTest (5 tests)
- Pagina accesible
- Registration crea user + tenant + seed data
- Slug unico con suffix
- Validacion de campos requeridos
- Email unico

### OnboardingTest (10 tests)
- Accesible cuando no completado
- Redirect a dashboard cuando completado
- Progreso de pasos
- Trial helpers (activo, expirado, gracia, suscrito)
- Slug unico

### TenantSeedServiceTest (5 tests)
- Seed completo (32 records)
- Idempotente
- Aislado entre tenants
- Restaura contexto previo
- Funciona sin contexto previo

## Decisiones Tecnicas

| Decision | Alternativa | Razon |
|----------|-------------|-------|
| Extend Filament Register | Custom Livewire | Reutiliza validacion, UI, rate limiting del vendor |
| Override `getPasswordFormComponent()` | Override `handleRegistration()` para hash manual | Mas limpio, un solo lugar donde se hashea |
| TenantSeedService separado | Seeder con parametro | Reutilizable en registration, tests, artisan commands |
| config/saas.php | .env variables | Centralizado, accesible con config(), funciona con config:cache |
| Composite unique constraints | Application-level validation | DB-level integrity, no race conditions |
