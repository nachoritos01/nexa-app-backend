# Test Cases — SaaS Fase 6 PR1: Core Retention (6.1-6.3)

**Version:** 1.0
**Date:** 2026-02-20
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-RT-{numero secuencial} (RT = Retention)
- **Priority:** P1 (critica), P2 (alta), P3 (media), P4 (baja)
- **Tipo:** Funcional, Negativo, Regresion, E2E, Security
- **Status:** Pendiente, Pasado, Fallido, Bloqueado

---

## Health Score — Calculation

### TC-RT-001: Health score calculado para tenants activos

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthScoreTest::test_health_score_calculated_for_active_tenants`) |
| **Precondiciones** | Tenant con is_active=true |
| **Steps** | 1. Ejecutar `saas:health-score` |
| **Resultado esperado** | health_score != null, health_score_calculated_at != null |
| **Estado** | Pasado |

---

### TC-RT-002: Health score 0 para tenant sin actividad

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-002 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthScoreTest::test_health_score_zero_for_inactive_tenant`) |
| **Precondiciones** | Tenant activo sin users, orders, ni features |
| **Steps** | 1. Ejecutar `saas:health-score` |
| **Resultado esperado** | health_score = 0 |
| **Estado** | Pasado |

---

### TC-RT-003: Health score alto para tenant con toda la actividad

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-003 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthScoreTest::test_health_score_high_for_active_tenant`) |
| **Precondiciones** | Tenant con login hoy, order hoy, 6/6 features, 1 user activo |
| **Steps** | 1. Ejecutar `saas:health-score` |
| **Resultado esperado** | health_score >= 90 |
| **Estado** | Pasado |

---

### TC-RT-004: Tenants suspendidos no se calculan

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-004 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`HealthScoreTest::test_suspended_tenants_not_calculated`) |
| **Precondiciones** | Tenant con is_active=false |
| **Steps** | 1. Ejecutar `saas:health-score` |
| **Resultado esperado** | health_score permanece null |
| **Estado** | Pasado |

---

### TC-RT-005: Login component 0 sin logins

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-005 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`HealthScoreTest::test_login_component_zero_without_logins`) |
| **Precondiciones** | Tenant activo con user sin last_login_at |
| **Steps** | 1. Ejecutar `saas:health-score` |
| **Resultado esperado** | health_score <= 30 (login 30% + user activity 20% son 0) |
| **Estado** | Pasado |

---

## Retention Alerts

### TC-RT-006: Inactivity reminder enviado despues de 14d sin orders

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RetentionAlertsTest::test_inactivity_reminder_sent_after_14_days_without_orders`) |
| **Precondiciones** | Tenant suscrito, ultimo order 20 dias atras |
| **Steps** | 1. Ejecutar `saas:retention-alerts` |
| **Resultado esperado** | InactivityReminderNotification enviado al owner |
| **Estado** | Pasado |

---

### TC-RT-007: Inactivity reminder NO enviado con orders recientes

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-007 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RetentionAlertsTest::test_inactivity_reminder_not_sent_with_recent_orders`) |
| **Precondiciones** | Tenant suscrito, ultimo order 3 dias atras |
| **Steps** | 1. Ejecutar `saas:retention-alerts` |
| **Resultado esperado** | Notificacion NO enviada |
| **Estado** | Pasado |

---

### TC-RT-008: Inactivity reminder NO enviado dos veces en 14 dias (dedup)

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RetentionAlertsTest::test_inactivity_reminder_not_sent_twice_in_14_days`) |
| **Precondiciones** | Tenant con settings.inactivity_reminder_sent_at = 5 dias atras |
| **Steps** | 1. Ejecutar `saas:retention-alerts` |
| **Resultado esperado** | Notificacion NO enviada (dedup activo) |
| **Estado** | Pasado |

---

### TC-RT-009: Low health alert enviado a super-admins

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-009 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RetentionAlertsTest::test_low_health_alert_sent_to_super_admins`) |
| **Precondiciones** | Tenant activo con health_score=30, super-admin existente |
| **Steps** | 1. Ejecutar `saas:retention-alerts` |
| **Resultado esperado** | LowHealthScoreAlert enviado al super-admin |
| **Estado** | Pasado |

---

### TC-RT-010: Low health alert NO enviado cuando todos saludables

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-010 |
| **Prioridad** | P2 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RetentionAlertsTest::test_low_health_alert_not_sent_when_all_healthy`) |
| **Precondiciones** | Tenant activo con health_score=80, super-admin existente |
| **Steps** | 1. Ejecutar `saas:retention-alerts` |
| **Resultado esperado** | Notificacion NO enviada |
| **Estado** | Pasado |

