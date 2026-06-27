# QA Summary: Security — Fix Cross-Tenant Data Leaks

**Date:** 2026-02-24
**Branch:** security/fix-cross-tenant-data-leaks
**PR:** #48

## Test Results

```
composer test
Tests: 278 passed (714 assertions)
Duration: ~25s
```

### Tests modificados

| File | Cambio | Status |
|---------|--------|--------|
| tests/Feature/Api/V1/OrderApiV1Test.php | +2 tests (updateStatus) | PASS |
| tests/Feature/Api/QuoteApiTest.php | Reemplazados 3 tests legacy por 2 tests de rutas eliminadas | PASS |
| tests/Feature/Api/OrderApiTest.php | Eliminado (rutas legacy removidas) | N/A |

### Tests existentes (270) — No Regressions

| Suite | Tests | Status |
|-------|-------|--------|
| tests/Unit/*.php | 46 | PASS |
| tests/Feature/*.php | 232 | PASS |

## Verification Checklist

### A. Migration

- [x] `tenant_id` agregado a `payments` (nullable → backfill → NOT NULL)
- [x] `tenant_id` agregado a `order_lines` (nullable → backfill → NOT NULL)
- [x] Foreign keys a `tenants.id` con CASCADE delete
- [x] Indices compuestos `[tenant_id, order_id]` en ambas tables
- [x] Migration reversible (down method funcional)

### B. Global Scopes

- [x] Payment: `BelongsToTenant` trait + `tenant_id` en fillable
- [x] OrderLine: `BelongsToTenant` trait + `tenant_id` en fillable
- [x] CancellationSurvey: `BelongsToTenant` trait (ya tenia `tenant_id`)
- [x] FeatureUsage: `BelongsToTenant` trait (ya tenia `tenant_id`)
- [x] PersonalAccessToken: sin cambio (scope rompe Sanctum token resolution)

### C. Raw JOINs con filtro explicito

- [x] ProductProfitability: `->where('orders.tenant_id', currentTenant()?->id)`
- [x] MonthlyReport (topProducts): `->where('orders.tenant_id', currentTenant()?->id)`
- [x] CashRegister (paymentsByMethod): `->where('orders.tenant_id', currentTenant()?->id)`
- [x] CashRegister (prevTotal): `->where('orders.tenant_id', currentTenant()?->id)`
- [x] ProductionBoard: `->where('orders.tenant_id', currentTenant()?->id)`
- [x] ExportController (profitability): `->where('orders.tenant_id', currentTenant()?->id)`

### D. PaymentStatusController

- [x] `Order::withoutGlobalScopes()` (intencional — ruta publica post-payment)
- [x] `->whereNotNull('payment_gateway_order_id')` (limita a ordenes con payment online)
- [x] Aplicado a metodos `success()` y `failure()`

### E. Rutas legacy eliminadas

- [x] `Route::apiResource('products', ProductController)` — eliminada
- [x] `Route::get('products/{product}/sizes', ...)` — eliminada
- [x] `Route::apiResource('orders', OrderController)` — eliminada
- [x] `Route::patch('orders/{order}/status', ...)` — eliminada
- [x] `Route::apiResource('quotes', ...) ->only([index, show, store, destroy])` — reducida a solo `store`
- [x] `app/Http/Controllers/Api/OrderController.php` — eliminado
- [x] `app/Http/Controllers/Api/ProductController.php` — eliminado
- [x] QuoteController: eliminados metodos `index()`, `show()`, `destroy()`

### F. V1 updateStatus

- [x] `PATCH /api/v1/orders/{order}/status` agregada en `routes/api.php`
- [x] Metodo `updateStatus()` en `V1\OrderController`
- [x] Protected por middlewares: `auth:sanctum`, `api.tenant`, `api.pro`, `throttle:api-tenant`

## Tests Manuales

| # | Test Case | Plataforma | Resultado |
|---|-----------|------------|-----------|
| TC-SEC-01 | Tenant 2 no ve datos de Tenant 1 en widgets | Web Admin | **PASS** |
| TC-SEC-02 | Tenant 1 ve sus propios datos correctamente | Web Admin | **PASS** |
| TC-SEC-03 | GET /api/orders retorna 404 | API | **PASS** |
| TC-SEC-04 | PATCH /api/v1/orders/1/status sin auth retorna 401 | API | **PASS** |
| TC-SEC-05 | Tenant 1 ve ordenes en app mobile | Mobile | **PASS** |
| TC-SEC-06 | Tenant 2 recibe 403 en API v1 (plan starter) | Mobile | **PASS** |
| TC-SEC-07 | 278 tests automatizados pasan | PHPUnit | **PASS** |
| TC-SEC-08 | PHPStan sin errores nuevos | PHPStan | **PASS** |

## Findings

### ~BUG-SEC-01~: Descartado — App mobile NO muestra datos cacheados

Reportado inicialmente como "app mobile muestra datos cacheados del login anterior". Tras verificacion, el comportamiento es correcto:

- El middleware `EnsureApiAccess` (api.pro) retorna 403 con `PLAN_UPGRADE_REQUIRED` para tenants con plan starter
- React Query en la app mobile maneja el 403 y muestra estados vacios ("No hay orders", "Sin datos")
- El falso positivo se produjo por el interceptor de fetch inyectado durante la test manual en web, que no replica el comportamiento real de la app

**Estado: Descartado (no es bug)**

### BUG-SEC-02: Error de Notifications en web al hacer logout

| Campo | Valor |
|-------|-------|
| **Severidad** | Baja |
| **Prioridad** | P4 |
| **Estado** | Abierto |
| **Componente** | saas-template-mobile (frontend) |

**Descripcion:** Al hacer logout en la app mobile corriendo en web (localhost:8081), se lanza `Notifications.removeNotificationSubscription is not a function` en `usePushNotifications.ts:47`. La API de Expo Notifications no esta disponible en web. Solo afecta environment de desarrollo web, no la app nativa.

## Conclusion

**APROBADO PARA MERGE.** Todas las tests automatizadas y manuales pasan. Los data leaks cross-tenant estan corregidos en el backend. El unico hallazgo es un error menor de Expo Notifications en web (BUG-SEC-02), no relacionado con este PR.

## Files modificados (19)

| File | Cambio |
|---------|--------|
| database/migrations/2026_02_24_100001_*.php | Nuevo — migration tenant_id |
| app/Models/Payment.php | +BelongsToTenant, +tenant_id fillable |
| app/Models/OrderLine.php | +BelongsToTenant, +tenant_id fillable |
| app/Models/CancellationSurvey.php | +BelongsToTenant |
| app/Models/FeatureUsage.php | +BelongsToTenant |
| app/Filament/Widgets/ProductProfitability.php | +tenant filter en JOIN |
| app/Filament/Widgets/MonthlyReport.php | +tenant filter en JOIN |
| app/Filament/Pages/CashRegister.php | +tenant filter en 2 JOINs |
| app/Filament/Pages/ProductionBoard.php | +tenant filter en JOIN |
| app/Http/Controllers/ExportController.php | +tenant filter en JOIN |
| app/Http/Controllers/PaymentStatusController.php | withoutGlobalScopes + payment_gateway guard |
| app/Http/Controllers/Api/V1/OrderController.php | +updateStatus method |
| app/Http/Controllers/Api/QuoteController.php | -index, -show, -destroy |
| app/Http/Controllers/Api/OrderController.php | Eliminado |
| app/Http/Controllers/Api/ProductController.php | Eliminado |
| routes/api.php | -legacy routes, +v1 updateStatus |
| tests/Feature/Api/OrderApiTest.php | Eliminado |
| tests/Feature/Api/QuoteApiTest.php | Actualizado |
| tests/Feature/Api/V1/OrderApiV1Test.php | +2 tests |
