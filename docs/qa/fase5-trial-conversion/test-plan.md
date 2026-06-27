# Test Plan — SaaS Fase 5 PR1: Trial Expiry + Conversion UI

**Version:** 1.0
**Date:** 2026-02-20
**Feature:** Trial expiry flow, suspension, conversion UI, near-limit notifications
**Branch:** feature/saas-phase5-trial-conversion

---

## 1. Objetivo

Validar que el flujo completo de trial → expiracion → suspension → conversion funciona correctamente: el command diario envia notificaciones y suspende tenants, el middleware redirige suspendidos, la pagina /suspended muestra planes, y los widgets de dashboard informan al user sobre su plan y limites.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Tenant model | isSuspended(), suspend() |
| Notifications | TrialExpiringNotification (3d/1d), TrialExpiredNotification (0d), TenantSuspendedNotification |
| CheckTrialExpiry command | Job diario: avisos, expiracion, suspension |
| EnsureTenant middleware | Redirect suspendidos, bypass billing/checkout/logout |
| /suspended page | Pagina standalone con 3 plan cards y CTAs |
| CurrentPlanWidget | Widget dashboard: plan, status, trial progress, upgrade |
| NotifiesNearLimit trait | Warning notification al crear recurso cerca del limite |
| canAccessPanel | Permite users con tenants suspendidos |
| PlanLimitsBanner | Banners warning/danger en dashboard (existente, verificado) |

### Fuera de alcance

- Landing page publica — Fase 5 PR2
- Pricing page — Fase 5 PR2
- Super-Admin dashboard — Fase 5 PR2
- Stripe Checkout E2E — requiere Stripe keys
- Email delivery real — solo se verifica que Notification se envia

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (`TrialExpiryTest.php`, `ConversionUiTest.php`) | Command, suspension, middleware, widget |
| Manuales funcionales | Browser + Chrome DevTools MCP | UI suspended, dashboard widgets, banners |
| Seeder de stress | `TestLimitsSeeder` (local) | Near-limit banners con datos reales |
| Regresion | `composer test` (151 tests) | Todo el sistema existente |

## 4. Criterios de entrada

- [x] Tenant model con isSuspended(), suspend()
- [x] 3 Notification classes creadas
- [x] CheckTrialExpiry command creado y scheduled
- [x] /suspended page y ruta creados
- [x] EnsureTenant middleware con suspension redirect
- [x] CurrentPlanWidget creado
- [x] NotifiesNearLimit trait en 4 CreateRecord pages
- [x] canAccessPanel actualizado

## 5. Criterios de salida

- [x] 26/26 tests automatizados nuevos pasan
- [x] 151/151 tests totales pasan (0 regresiones)
- [x] Checklist manual: /suspended page con planes
- [x] Checklist manual: Dashboard widgets (Pro vs Starter trial)
- [x] Checklist manual: Banners de limites con datos reales
- [x] Checklist manual: Trial banner danger con 1 dia restante
- [x] 0 bugs criticos abiertos

## 6. Datos de test

### Tenants

| Tenant | Plan | subscribed_at | trial_ends_at | is_active | Descripcion |
|--------|------|---------------|---------------|-----------|-------------|
| SaaS Template | pro | now() | null | true | Suscrito, sin limites |
| Dtf merida | starter | null | +14 days | true | Trial, con TestLimitsSeeder al 85-90% |

### Escenarios de trial simulados (tinker)

| Escenario | trial_ends_at | Resultado esperado |
|-----------|---------------|--------------------|
| Trial normal (13d) | +14 days | Banner verde, "13 dias restantes" |
| Trial critico (1d) | +2 days | Banner rojo, "1 dia restantes" |
| Trial expirado | -1 day | Banner "ha terminado", grace period |
| Post-grace suspendido | -(grace+1) days | Redirect a /suspended |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Sticky CSS no funciona en Filament | Confirmado | Bajo | Removido; banner es sort=-2 (primero) |
| diffInDays floor rounding | Confirmado | Medio | Command usa whereDate + startOfDay |
| canAccessPanel 403 antes de redirect | Confirmado | Alto | Cambiado a permitir any tenant |
| Faker genera datos largos (state varchar(5)) | Confirmado | Bajo | Seeder usa valores fijos |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Test Plan | `docs/qa/fase5-trial-conversion/test-plan.md` (este file) |
| Test Cases | `docs/qa/fase5-trial-conversion/test-cases.md` |
| Test Summary | `docs/qa/fase5-trial-conversion/test-summary.md` |
