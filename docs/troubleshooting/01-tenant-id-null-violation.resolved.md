# 01 - tenant_id NULL violation al crear orders

**Status:** Resuelto
**Date:** 2026-02-20
**Branch:** `feature/saas-phase1-multitenancy`
**Contexto:** SaaS Fase 1 — Multi-tenancy

---

## Problema

Al crear un order desde `/admin/orders/create`, el insert fallaba con:

```
SQLSTATE[23502]: Not null violation: 7 ERROR: null value in column "tenant_id"
of relation "orders" violates not-null constraint
```

El SQL generado no included `tenant_id` en las columns del INSERT:

```sql
insert into "orders" ("customer_phone", "customer_name", "customer_email",
"customer_id", "delivery_type", ..., "updated_at", "created_at")
values (...) returning "id"
-- tenant_id ausente
```

---

## Diagnosis

### Step 1: Check la cadena de assignment de tenant_id

El flujo esperado es:

```
Request HTTP
  → Middleware EnsureTenant (bindea tenant al container)
    → app()->instance('currentTenant', $tenant)
      → currentTenant() retorna el tenant
        → BelongsToTenant::creating() asigna $model->tenant_id
```

### Step 2: Check association user-tenant

```sql
-- Resultado: 0 filas
SELECT * FROM tenant_user WHERE user_id = 1;
```

```php
// Tinker
$user = User::first(); // admin@example.com
$user->tenants()->count(); // 0
```

**Finding 1:** El user admin no had association con no tenant en `tenant_user`. La migration `backfill_and_enforce_tenant_id` created el tenant y assigned `tenant_id` a las tables de negocio, pero no created la relationship pivot `tenant_user`.

### Step 3: Comportamiento del middleware sin tenants

```php
// EnsureTenant.php lines 49-52
if ($userTenants->count() === 0) {
    // No tenants — allow through without tenant context
    return $next($request);
}
```

Con 0 tenants asociados, el middleware dejaba pasar sin bindear tenant → `currentTenant()` retornaba `null` → `BelongsToTenant::creating()` no asignaba `tenant_id`.

### Step 4: Ejecutar DefaultTenantSeeder

```bash
php artisan db:seed --class=DefaultTenantSeeder
# Default tenant 'SaaS Template' ready (ID: 1)
```

```sql
-- Verification posterior
SELECT * FROM tenant_user;
-- user_id: 1 | tenant_id: 1 | role: owner
```

### Step 5: Error persisted after del seeder

Tras cerrar session y re-autenticarse, el error continued. El query log del request mostraba:

```
pgsql: select * from "sessions" where "id" = '...' limit 1           -- 11ms
pgsql: select * from "users" where "id" = 1 limit 1                  -- 2ms
pgsql: select * from "order_lines" where "order_lines"."order_id"...  -- 2ms
pgsql: select * from "customers" where ("phone" = '...') limit 1     -- 1ms
```

**Finding 2:** No was queries a `tenants` ni `tenant_user`. El middleware `EnsureTenant` no estaba corriendo para esta request.

### Step 6: Identificar el endpoint

El request que executed el `save()` era:

```
POST http://localhost:8000/livewire/update
```

El middleware estaba registrado solo en `authMiddleware` del panel Filament:

```php
// AdminPanelProvider.php
->authMiddleware([
    Authenticate::class,
    EnsureTenant::class,  // Solo aplica a rutas /admin/*
])
```

Las requests de Livewire van a `/livewire/update`, que usa el middleware group `web`, no los `authMiddleware` del panel. Por lo tanto, `EnsureTenant` nunca se executed durante la creation del order.

---

## Root cause (dos problemas)

1. **Missing la relationship `tenant_user`** entre el user admin y el tenant. El `DefaultTenantSeeder` no se was ejecutado after de las migrations de multi-tenancy.

2. **Middleware `EnsureTenant` mal ubicado.** Estaba solo en `authMiddleware` del panel Filament, que no cubre requests de Livewire (`/livewire/update`). Incluso con la relationship correcta, el tenant nunca se bindeaba al container durante el save.

---

## Solution

### Fix 1: Ejecutar seeder para crear la association

```bash
php artisan db:seed --class=DefaultTenantSeeder
```

### Fix 2: Mover EnsureTenant al middleware group `web`

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustProxies(at: '*');
    $middleware->redirectGuestsTo(fn () => route('customer.login'));
    $middleware->appendToGroup('web', [
        \App\Http\Middleware\EnsureTenant::class,
    ]);
    $middleware->alias([
        'tenant' => \App\Http\Middleware\EnsureTenant::class,
        'customer.tenant' => \App\Http\Middleware\EnsureCustomerTenant::class,
    ]);
})
```

Esto garantiza que `EnsureTenant` corre en TODAS las requests web, incluyendo `/livewire/update`. El middleware ya maneja users no autenticados (early return en line 16).

---

## Validation post-fix

### Integridad referencial (todas las tables)

```sql
-- Orders sin tenant: 0
SELECT count(*) FROM orders WHERE tenant_id IS NULL;  -- 0

-- Customers sin tenant: 0
SELECT count(*) FROM customers WHERE tenant_id IS NULL;  -- 0

-- Products sin tenant: 0
SELECT count(*) FROM products WHERE tenant_id IS NULL;  -- 0

-- Order items orphan: 0
SELECT count(*) FROM order_lines
WHERE order_id NOT IN (SELECT id FROM orders);  -- 0

