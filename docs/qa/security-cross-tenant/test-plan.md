# Test Plan — Security: Fix Cross-Tenant Data Leaks

**Version:** 1.0
**Date:** 2026-02-24
**Feature:** Correccion de data leaks cross-tenant en payments, order items, widgets, API routes
**Branch:** security/fix-cross-tenant-data-leaks
**PR:** #48

## 1. Objetivo

Validar que la correccion de data leaks cross-tenant funciona correctamente: tenant isolation en Payment/OrderLine, filtros explicitos en JOINs, eliminacion de rutas legacy sin auth, y proteccion de la ruta publica de payment status.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Migration | `tenant_id` en `payments` y `order_lines` con backfill desde `orders` |
| BelongsToTenant | Global scope en Payment, OrderLine, CancellationSurvey, FeatureUsage |
| JOINs con filtro | Filtro `orders.tenant_id` explicito en 6 queries con raw JOIN |
| PaymentStatusController | Guard `withoutGlobalScopes()` + `whereNotNull('payment_gateway_order_id')` |
| Legacy API routes | Eliminacion de `/api/products`, `/api/orders`, `/api/quotes` CRUD |
| V1 updateStatus | `PATCH /api/v1/orders/{order}/status` autenticado y tenant-scoped |
| Tests automatizados | 278 tests, 714 assertions |

### Fuera de alcance

- PersonalAccessToken no recibe global scope (rompe Sanctum token resolution)
- Pricing routes publicas (intencional para landing page)
- Bug de cache local en app mobile (problema del frontend, no del backend)

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Unit/Feature tests | PHPUnit (`composer test`) | 278 tests, 714 assertions |
| Static analysis | PHPStan level 5 (`composer analyse`) | Sin errores nuevos |
| Manual — Web Admin | Chrome DevTools MCP | Tenant isolation en widgets |
| Manual — API | Chrome DevTools (fetch) | Rutas eliminadas y auth |
| Manual — Mobile | Chrome DevTools MCP + interceptor | Tenant isolation en API v1 |

## 4. Criterios de entrada

- [x] Branch security/fix-cross-tenant-data-leaks creado desde develop
- [x] Migration creada con backfill y constraints
- [x] BelongsToTenant agregado a 4 models
- [x] Filtros tenant_id en 6 queries con JOIN
- [x] PaymentStatusController protegido
- [x] Rutas legacy eliminadas, updateStatus movido a V1
- [x] Tests actualizados

## 5. Criterios de salida

- [x] 278 tests pasan (0 failures)
- [x] PHPStan sin errores nuevos
- [x] Tests manuales web admin: tenant 2 no ve data de tenant 1
- [x] Tests manuales web admin: tenant 1 ve su propia data
- [x] `GET /api/orders` retorna 404
- [x] `PATCH /api/v1/orders/1/status` sin auth retorna 401
- [x] App mobile: backend retorna 403 para tenant sin plan pro

## 6. Riesgos

| Riesgo | Mitigacion |
|--------|-----------|
| Migration falla en operations (tables sin datos) | Backfill usa JOIN, tolerante a tables vacias |
| Global scope rompe queries internas | Filtros explicitos en JOINs como defensa en profundidad |
| PersonalAccessToken scope rompe auth | Descartado durante desarrollo, revertido a manual `tenant()` |
| App mobile muestra datos cacheados | Bug de frontend mobile, no del backend — documentado como hallazgo |
