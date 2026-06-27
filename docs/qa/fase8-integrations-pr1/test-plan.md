# Test Plan — SaaS Fase 8 PR1: API Publica + Webhooks (8.1-8.2)

**Version:** 1.0
**Date:** 2026-02-22
**Feature:** API REST v1 con Sanctum, middlewares tenant-aware, webhooks salientes con HMAC
**Branch:** feature/saas-phase8-integrations-pr1

## 1. Objetivo

Validar que la API REST v1 funciona con autenticacion Sanctum tenant-aware (tokens asociados a tenant), middlewares de security (ResolveApiTenant, EnsureApiAccess), endpoints CRUD para orders/customers/products/payments, y webhooks salientes con firma HMAC SHA256 para eventos de ordenes y payments.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Sanctum Tokens | PersonalAccessToken con tenant_id, generacion/revocacion |
| Middleware chain | auth:sanctum → ResolveApiTenant → EnsureApiAccess → throttle |
| API v1 Orders | GET /api/v1/orders (paginado, filtros), GET /:id, POST / |
| API v1 Customers | GET /api/v1/customers (busqueda), GET /:id |
| API v1 Products | GET /api/v1/products (activos), GET /:id |
| API v1 Payments | GET /api/v1/payments (filtro order_id), GET /:id |
| Webhook Events | OrderCreated, OrderStatusChanged, PaymentReceived |
| WebhookService | Dispatch condicional segun settings tenant |
| SendWebhookJob | Queue, 3 retries, backoff, HMAC SHA256, WebhookLog |
| Settings Pages | ApiSettings (tokens), WebhookSettings (URL, secret, eventos) |

### Fuera de alcance

- Rate limiting por plan (PR2)
- Integracion Shopify (PR2)
- Webhook retry UI / manual resend

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Feature tests | PHPUnit (composer test) | Tokens, API endpoints, webhooks, middleware |
| Manual UI | Chrome DevTools MCP | Paginas Filament ApiSettings, WebhookSettings |
| Regresion | PHPUnit suite completa | 240 tests existentes |

## 4. Criterios de entrada

- [x] Branch feature/saas-phase8-integrations-pr1 creado desde develop
- [x] Sanctum instalado y configurado con PersonalAccessToken custom
- [x] Middlewares ResolveApiTenant + EnsureApiAccess implementados
- [x] 4 controladores API v1 (Order, Customer, Product, Payment)
- [x] Form Requests (StoreOrderRequest, ListOrdersRequest)
- [x] WebhookService + SendWebhookJob + WebhookEventSubscriber
- [x] 3 eventos: OrderCreated, OrderStatusChanged, PaymentReceived
- [x] WebhookLog model + migracion
- [x] Tests creados (25 tests en 7 files)

## 5. Criterios de salida

- [x] 240/240 tests pasan (600 assertions)
- [x] Checklist manual completo (10/10 verificaciones)
- [x] 3 bugs encontrados y corregidos
- [x] Sin regresiones en tests existentes

## 6. Datos de test

### Tokens

| Escenario | Resultado esperado |
|-----------|-------------------|
| Generar token con plan Pro | Token creado con tenant_id correcto |
| Token revocado | 401 Unauthorized |
| Plan Starter intenta usar API | 403 PLAN_UPGRADE_REQUIRED |
| Tenant inactivo | 403 TENANT_INACTIVE |

### API Endpoints

| Escenario | Resultado esperado |
|-----------|-------------------|
| GET /api/v1/orders | Lista paginada, solo ordenes del tenant |
| GET /api/v1/orders?status=confirmado | Filtro por status funciona |
| POST /api/v1/orders | 201 Created con order_number generado |
| GET /api/v1/customers?search=Juan | Busqueda por name/telefono |
| GET /api/v1/products | Solo items activos del tenant |
| Token de otro tenant | No ve datos del primer tenant |

### Webhooks

| Escenario | Resultado esperado |
|-----------|-------------------|
| Orden creada | OrderCreated event dispatched |
| Status cambia | OrderStatusChanged event dispatched |
| Payment registrado | PaymentReceived event dispatched |
| WebhookService con URL configurada | SendWebhookJob dispatched |
| HMAC signature | SHA256 valida con secret del tenant |

## 7. Riesgos

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| tenant_id NOT NULL en createToken | Alta | Alto | Detectado: Sanctum no pasa campos extra en INSERT. Fix: nullable + update post-creacion |
| Sanctum cachea token en mismo proceso | Media | Alto | Detectado: revocar antes del request, no despues |
| PHPStan @use annotation en trait equivocado | Media | Bajo | Detectado: mover anotacion al trait correcto |

## 8. Entregables

| Artefacto | File |
|-----------|---------|
| Plan de tests | `docs/qa/fase8-integrations-pr1/test-plan.md` |
| Casos de test | `docs/qa/fase8-integrations-pr1/test-cases.md` |
| Resumen QA | `docs/qa/fase8-integrations-pr1/test-summary.md` |
