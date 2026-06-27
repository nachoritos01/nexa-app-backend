# Test Cases — SaaS Fase 4: Stripe + Plans

**Version:** 1.0
**Date:** 2026-02-20
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-BILL-{numero secuencial}
- **Priority:** P1 (critica), P2 (alta), P3 (media), P4 (baja)
- **Tipo:** Funcional, Negativo, Regresion, E2E, Security
- **Status:** Pendiente, Pasado, Fallido, Bloqueado

---

## Billing Page — Acceso por rol

### TC-BILL-001: Owner puede acceder a billing page

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`BillingTest::test_owner_can_access_billing_page`) |
| **Precondiciones** | User con rol owner en tenant activo |
| **Steps** | 1. GET /admin/billing como owner |
| **Resultado esperado** | HTTP 200 |
| **Estado** | Pasado |

---

### TC-BILL-002: Admin NO puede acceder a billing page

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-002 |
| **Prioridad** | P1 |
| **Tipo** | Negativo / Security |
| **Automatizado** | Si (`BillingTest::test_admin_cannot_access_billing_page`) |
| **Precondiciones** | User con rol admin en tenant |
| **Steps** | 1. GET /admin/billing como admin |
| **Resultado esperado** | HTTP 403 |
| **Estado** | Pasado |

---

### TC-BILL-003: Sales NO puede acceder a billing page

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-003 |
| **Prioridad** | P1 |
| **Tipo** | Negativo / Security |
| **Automatizado** | Si (`BillingTest::test_sales_cannot_access_billing_page`) |
| **Precondiciones** | User con rol sales en tenant |
| **Steps** | 1. GET /admin/billing como sales |
| **Resultado esperado** | HTTP 403 |
| **Estado** | Pasado |

---

## Billing Page — Contenido

### TC-BILL-004: Billing page muestra plan actual

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`BillingTest::test_billing_page_shows_current_plan`) |
| **Precondiciones** | Owner logueado, tenant con plan asignado |
| **Steps** | 1. GET /admin/billing<br>2. Check texto "Plan {label}" |
| **Resultado esperado** | Plan label visible en la pagina |
| **Estado** | Pasado |

---

### TC-BILL-005: Billing page muestra comparacion de 3 planes

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-005 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`BillingTest::test_billing_page_shows_plan_comparison`) |
| **Precondiciones** | Owner logueado |
| **Steps** | 1. GET /admin/billing<br>2. Check textos "Starter", "Growth", "Pro" |
| **Resultado esperado** | 3 planes visibles con precios |
| **Estado** | Pasado |

---

### TC-BILL-006: Billing page muestra status "Activa" para tenant suscrito

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-006 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner de SaaS Template (Pro, suscrito) |
| **Steps** | 1. Ir a /admin/billing<br>2. Check badge status |
| **Resultado esperado** | Badge "Activa" verde, texto "Suscripcion activa" |
| **Estado** | Pasado |

---

### TC-BILL-007: Billing page muestra uso con infinito para Pro

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-007 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner de SaaS Template (Pro) |
| **Steps** | 1. Ir a /admin/billing<br>2. Check seccion "Uso actual" |
| **Resultado esperado** | Todos los recursos muestran "X / ∞" |
| **Estado** | Pasado |

---

### TC-BILL-008: Plan actual muestra boton deshabilitado

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-008 |
| **Prioridad** | P3 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner, plan Pro |
| **Steps** | 1. Ir a /admin/billing<br>2. Check card de Pro |
| **Resultado esperado** | Boton "Plan actual" disabled, otros planes muestran "Cambiar plan" |
| **Estado** | Pasado |

---

### TC-BILL-009: Precios correctos en comparacion

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-009 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner |
| **Steps** | 1. Ir a /admin/billing<br>2. Check precios de cada plan |
| **Resultado esperado** | Starter: $299/mes ($239 anual), Growth: $699/mes ($559 anual), Pro: $1,299/mes ($1,039 anual) |
| **Estado** | Pasado |

---

## Plan Limits — Tenant helpers

### TC-BILL-010: Starter at limit cuando orders alcanzan max

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-010 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_starter_is_at_limit_when_orders_reach_max`) |
| **Precondiciones** | Tenant starter con 50 orders este mes |
| **Steps** | 1. isAtLimit('orders') |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-BILL-011: Starter NOT at limit debajo del max

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-011 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_starter_is_not_at_limit_below_max`) |
| **Precondiciones** | Tenant starter con 5 orders |
| **Steps** | 1. isAtLimit('orders') |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-BILL-012: Pro nunca at limit

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-012 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_pro_never_at_limit`) |
| **Precondiciones** | Tenant Pro con 100 orders |
| **Steps** | 1. isAtLimit('orders', 'products', 'branches', 'customers') |
| **Resultado esperado** | false para todos |
| **Estado** | Pasado |

---

### TC-BILL-013: Near limit al 80%

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-013 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_near_limit_at_80_percent`) |
| **Precondiciones** | Tenant starter con 80% de orders |
| **Steps** | 1. isNearLimit('orders') |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-BILL-014: NOT near limit debajo del 80%

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-014 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_not_near_limit_below_80_percent`) |
| **Precondiciones** | Tenant starter con 50% de orders |
| **Steps** | 1. isNearLimit('orders') |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-BILL-015: Pro nunca near limit

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-015 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_pro_never_near_limit`) |
| **Precondiciones** | Tenant Pro con 100 orders |
| **Steps** | 1. isNearLimit('orders') |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-BILL-016: Usage percentage correcto

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-016 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_usage_percentage_returns_correct_value`) |
| **Precondiciones** | Tenant starter con 10 products (max 20) |
| **Steps** | 1. usagePercentage('products') |
| **Resultado esperado** | 50 |
| **Estado** | Pasado |

---

### TC-BILL-017: Usage percentage null para unlimited

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-017 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_usage_percentage_null_for_unlimited`) |
| **Precondiciones** | Tenant Pro |
| **Steps** | 1. usagePercentage('orders') |
| **Resultado esperado** | null |
| **Estado** | Pasado |