-- Payments orphan: 0
SELECT count(*) FROM payments
WHERE order_id NOT IN (SELECT id FROM orders);  -- 0

-- Orders con customer invalid: 0
SELECT count(*) FROM orders
WHERE customer_id IS NOT NULL
AND customer_id NOT IN (SELECT id FROM customers);  -- 0
```

### Consistencia de tenant entre entidades relacionadas

```sql
-- Orders y customers deben compartir tenant_id
SELECT o.id as order_id, o.tenant_id as order_tenant,
       c.id as customer_id, c.tenant_id as customer_tenant,
       o.tenant_id = c.tenant_id as match
FROM orders o
JOIN customers c ON o.customer_id = c.id;

-- Resultado: todas las filas match = true
-- order_id | order_tenant | customer_id | customer_tenant | match
-- 1        | 1            | 1           | 1               | true
-- 2        | 1            | 1           | 1               | true
-- 3        | 1            | 1           | 1               | true
-- 7        | 1            | 2           | 1               | true
```

### Consistencia financiera (todas las orders)

```sql
-- Subtotal = suma de items
SELECT o.id, o.subtotal, COALESCE(SUM(oi.subtotal), 0) as items_sum,
       o.subtotal = COALESCE(SUM(oi.subtotal), 0) as match
FROM orders o
LEFT JOIN order_lines oi ON oi.order_id = o.id
GROUP BY o.id, o.subtotal;

-- Resultado:
-- id | subtotal | items_sum | match
-- 1  | 1295.00  | 1295.00   | true
-- 2  | 2975.00  | 2975.00   | true
-- 3  | 2275.00  | 2275.00   | true
-- 7  | 3500.00  | 3500.00   | true

-- Total = subtotal + shipping
SELECT id, subtotal, COALESCE(shipping_cost, 0) as shipping,
       total, subtotal + COALESCE(shipping_cost, 0) as expected,
       total = subtotal + COALESCE(shipping_cost, 0) as match
FROM orders;

-- Resultado: todas match = true

-- total_paid = suma de payments
SELECT o.id, o.total_paid, COALESCE(SUM(p.amount), 0) as payments_sum,
       o.total_paid = COALESCE(SUM(p.amount), 0) as match
FROM orders o
LEFT JOIN payments p ON p.order_id = o.id
GROUP BY o.id, o.total_paid;

-- Resultado: todas match = true
```

### Validation specific Order #7 (creada post-fix)

```
Order #7
├── tenant_id: 1 (SaaS Template) ✓
├── customer_id: 2 (Ignacio Navarrete, tenant_id: 1) ✓
├── Items: 1
│   └── Item #4: product_id 5 (tenant_id: 1) | 20 × $175.00 = $3,500.00 ✓
├── Financiero:
│   ├── Subtotal: $3,500.00 (= sum items) ✓
│   ├── Shipping: $185.00 (DHL Economy Select Domestic) ✓
│   ├── Total: $3,685.00 (= subtotal + shipping) ✓
│   ├── Paid: $800.00 (= sum payments) ✓
│   │   ├── Payment #7: $300.00 cash
│   │   └── Payment #8: $500.00 cash
│   └── Balance: $2,885.00 pendiente
└── Status: confirmado (tiene anticipo > 0) ✓
```

---

## Tables con y sin tenant_id

| Table | tenant_id | Justification |
|-------|-----------|---------------|
| orders | SI | Entidad principal de negocio |
| customers | SI | Cada tenant tiene sus customers |
| products | SI | Catalog per tenant |
| pricing_rules | SI | Precios por tenant |
| branches | SI | Locationes por tenant |
| business_configs | SI | Configuration por tenant |
| quotes | SI | Quotes por tenant |
| item_categories | SI | Models de item por tenant |
| sizes | SI | Sizes por tenant |
| colors | SI | Colors por tenant |
| dimension_limits | SI | Limits per tenant |
| order_lines | NO | Hereda tenant via order (FK) |
| payments | NO | Hereda tenant via order (FK) |
| product_sizes | NO | Table pivot, hereda via product |
| customer_addresses | NO | Hereda tenant via customer (FK) |

---

## Files modificados

| File | Cambio |
|---------|--------|
| `bootstrap/app.php` | Agregado `EnsureTenant` al middleware group `web` |

## Files involucrados (sin modificar)

| File | Rol |
|---------|-----|
| `app/Models/Concerns/BelongsToTenant.php` | Trait que asigna tenant_id en evento `creating` |
| `app/Http/Middleware/EnsureTenant.php` | Middleware que resuelve y bindea tenant al container |
| `app/helpers.php` | Helper global `currentTenant()` |
| `database/seeders/DefaultTenantSeeder.php` | Crea tenant default y association tenant_user |
| `app/Providers/Filament/AdminPanelProvider.php` | Registration del middleware en authMiddleware (insuficiente) |

## Lecciones aprendidas

1. **Livewire update bypass:** Las requests de Livewire (`/livewire/update`) no pasan por los `authMiddleware` del panel Filament. Middleware critical como `EnsureTenant` debe ir en el group `web`.
2. **Migrations vs seeders:** Las migrations de schema no deben asumir que los seeders ya corrieron. El `DefaultTenantSeeder` debe ser parte del flujo required post-migration.
3. **Validar el query log:** Cuando un middleware no produce efecto, check si realmente se executed revisando las queries. La ausencia de queries a `tenants`/`tenant_user` fue la evidence clave.
