# Test Cases — SaaS Fase 5 PR1: Trial Expiry + Conversion UI

**Version:** 1.0
**Date:** 2026-02-20
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-TRIAL-{numero secuencial}
- **Priority:** P1 (critica), P2 (alta), P3 (media), P4 (baja)
- **Tipo:** Funcional, Negativo, Regresion, E2E, Security
- **Status:** Pendiente, Pasado, Fallido, Bloqueado

---

## Tenant Model — isSuspended / suspend

### TC-TRIAL-001: isSuspended true cuando trial expirado + post-grace + no suscrito

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TrialExpiryTest::test_is_suspended_true_when_trial_expired_past_grace`) |
| **Precondiciones** | Tenant con trial_ends_at = -(grace+1) days, subscribed_at = null |
| **Steps** | 1. isSuspended() |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-TRIAL-002: isSuspended false durante grace period

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-002 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TrialExpiryTest::test_is_suspended_false_during_grace_period`) |
| **Precondiciones** | Tenant con trial_ends_at = -1 day (dentro de grace) |
| **Steps** | 1. isSuspended() |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-TRIAL-003: isSuspended false cuando suscrito

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-003 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TrialExpiryTest::test_is_suspended_false_when_subscribed`) |
| **Precondiciones** | Tenant con trial expirado PERO subscribed_at = now() |
| **Steps** | 1. isSuspended() |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-TRIAL-004: isSuspended false cuando en trial activo

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-004 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TrialExpiryTest::test_is_suspended_false_when_on_trial`) |
| **Precondiciones** | Tenant con trial_ends_at futuro |
| **Steps** | 1. isSuspended() |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-TRIAL-005: suspend() pone is_active = false

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-005 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TrialExpiryTest::test_suspend_sets_is_active_false`) |
| **Precondiciones** | Tenant con is_active = true |
| **Steps** | 1. suspend()<br>2. Check is_active en DB |
| **Resultado esperado** | is_active = false |
| **Estado** | Pasado |

---

## CheckTrialExpiry Command — Notificaciones

### TC-TRIAL-006: Command envia notificacion 3 dias antes de expiry

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TrialExpiryTest::test_command_sends_notification_3_days_before_expiry`) |
| **Precondiciones** | Tenant trial con trial_ends_at = +3 days |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | TrialExpiringNotification enviada al owner con daysRemaining=3 |
| **Estado** | Pasado |

---

### TC-TRIAL-007: Command envia notificacion 1 dia antes de expiry

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-007 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TrialExpiryTest::test_command_sends_notification_1_day_before_expiry`) |
| **Precondiciones** | Tenant trial con trial_ends_at = +1 day |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | TrialExpiringNotification enviada con daysRemaining=1 |
| **Estado** | Pasado |

---

### TC-TRIAL-008: Command NO envia notificacion a 2 dias (solo 1 y 3)

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-008 |
| **Prioridad** | P2 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TrialExpiryTest::test_command_does_not_send_at_2_days`) |
| **Precondiciones** | Tenant trial con trial_ends_at = +2 days |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | Ninguna notificacion enviada |
| **Estado** | Pasado |

---

### TC-TRIAL-009: Command envia notificacion el dia de expiracion

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-009 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TrialExpiryTest::test_command_sends_expired_notification_on_expiry_day`) |
| **Precondiciones** | Tenant con trial_ends_at = today |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | TrialExpiredNotification enviada al owner |
| **Estado** | Pasado |

---

## CheckTrialExpiry Command — Suspension

### TC-TRIAL-010: Command suspende tenant despues del grace period

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-010 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`TrialExpiryTest::test_command_suspends_tenant_after_grace_period`) |
| **Precondiciones** | Tenant con trial expirado + grace terminado, is_active = true |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | is_active = false, TenantSuspendedNotification enviada |
| **Estado** | Pasado |

---

### TC-TRIAL-011: Command NO afecta tenants suscritos

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-011 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TrialExpiryTest::test_command_does_not_affect_subscribed_tenants`) |
| **Precondiciones** | Tenant con trial expirado PERO subscribed_at != null |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | is_active sigue true, sin notificacion |
| **Estado** | Pasado |