---

### TC-BILL-018: Orders count solo mes actual

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-018 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_usage_counts_orders_only_current_month`) |
| **Precondiciones** | 3 orders este mes + 2 orders mes pasado |
| **Steps** | 1. usageCounts()['orders'] |
| **Resultado esperado** | 3 (no 5) |
| **Estado** | Pasado |

---

## Plan Limits — Policy enforcement

### TC-BILL-019: Policy bloquea crear order al limite

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-019 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`PlanLimitsTest::test_policy_blocks_order_creation_at_limit`) |
| **Precondiciones** | Tenant starter con 50 orders (max) |
| **Steps** | 1. user->can('create', Order::class) |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-BILL-020: Policy permite crear order debajo del limite

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-020 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_policy_allows_order_creation_below_limit`) |
| **Precondiciones** | Tenant starter con 5 orders |
| **Steps** | 1. user->can('create', Order::class) |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-BILL-021: Policy permite crear order en Pro (ilimitado)

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-021 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_policy_allows_order_creation_on_pro`) |
| **Precondiciones** | Tenant Pro con 100 orders |
| **Steps** | 1. user->can('create', Order::class) |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

## Subscription helpers

### TC-BILL-022: isSubscribed con subscribed_at

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-022 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_is_subscribed_with_subscribed_at`) |
| **Precondiciones** | Tenant con subscribed_at = now() |
| **Steps** | 1. isSubscribed() |
| **Resultado esperado** | true |
| **Estado** | Pasado |

---

### TC-BILL-023: NOT isSubscribed sin subscribed_at

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-023 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`PlanLimitsTest::test_is_not_subscribed_without_subscribed_at`) |
| **Precondiciones** | Tenant con subscribed_at = null |
| **Steps** | 1. isSubscribed() |
| **Resultado esperado** | false |
| **Estado** | Pasado |

---

### TC-BILL-024: planLabel retorna label de config

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-024 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_plan_label_returns_config_label`) |
| **Precondiciones** | Tenant con distintos planes |
| **Steps** | 1. planLabel() para starter, growth, pro |
| **Resultado esperado** | "Starter", "Growth", "Pro" |
| **Estado** | Pasado |

---

### TC-BILL-025: Factory state subscribed()

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-025 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PlanLimitsTest::test_subscribed_factory_state`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. Tenant::factory()->subscribed('pro')->create() |
| **Resultado esperado** | plan = pro, subscribed_at != null, isSubscribed() = true |
| **Estado** | Pasado |

---

## UI — Dashboard banners

### TC-BILL-026: No trial/limits banners para Pro suscrito

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-026 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner de SaaS Template (Pro, suscrito) |
| **Steps** | 1. Ir a /admin (dashboard)<br>2. Check ausencia de banners |
| **Resultado esperado** | Sin trial banner, sin plan limits banner |
| **Estado** | Pasado |

---

### TC-BILL-027: Sidebar muestra link "Plan" para Owner

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-027 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner |
| **Steps** | 1. Check sidebar bajo "Configuracion" |
| **Resultado esperado** | Link "Plan" visible |
| **Estado** | Pasado |

---

### TC-BILL-028: TrialBanner muestra boton "Suscribirse"

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-028 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Tenant en trial (trial_ends_at futuro, subscribed_at null) |
| **Steps** | 1. Login<br>2. Check trial banner en dashboard |
| **Resultado esperado** | Banner con dias restantes + boton "Suscribirse" que linkea a /admin/billing |
| **Estado** | Pending |

---

## Checkout flow (requiere Stripe keys)

### TC-BILL-029: Checkout redirect sin Stripe keys muestra error controlado

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-029 |
| **Prioridad** | P3 |
| **Tipo** | Negativo |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Sin STRIPE_KEY/SECRET configurados |
| **Steps** | 1. Click "Suscribirse" en billing page |
| **Resultado esperado** | Redirect a billing con mensaje de error (no crash) |
| **Estado** | Pending |

---

### TC-BILL-030: Checkout redirect con Stripe keys funciona

| Campo | Valor |
|-------|-------|
| **ID** | TC-BILL-030 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, requiere Stripe test mode) |
| **Precondiciones** | Stripe keys configuradas en .env, price IDs creados |
| **Steps** | 1. Click "Suscribirse" en Starter<br>2. Completar Stripe Checkout con tarjeta test 4242...<br>3. Check redirect a /admin/billing?checkout=success |
| **Resultado esperado** | Suscripcion creada, plan actualizado |
| **Estado** | Pending |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 27 |
| Pending | 3 |
| Fallido | 0 |
| Bloqueado | 0 |
| **Total** | **30** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 21 |
| Manual (UI/browser) | 6 |
| E2E (Stripe required) | 3 |
