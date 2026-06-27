# Test Plan — SaaS Fase 6 PR2: Win-back, Referidos, Messaging Auto (6.4-6.6)

**Version:** 1.0
**Date:** 2026-02-22
**Feature:** Win-back Emails, Referral Program, Messaging Auto-notify
**Branch:** feature/saas-phase6-retention-pr2

---

## 1. Objetivo

Validar que el sistema de retencion post-cancelacion funciona correctamente: win-back emails enviados 7 dias despues de cancelar, programa de referidos con captura en registration y recompensa Stripe, y notificaciones Messaging automaticas al cambiar status de order.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Win-back Emails | Command `saas:winback-emails` envia 7d post-cancelacion, dedup, skip re-suscrito |
| Referral Program | referral_code auto-generado, captura `?ref=` en registration, Referral model |
| Referral Reward | Webhook Stripe marca conversion, aplica cupon a ambos tenants |
| Referral UI | Seccion en billing con link copiable y stats (referidos/convertidos) |
| Messaging Auto | OrderObserver detecta status change, envia SMS o log wa.me link |
| Messaging Settings | Pagina Filament con toggle y checkboxes de transiciones |
| Super-Admin Widget | ReferralStats con total, conversiones, recompensas |

### Fuera de alcance

- Stripe real E2E — coupons y webhooks requieren Stripe API key real
- Twilio real — sin credenciales, Messaging auto solo genera wa.me links en logs
- Email delivery real — solo se verifica con Notification::fake()
- Deploy a Railway — verificacion local unicamente

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (3 files, 12 tests) | Win-back, referidos, Messaging auto |
| Manuales funcionales | Browser + Chrome DevTools MCP | Billing referral UI, Messaging settings, registration con ?ref= |
| Regresion | `composer test` (198 tests) | Todo el sistema existente |

## 4. Criterios de entrada

- [x] Config saas.php con secciones winback, referral, messaging_auto
- [x] WinbackNotification creada
- [x] SendWinbackEmails command creado y scheduled
- [x] Migration create_referrals_table ejecutada
- [x] Migration add_referral_code_to_tenants ejecutada
- [x] Referral model creado con relationships
- [x] Tenant model con referral_code, boot event, relationships
- [x] Register page captura ?ref= param
- [x] StripeWebhookController con processReferralReward()
- [x] Billing page con seccion referidos
- [x] OrderObserver registrado en AppServiceProvider
- [x] MessagingSettings page funcional
- [x] ReferralStats widget en super-admin

## 5. Criterios de salida

- [x] 12/12 tests automatizados nuevos pasan
- [x] 198/198 tests totales pasan (0 regresiones)
- [x] Checklist manual: Messaging Settings toggle y checkboxes funcionales
- [x] Checklist manual: Billing referral section con link y stats
- [x] Checklist manual: Registration con ?ref= crea Referral record en DB
- [x] Checklist manual: saas:winback-emails ejecuta sin errores
- [x] Checklist manual: Dark mode cards correctas
- [x] 0 bugs criticos abiertos

## 6. Datos de test

### Win-back Scenarios

| Escenario | Resultado esperado |
|-----------|-------------------|
| Survey creada hace 7 dias, tenant sin suscripcion | Email enviado |
| Survey creada hace 3 dias | No enviado (muy pronto) |
| Survey creada hace 7 dias, tenant re-suscrito | No enviado |
| Survey creada hace 7 dias, ya enviado antes | No enviado (dedup) |

### Referral Scenarios

| Escenario | Resultado esperado |
|-----------|-------------------|
| Nuevo tenant creado | referral_code auto-generado (8 chars) |
| Registration con ?ref=VALID_CODE | Referral record creado |
| Registration con ?ref=INVALID | No se crea Referral |
| Webhook subscription.created con Referral pendiente | converted_at marcado |

### Messaging Auto Scenarios

| Escenario | Resultado esperado |
|-----------|-------------------|
| Status change con WA auto enabled + status en whitelist | Log wa.me link |
| Status change con WA auto disabled | No log |
| Status change con status NO en whitelist | No log |
| Status change sin customer_phone | No log |

### Default Messaging Transitions

| Status | Value |
|--------|-------|
| Confirmado | confirmado |
| En Operations | en_operations |
| Listo | listo |
| Entregado | entregado |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| created_at no mass-assignable en tests | Confirmado | Medio | Corregido: usar property assignment + saveQuietly() |
| customer_phone NOT NULL en DB | Confirmado | Bajo | Corregido: usar '' (empty string) en tests, guard clause if (!$phone) |
| Dark mode stat cards no visibles | Confirmado | Bajo | Corregido: dark:bg-gray-900 en lugar de dark:bg-gray-700/50 |
| Stripe coupon no configurado | Bajo | Bajo | Reward se salta gracefully con try/catch |
| Twilio no configurado | Bajo | Bajo | Fallback a wa.me link en logs |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Test Plan | `docs/qa/fase6-retention-pr2/test-plan.md` (este file) |
| Test Cases | `docs/qa/fase6-retention-pr2/test-cases.md` |
| Test Summary | `docs/qa/fase6-retention-pr2/test-summary.md` |
