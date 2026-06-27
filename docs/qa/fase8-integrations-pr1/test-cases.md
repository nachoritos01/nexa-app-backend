# Test Cases — SaaS Fase 8 PR1: API Publica + Webhooks (8.1-8.2)

**Version:** 1.0
**Date:** 2026-02-22
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-INT-{numero} (INT = Integrations)
- **Priority:** P1 / P2 / P3 / P4
- **Tipo:** Funcional / Negativo / Regresion / E2E / Security
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 8.1 — API Publica (Sanctum + Endpoints)

### TC-INT-001: Token se genera con tenant_id correcto

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ApiTokenTest::test_token_is_generated_with_correct_tenant_id`) |
| **Precondiciones** | User con tenant plan Pro |
| **Steps** | 1. createToken('test')<br>2. Update token con tenant_id<br>3. Check token->tenant_id == tenant->id |
| **Resultado esperado** | Token asociado al tenant correcto |
| **Estado** | Pasado |

### TC-INT-002: Token revocado no autentica

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-002 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ApiTokenTest::test_revoked_token_does_not_authenticate`) |
| **Precondiciones** | Token creado y revocado |
| **Steps** | 1. Crear token<br>2. Revocar (delete)<br>3. GET /api/v1/orders con token revocado |
| **Resultado esperado** | 401 Unauthorized |
| **Estado** | Pasado |

### TC-INT-003: Plan no-Pro recibe 403

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-003 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`ApiTokenTest::test_non_pro_plan_receives_403`) |
| **Precondiciones** | Tenant plan Starter |
| **Steps** | 1. Crear token para tenant starter<br>2. GET /api/v1/orders |
| **Resultado esperado** | 403 con code PLAN_UPGRADE_REQUIRED |
| **Estado** | Pasado |

### TC-INT-004: Tenant inactivo recibe 403

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-004 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ApiTokenTest::test_inactive_tenant_receives_403`) |
| **Precondiciones** | Tenant con status suspended |
| **Steps** | 1. Crear token<br>2. Suspender tenant<br>3. GET /api/v1/orders |
| **Resultado esperado** | 403 con code TENANT_INACTIVE |
| **Estado** | Pasado |

### TC-INT-005: Request sin token retorna 401

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-005 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ApiTokenTest::test_unauthenticated_request_returns_401`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /api/v1/orders sin header Authorization |
| **Resultado esperado** | 401 Unauthorized |
| **Estado** | Pasado |

### TC-INT-006: Listar ordenes paginadas

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderApiV1Test::test_list_orders_returns_paginated_results`) |
| **Precondiciones** | Ordenes del tenant creadas |
| **Steps** | 1. GET /api/v1/orders<br>2. Check estructura: data[], meta{total, page, per_page, last_page} |
| **Resultado esperado** | Response paginada con ordenes del tenant |
| **Estado** | Pasado |

### TC-INT-007: Ver orden con relationships

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-007 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderApiV1Test::test_show_order_returns_order_with_relations`) |
| **Precondiciones** | Orden con customer e items |
| **Steps** | 1. GET /api/v1/orders/{id}<br>2. Check customer e items incluidos |
| **Resultado esperado** | Order con relationships cargadas |
| **Estado** | Pasado |

### TC-INT-008: Crear orden via API

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderApiV1Test::test_create_order_returns_201`) |
| **Precondiciones** | Customer y product existentes |
| **Steps** | 1. POST /api/v1/orders con datos validos<br>2. Check 201 + order_number generado |
| **Resultado esperado** | 201 Created, orden en BD con tenant_id |
| **Estado** | Pasado |

### TC-INT-009: API sin token retorna 401

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-009 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`OrderApiV1Test::test_without_token_returns_401`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. GET /api/v1/orders sin Authorization |
| **Resultado esperado** | 401 |
| **Estado** | Pasado |

### TC-INT-010: Token de otro tenant no ve datos

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-010 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`OrderApiV1Test::test_token_from_other_tenant_does_not_see_data`) |
| **Precondiciones** | 2 tenants con ordenes |
| **Steps** | 1. Crear orden en tenant A<br>2. GET /api/v1/orders con token de tenant B |
| **Resultado esperado** | Lista vacia (no ve datos de tenant A) |
| **Estado** | Pasado |

### TC-INT-011: Filtrar ordenes por status

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-011 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderApiV1Test::test_filter_by_status_works`) |
| **Precondiciones** | Ordenes con distintos status |
| **Steps** | 1. GET /api/v1/orders?status=confirmado |
| **Resultado esperado** | Solo ordenes con status confirmado |
| **Estado** | Pasado |

### TC-INT-012: Listar customers paginados

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-012 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CustomerApiV1Test::test_list_customers_returns_paginated_results`) |
| **Precondiciones** | Customers del tenant |
| **Steps** | 1. GET /api/v1/customers |
| **Resultado esperado** | Lista paginada de customers |
| **Estado** | Pasado |

### TC-INT-013: Buscar customers por name

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-013 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CustomerApiV1Test::test_search_customers_by_name`) |
| **Precondiciones** | Customer "Juan" |
| **Steps** | 1. GET /api/v1/customers?search=Juan |
| **Resultado esperado** | Solo customers que matchean |
| **Estado** | Pasado |

