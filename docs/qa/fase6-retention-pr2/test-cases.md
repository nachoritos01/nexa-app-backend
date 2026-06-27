# Test Cases — SaaS Fase 6 PR2: Win-back, Referidos, Messaging Auto (6.4-6.6)

**Version:** 1.0
**Date:** 2026-02-22
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-RT-{numero secuencial} (RT = Retention, continua desde PR1: TC-RT-022+)
- **Priority:** P1 (critica), P2 (alta), P3 (media), P4 (baja)
- **Tipo:** Funcional, Negativo, Regresion, E2E, Security
- **Status:** Pendiente, Pasado, Fallido, Bloqueado

---

## Win-back Emails (6.4)

### TC-RT-022: Win-back email enviado 7d post-cancelacion

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-022 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`WinbackEmailTest::test_winback_email_sent_after_7_days`) |
| **Precondiciones** | CancellationSurvey creada hace 7 dias, tenant sin suscripcion activa |
| **Steps** | 1. Ejecutar `saas:winback-emails` |
| **Resultado esperado** | WinbackNotification enviado al owner del tenant |
| **Estado** | Pasado |

---

### TC-RT-023: Win-back email NO enviado antes de 7d

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-023 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`WinbackEmailTest::test_winback_email_not_sent_before_7_days`) |
| **Precondiciones** | CancellationSurvey creada hace 3 dias |
| **Steps** | 1. Ejecutar `saas:winback-emails` |
| **Resultado esperado** | Notificacion NO enviada |
| **Estado** | Pasado |

---

### TC-RT-024: Win-back email NO enviado si tenant se re-suscribio

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-024 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`WinbackEmailTest::test_winback_email_not_sent_if_resubscribed`) |
| **Precondiciones** | CancellationSurvey hace 7d, tenant con subscribed_at != null |
| **Steps** | 1. Ejecutar `saas:winback-emails` |
| **Resultado esperado** | Notificacion NO enviada (tenant re-suscrito) |
| **Estado** | Pasado |

---

### TC-RT-025: Win-back email NO enviado dos veces (dedup)

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-025 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`WinbackEmailTest::test_winback_email_not_sent_twice`) |
| **Precondiciones** | CancellationSurvey hace 7d, tenant.settings.winback_email_sent_at ya existe |
| **Steps** | 1. Ejecutar `saas:winback-emails` |
| **Resultado esperado** | Notificacion NO enviada (dedup via settings) |
| **Estado** | Pasado |

---

## Referral Program (6.5)

### TC-RT-026: Referral code auto-generado al crear tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-026 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ReferralProgramTest::test_referral_code_auto_generated_on_tenant_creation`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. Crear tenant via Tenant::create() |
| **Resultado esperado** | referral_code != null, length = 8 chars |
| **Estado** | Pasado |

---

### TC-RT-027: Registration con ?ref= crea Referral record

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-027 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ReferralProgramTest::test_registration_with_ref_creates_referral`) |
| **Precondiciones** | Tenant referrer existente con referral_code |
| **Steps** | 1. POST /admin/register con ?ref=CODE valido |
| **Resultado esperado** | Referral record con referrer_tenant_id y referred_tenant_id correctos |
| **Estado** | Pasado |

---

### TC-RT-028: Registration con ?ref= invalido NO crea Referral

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-028 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`ReferralProgramTest::test_registration_with_invalid_ref_no_referral`) |
| **Precondiciones** | Codigo de referido inexistente |
| **Steps** | 1. POST /admin/register con ?ref=INVALID |
| **Resultado esperado** | 0 registrations en referrals table |
| **Estado** | Pasado |

---

### TC-RT-029: Conversion marca converted_at en Referral

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-029 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ReferralProgramTest::test_referral_conversion_marks_converted_at`) |
| **Precondiciones** | Referral record pendiente (converted_at = null) |
| **Steps** | 1. Llamar processReferralReward() via webhook |
| **Resultado esperado** | converted_at != null |
| **Estado** | Pasado |

---

## Messaging Auto-notify (6.6)

### TC-RT-030: Observer dispara cuando enabled + status en whitelist

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-030 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`MessagingAutoNotifyTest::test_observer_fires_when_enabled_and_status_change`) |
| **Precondiciones** | Tenant con messaging_auto_enabled=true, status en whitelist, order con phone |
| **Steps** | 1. Cambiar order->status a 'confirmed' y guardar |
| **Resultado esperado** | Log contiene 'Messaging auto' |
| **Estado** | Pasado |

---

### TC-RT-031: Observer NO dispara cuando WA auto disabled

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-031 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`MessagingAutoNotifyTest::test_observer_does_not_fire_when_disabled`) |
| **Precondiciones** | Tenant con messaging_auto_enabled=false |
| **Steps** | 1. Cambiar order->status y guardar |
| **Resultado esperado** | Log NO contiene 'Messaging auto' |
| **Estado** | Pasado |

