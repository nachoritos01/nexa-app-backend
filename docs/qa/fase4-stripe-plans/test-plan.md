# Test Plan — SaaS Fase 4: Stripe + Plans

**Version:** 1.0
**Date:** 2026-02-20
**Feature:** Suscripciones Stripe, Billing page, Plan limits
**Branch:** feature/saas-phase4-stripe-plans

---

## 1. Objetivo

Validar que el sistema de suscripciones funciona correctamente: billing page accesible solo por Owner, limites de plan enforzados en policies, banners de limites visibles, y webhooks de Stripe sincronizan el estado del tenant.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Billing page | Acceso por rol, plan actual, uso, comparacion de planes |
| Plan limits | Enforcement en policies (create), near/at limit detection |
| PlanLimitsBanner | Warnings en dashboard al 80%/100% de uso |
| TrialBanner | Boton "Suscribirse" visible para tenants en trial |
| Checkout flow | Redirect a Stripe Checkout (requiere Stripe keys) |
| Webhooks | Sync de plan y subscribed_at en subscription events |
| Tenant helpers | isSubscribed, planLabel, planLimits, usageCounts, isAtLimit, isNearLimit |
| Seeder | SaaS Template = Pro con subscribed_at |
| Factory | State subscribed() |

### Fuera de alcance

- Stripe Dashboard (configuracion de items/pricing) — manual, externo
- Stripe Customer Portal — depende de Stripe keys reales
- Dunning (reintentos de payment fallido) — gestionado por Stripe
- Landing page publica — Fase 5

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (`BillingTest.php`, `PlanLimitsTest.php`) | Acceso billing, limites, policies |
| Manuales funcionales | Browser + Chrome DevTools MCP | UI billing, banners, sidebar |
| Regresion | `composer test` (125 tests) | Todo el sistema existente |

## 4. Criterios de entrada

- [x] Laravel Cashier v16 instalado
- [x] Migrations ejecutadas (stripe columns en tenants, subscriptions)
- [x] config/saas.php con precios y limites
- [x] Tenant model con Billable trait y plan helpers
- [x] 4 Policies con isAtLimit check
- [x] Billing page y BillingController creados
- [x] StripeWebhookController creado
- [x] DefaultTenantSeeder con subscribed_at

## 5. Criterios de salida

- [x] 21/21 tests automatizados nuevos pasan
- [x] 125/125 tests totales pasan (0 regresiones)
- [x] Checklist manual completado para billing page
- [x] Checklist manual completado para plan limits (Pro vs Starter)
- [ ] 0 bugs criticos o bloqueantes abiertos

## 6. Datos de test

### Tenants

| Tenant | Plan | subscribed_at | trial_ends_at | Descripcion |
|--------|------|---------------|---------------|-------------|
| SaaS Template | pro | now() | null | Tenant principal, suscrito sin Stripe |
| (factory) starter | starter | null | +14 days | Tenant trial para probar limites |
| (factory) subscribed | growth | now() | null | Tenant suscrito para probar cambios |

### Limites por plan

| Recurso | Starter | Growth | Pro |
|---------|---------|--------|-----|
| Orders/mes | 50 | 200 | Ilimitados |
| Users | 2 | 5 | Ilimitados |
| Locationes | 1 | 3 | Ilimitadas |
| Items | 20 | 100 | Ilimitados |
| Customers | 200 | 1,000 | Ilimitados |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Stripe keys no configuradas | Alta (dev local) | Medio | Checkout muestra error controlado |
| Webhook sin secret | Alta (dev local) | Bajo | Stripe CLI para testing local |
| isSubscribed() false positive | Baja | Alto | Checa subscribed_at OR Cashier subscribed() |
| Race condition en usage counts | Baja | Medio | Queries count en tiempo real |
| Cashier migration conflicto con tenants | Baja | Alto | Migration custom (no users, omit trial_ends_at) |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Test Plan | `docs/qa/fase4-stripe-plans/test-plan.md` (este file) |
| Test Cases | `docs/qa/fase4-stripe-plans/test-cases.md` |
| Test Summary | `docs/qa/fase4-stripe-plans/test-summary.md` |