### TC-INT-014: Ver customer con orders_count

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-014 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CustomerApiV1Test::test_show_customer_returns_orders_count`) |
| **Precondiciones** | Customer con ordenes |
| **Steps** | 1. GET /api/v1/customers/{id} |
| **Resultado esperado** | Customer con orders_count incluido |
| **Estado** | Pasado |

### TC-INT-015: Listar items paginados

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-015 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ProductApiV1Test::test_list_products_returns_paginated_results`) |
| **Precondiciones** | Items del tenant |
| **Steps** | 1. GET /api/v1/products |
| **Resultado esperado** | Lista paginada de items activos |
| **Estado** | Pasado |

### TC-INT-016: Ver item con detalles

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-016 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`ProductApiV1Test::test_show_product_returns_details`) |
| **Precondiciones** | Item con model, color, sizes |
| **Steps** | 1. GET /api/v1/products/{id} |
| **Resultado esperado** | Item con relationships cargadas |
| **Estado** | Pasado |

### TC-INT-017: Otro tenant no ve nuestros items

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-017 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`ProductApiV1Test::test_other_tenant_token_does_not_see_our_products`) |
| **Precondiciones** | 2 tenants con items |
| **Steps** | 1. Crear item en tenant A<br>2. GET /api/v1/products con token de tenant B |
| **Resultado esperado** | No ve items de tenant A |
| **Estado** | Pasado |

### TC-INT-018: Listar payments paginados

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-018 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PaymentApiV1Test::test_list_payments_returns_paginated_results`) |
| **Precondiciones** | Payments de ordenes del tenant |
| **Steps** | 1. GET /api/v1/payments |
| **Resultado esperado** | Lista paginada de payments |
| **Estado** | Pasado |

### TC-INT-019: Filtrar payments por order_id

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-019 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PaymentApiV1Test::test_filter_payments_by_order_id`) |
| **Precondiciones** | Payments en distintas ordenes |
| **Steps** | 1. GET /api/v1/payments?order_id={id} |
| **Resultado esperado** | Solo payments de esa orden |
| **Estado** | Pasado |

### TC-INT-020: Ver payment con detalles

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-020 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`PaymentApiV1Test::test_show_payment_returns_details`) |
| **Precondiciones** | Payment existente |
| **Steps** | 1. GET /api/v1/payments/{id} |
| **Resultado esperado** | Payment con detalles |
| **Estado** | Pasado |

---

## 8.2 — Webhooks Salientes

### TC-INT-021: OrderCreated event se dispara

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-021 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`WebhookDispatchTest::test_order_created_dispatches_event`) |
| **Precondiciones** | Orden nueva |
| **Steps** | 1. Crear orden via factory<br>2. Check OrderCreated dispatched |
| **Resultado esperado** | Event fired con order correcta |
| **Estado** | Pasado |

### TC-INT-022: OrderStatusChanged event se dispara

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-022 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`WebhookDispatchTest::test_order_status_change_dispatches_event`) |
| **Precondiciones** | Orden existente |
| **Steps** | 1. Cambiar status de orden<br>2. Check OrderStatusChanged dispatched |
| **Resultado esperado** | Event fired con order, old_status, new_status |
| **Estado** | Pasado |

### TC-INT-023: PaymentReceived event se dispara

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-023 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`WebhookDispatchTest::test_payment_dispatches_event`) |
| **Precondiciones** | Payment nuevo |
| **Steps** | 1. Crear payment<br>2. Check PaymentReceived dispatched |
| **Resultado esperado** | Event fired con payment |
| **Estado** | Pasado |

### TC-INT-024: WebhookService despacha job

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-024 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`WebhookDispatchTest::test_webhook_service_dispatches_job`) |
| **Precondiciones** | Tenant con webhooks habilitados, URL y eventos configurados |
| **Steps** | 1. Configurar tenant settings<br>2. Llamar WebhookService::dispatch()<br>3. Check SendWebhookJob queued |
| **Resultado esperado** | Job encolado con payload correcto |
| **Estado** | Pasado |

### TC-INT-025: HMAC signature es correcta

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-025 |
| **Prioridad** | P1 |
| **Tipo** | Security |
| **Automatizado** | Si (`WebhookDispatchTest::test_hmac_signature_is_correct`) |
| **Precondiciones** | Payload y secret conocidos |
| **Steps** | 1. Generar signature con secret<br>2. Check hash_hmac('sha256', payload, secret) |
| **Resultado esperado** | Signature valida y verificable |
| **Estado** | Pasado |

---

## Manual UI — API Settings & Webhooks

### TC-INT-026: Pagina API Settings renderiza (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-026 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin, plan Pro |
| **Steps** | 1. Navegar a /admin/api-settings<br>2. Check formulario "Generar Token de API"<br>3. Check table "Tokens Activos"<br>4. Check seccion "Referencia Rapida" con endpoints |
| **Resultado esperado** | Pagina renderiza con todas las secciones |
| **Estado** | Pasado |

### TC-INT-027: Pagina Webhook Settings renderiza (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-INT-027 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin, plan Pro |
| **Steps** | 1. Navegar a /admin/webhook-settings<br>2. Check toggle "Activar webhooks"<br>3. Check campo URL + Signing Secret<br>4. Check checkboxes de eventos (3 eventos)<br>5. Check boton "Guardar Configuracion" |
| **Resultado esperado** | Pagina renderiza con toggle, URL, secret, eventos |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 27 |
| Total | **27** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 25 |
| Manual (Chrome DevTools MCP) | 2 |