---

### TC-RT-032: Observer respeta whitelist de transiciones

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-032 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`MessagingAutoNotifyTest::test_observer_respects_transition_whitelist`) |
| **Precondiciones** | Tenant con WA auto enabled, whitelist = ['delivered'] |
| **Steps** | 1. Cambiar order->status a 'confirmed' (no en whitelist) y guardar |
| **Resultado esperado** | Log NO contiene 'Messaging auto' |
| **Estado** | Pasado |

---

### TC-RT-033: Observer NO dispara sin customer_phone

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-033 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`MessagingAutoNotifyTest::test_observer_does_not_fire_without_phone`) |
| **Precondiciones** | Tenant con WA auto enabled, order sin customer_phone |
| **Steps** | 1. Cambiar order->status y guardar |
| **Resultado esperado** | Log NO contiene 'Messaging auto' |
| **Estado** | Pasado |

---

## UI — Messaging Settings (Manual)

### TC-RT-034: Messaging Settings page con toggle y checkboxes

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-034 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin con permiso settings.manage |
| **Steps** | 1. Ir a /admin/messaging-settings<br>2. Check toggle "Habilitar notificaciones automaticas"<br>3. Check 4 checkboxes: Confirmado, En Operations, Listo, Entregado<br>4. Activar toggle y 2 checkboxes<br>5. Click "Guardar configuracion" |
| **Resultado esperado** | Mensaje "Configuracion guardada", settings persistidos en DB |
| **Estado** | Pasado |

---

### TC-RT-035: Messaging toggle estado visual correcto

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-035 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual) |
| **Precondiciones** | Login como admin |
| **Steps** | 1. Ir a /admin/messaging-settings<br>2. Toggle off → fondo gris<br>3. Toggle on → fondo naranja (primary)<br>4. Checkboxes deshabilitados cuando toggle off |
| **Resultado esperado** | Toggle visualmente distinguible en ambos estados, checkboxes con opacity-50 cuando disabled |
| **Estado** | Pasado |

---

## UI — Billing Referral Section (Manual)

### TC-RT-036: Seccion referidos visible con link y stats

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-036 |
| **Prioridad** | P1 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin |
| **Steps** | 1. Ir a /admin/billing<br>2. Check seccion "Programa de Referidos"<br>3. Check input con URL de referido<br>4. Check boton "Copiar"<br>5. Check codigo de referido<br>6. Check cards Referidos y Convertidos |
| **Resultado esperado** | URL contiene ?ref=CODE, boton copia al clipboard, stats muestran numeros correctos |
| **Estado** | Pasado |

---

### TC-RT-037: Referral stats se actualizan despues de registration con ?ref=

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-037 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Tenant con referral_code conocido |
| **Steps** | 1. Abrir /admin/register?ref=CODE en otra sesion<br>2. Registrar nuevo negocio<br>3. Check en DB que Referral record existe<br>4. Volver a /admin/billing como referrer<br>5. Check que "Referidos" incremento a 1 |
| **Resultado esperado** | Counter actualizado correctamente |
| **Estado** | Pasado |

---

### TC-RT-038: Dark mode cards de referidos visibles

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-038 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual) |
| **Precondiciones** | Dark mode activado en Filament |
| **Steps** | 1. Ir a /admin/billing<br>2. Check seccion referidos<br>3. Cards "Referidos" y "Convertidos" con fondo dark:bg-gray-900 |
| **Resultado esperado** | Cards visibles con contraste adecuado, numeros blancos sobre fondo oscuro |
| **Estado** | Pasado |

---

## UI — Super-Admin Widget (Manual)

### TC-RT-039: ReferralStats widget en super-admin dashboard

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-039 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual) |
| **Precondiciones** | Login como super-admin |
| **Steps** | 1. Ir a /super-admin<br>2. Check widget con 3 stats: Total Referidos, Conversiones, Recompensas |
| **Resultado esperado** | Stats visibles con valores correctos |
| **Estado** | Pasado |

---

## Command — Win-back (Manual)

### TC-RT-040: saas:winback-emails ejecuta sin errores

| Campo | Valor |
|-------|-------|
| **ID** | TC-RT-040 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No (manual, terminal) |
| **Precondiciones** | DB con migrations aplicadas |
| **Steps** | 1. Ejecutar `php artisan saas:winback-emails` |
| **Resultado esperado** | Output "Win-back emails sent: 0" (sin surveys recientes), exit code 0 |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 19 |
| Pending | 0 |
| Fallido | 0 |
| Bloqueado | 0 |
| **Total** | **19** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 12 |
| Manual (UI/browser) | 7 |