---

### TC-TRIAL-012: Command NO afecta tenants ya suspendidos

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-012 |
| **Prioridad** | P2 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`TrialExpiryTest::test_command_does_not_affect_already_suspended_tenants`) |
| **Precondiciones** | Tenant con is_active = false (ya suspendido) |
| **Steps** | 1. artisan saas:check-trial-expiry |
| **Resultado esperado** | Sin notificacion duplicada |
| **Estado** | Pasado |

---

## /suspended Page

### TC-TRIAL-013: Pagina accesible por user autenticado

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-013 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_suspended_page_accessible_by_authenticated_user`) |
| **Precondiciones** | User autenticado |
| **Steps** | 1. GET /suspended |
| **Resultado esperado** | HTTP 200 |
| **Estado** | Pasado |

---

### TC-TRIAL-014: Pagina muestra 3 planes

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-014 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_suspended_page_shows_plans`) |
| **Precondiciones** | User autenticado |
| **Steps** | 1. GET /suspended<br>2. Check textos "Starter", "Growth", "Pro" |
| **Resultado esperado** | 3 planes visibles |
| **Estado** | Pasado |

---

### TC-TRIAL-015: Pagina requiere autenticacion

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-015 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ConversionUiTest::test_suspended_page_requires_authentication`) |
| **Precondiciones** | Sin sesion |
| **Steps** | 1. GET /suspended sin autenticar |
| **Resultado esperado** | Redirect a login |
| **Estado** | Pasado |

---

### TC-TRIAL-016: Pagina muestra precios, limites y CTAs correctos (manual)

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-016 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Navegar a /suspended |
| **Steps** | 1. Check 3 cards<br>2. Check precios: $299, $699, $1,299<br>3. Check limites por plan<br>4. Check badge "Popular" en Growth<br>5. Check CTAs linkean a /billing/checkout<br>6. Check link "Contactar soporte"<br>7. Check boton "Cerrar sesion" |
| **Resultado esperado** | Todo visible y correcto |
| **Estado** | Pasado |

---

## EnsureTenant Middleware — Suspension Redirect

### TC-TRIAL-017: Middleware redirige tenant suspendido a /suspended

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-017 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_middleware_redirects_suspended_tenant_to_suspended_page`) |
| **Precondiciones** | Tenant con isSuspended() = true |
| **Steps** | 1. GET /admin |
| **Resultado esperado** | Redirect 302 a /suspended |
| **Estado** | Pasado |

---

### TC-TRIAL-018: Middleware permite /billing cuando suspendido

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-018 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_middleware_allows_billing_when_suspended`) |
| **Precondiciones** | Tenant suspendido |
| **Steps** | 1. GET /billing/checkout?plan=starter&period=monthly |
| **Resultado esperado** | NO redirect a /suspended (puede ir a Stripe o error de price ID) |
| **Estado** | Pasado |

---

### TC-TRIAL-019: Middleware permite /suspended cuando suspendido

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-019 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_middleware_allows_suspended_page_when_suspended`) |
| **Precondiciones** | Tenant suspendido |
| **Steps** | 1. GET /suspended |
| **Resultado esperado** | HTTP 200 (sin loop de redirect) |
| **Estado** | Pasado |

---

### TC-TRIAL-020: Middleware NO redirige tenant con trial activo

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-020 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`ConversionUiTest::test_middleware_does_not_redirect_active_tenant`) |
| **Precondiciones** | Tenant con trial_ends_at = +10 days |
| **Steps** | 1. GET /admin |
| **Resultado esperado** | HTTP 200 (dashboard normal) |
| **Estado** | Pasado |

---

### TC-TRIAL-021: Middleware NO redirige tenant suscrito

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-021 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`ConversionUiTest::test_middleware_does_not_redirect_subscribed_tenant`) |
| **Precondiciones** | Tenant con subscribed_at = now() |
| **Steps** | 1. GET /admin |
| **Resultado esperado** | HTTP 200 |
| **Estado** | Pasado |