---

## Feature Usage Tracking

### TC-RT-011: track() crea registration con tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-011 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`FeatureUsageTest::test_track_creates_record_with_tenant`) |
| **Precondiciones** | Tenant bound en container, user autenticado |
| **Steps** | 1. Llamar FeatureUsage::track('order_created') |
| **Resultado esperado** | Registration en feature_usages con tenant_id, user_id, feature |
| **Estado** | Pasado |

---

### TC-RT-012: track() no-op sin tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-012 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`FeatureUsageTest::test_track_noop_without_tenant`) |
| **Precondiciones** | Sin tenant en container |
| **Steps** | 1. Llamar FeatureUsage::track('order_created') |
| **Resultado esperado** | 0 registrations en feature_usages |
| **Estado** | Pasado |

---

### TC-RT-013: Login event actualiza last_login_at

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-013 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`FeatureUsageTest::test_login_event_updates_last_login_at`) |
| **Precondiciones** | User con last_login_at=null |
| **Steps** | 1. Disparar evento Login |
| **Resultado esperado** | last_login_at != null |
| **Estado** | Pasado |

---

## Cancellation Survey

### TC-RT-014: Survey se almacena correctamente

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-014 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CancellationSurveyTest::test_survey_stored_correctly`) |
| **Precondiciones** | Tenant y user existentes |
| **Steps** | 1. Crear CancellationSurvey con reason y details |
| **Resultado esperado** | Registration en DB, relationships cargadas |
| **Estado** | Pasado |

---

### TC-RT-015: reasonLabels retorna 5 opciones

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-015 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CancellationSurveyTest::test_reason_labels_returns_five_options`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. Llamar CancellationSurvey::reasonLabels() |
| **Resultado esperado** | Array con 5 keys: price, missing_features, closed_business, competitor, other |
| **Estado** | Pasado |

---

## Super-Admin — UI (Manual)

### TC-RT-016: Health score column visible con badges de colors

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-016 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como super-admin, saas:health-score ejecutado |
| **Steps** | 1. Ir a /super-admin/tenants<br>2. Check column "Health" con valores numericos<br>3. Check colors: rojo (<50), amarillo (50-69), verde (70+), gris (null) |
| **Resultado esperado** | Badges coloreados visibles y correctos |
| **Estado** | Pasado |

---

### TC-RT-017: Health score filter funcional

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-017 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual) |
| **Precondiciones** | Login como super-admin, health scores calculados |
| **Steps** | 1. Ir a /super-admin/tenants<br>2. Abrir filtros<br>3. Seleccionar "Critico (<50)" en filtro Health |
| **Resultado esperado** | Solo tenants con score < 50 visibles |
| **Estado** | Pasado |

---

### TC-RT-018: HealthScoreDistribution widget doughnut

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-018 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como super-admin, health scores calculados |
| **Steps** | 1. Ir a /super-admin<br>2. Check widget "Health Score Distribution"<br>3. Check doughnut con 4 segmentos: Saludable, En riesgo, Critico, Sin datos |
| **Resultado esperado** | Chart visible con leyenda y datos correctos |
| **Estado** | Pasado |

---

### TC-RT-019: ChurnReasonsChart widget doughnut

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-019 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como super-admin |
| **Steps** | 1. Ir a /super-admin<br>2. Check widget "Cancellation Reasons"<br>3. Sin surveys: muestra "Sin datos" en gris |
| **Resultado esperado** | Chart visible con estado vacio correcto |
| **Estado** | Pasado |

---

### TC-RT-020: Exit survey modal muestra 5 razones

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-020 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como owner, tenant suscrito con stripe_id |
| **Steps** | 1. Ir a /admin/billing<br>2. Click "Cancelar suscripcion"<br>3. Check modal con titulo, 5 radios, textarea, botones |
| **Resultado esperado** | Modal visible con Precio, Faltan funcionalidades, Cerro el negocio, Cambio a otra plataforma, Otro |
| **Estado** | Pasado |

---

### TC-RT-021: Billing page muestra 2 botones separados

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-021 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como owner, tenant suscrito con stripe_id |
| **Steps** | 1. Ir a /admin/billing<br>2. Check seccion "Gestionar suscripcion" |
| **Resultado esperado** | Dos botones: "Gestionar payment" (gris) y "Cancelar suscripcion" (rojo) |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 21 |
| Pending | 0 |
| Fallido | 0 |
| Bloqueado | 0 |
| **Total** | **21** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 15 |
| Manual (UI/browser) | 6 |