---

### TC-TRIAL-022: Middleware redirige user con solo tenants suspendidos (is_active=false)

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-022 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_middleware_redirects_user_with_only_suspended_tenants`) |
| **Precondiciones** | Tenant con is_active=false, trial expirado post-grace |
| **Steps** | 1. GET /admin |
| **Resultado esperado** | Redirect a /suspended |
| **Estado** | Pasado |

---

## CurrentPlanWidget

### TC-TRIAL-023: Widget visible para tenant en trial

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-023 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_current_plan_widget_visible_for_trial_tenant`) |
| **Precondiciones** | Tenant en trial |
| **Steps** | 1. CurrentPlanWidget::canView() |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-TRIAL-024: Widget visible para tenant suscrito

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-024 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_current_plan_widget_visible_for_subscribed_tenant`) |
| **Precondiciones** | Tenant suscrito |
| **Steps** | 1. CurrentPlanWidget::canView() |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-TRIAL-025: Widget muestra Upgrade para Starter

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-025 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_current_plan_widget_shows_upgrade_for_starter`) |
| **Precondiciones** | Tenant plan=starter |
| **Steps** | 1. getPlanData()['show_upgrade'] |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-TRIAL-026: Widget muestra Upgrade para Growth

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-026 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ConversionUiTest::test_current_plan_widget_shows_upgrade_for_growth`) |
| **Precondiciones** | Tenant plan=growth |
| **Steps** | 1. getPlanData()['show_upgrade'] |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-TRIAL-027: Widget NO muestra Upgrade para Pro

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-027 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`ConversionUiTest::test_current_plan_widget_hides_upgrade_for_pro`) |
| **Precondiciones** | Tenant plan=pro |
| **Steps** | 1. getPlanData()['show_upgrade'] |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

## UI Manual — Dashboard (Chrome DevTools MCP)

### TC-TRIAL-028: Dashboard Pro suscrito — sin trial/limits banners, widget "Plan maximo"

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-028 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual) |
| **Precondiciones** | Login como owner de SaaS Template (Pro, suscrito) |
| **Steps** | 1. Ir a /admin<br>2. Check widget "Tu Plan" → "Pro, Suscripcion activa, Plan maximo activo"<br>3. Check NO boton Upgrade<br>4. Check NO trial banner<br>5. Check NO plan limits banner |
| **Resultado esperado** | Widget correcto, sin banners de trial/limites |
| **Estado** | Pasado |

---

### TC-TRIAL-029: Dashboard Starter trial — banners de limites + widget Upgrade

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-029 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, con TestLimitsSeeder) |
| **Precondiciones** | Login como Joaquin Lopez (Starter, trial, datos al 85-90%) |
| **Steps** | 1. Ir a /admin<br>2. Check trial banner con dias restantes<br>3. Check PlanLimitsBanner con warnings para orders, items, customers<br>4. Check PlanLimitsBanner con dangers para users, locationes<br>5. Check widget "Tu Plan" → "Starter" + badge trial + boton Upgrade |
| **Resultado esperado** | Todos los banners y widget visibles con datos correctos |
| **Estado** | Pasado |

---

### TC-TRIAL-030: Dashboard con 1 dia restante — banner danger rojo

| Campo | Valor |
|-------|-------|
| **ID** | TC-TRIAL-030 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, via tinker) |
| **Precondiciones** | Cambiar trial_ends_at a +2 days via tinker |
| **Steps** | 1. Ir a /admin<br>2. Check trial banner rojo "1 dia restantes"<br>3. Check widget "Test: 1 dia restantes" |
| **Resultado esperado** | Banner danger (rojo), widget actualizado |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 30 |
| Pending | 0 |
| Fallido | 0 |
| Bloqueado | 0 |
| **Total** | **30** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 26 |
| Manual (UI/browser) | 4 |
