# SQL Queries Reference — Debugging Multi-Tenant

Consultas PostgreSQL para diagnostico rapido, debugging y toma de decisiones.
Ejecutar via `railway exec psql` (operations) o `psql` (local).

---

## 1. Tenants & Users

### Vista general de tenants
```sql
SELECT t.id, t.name, t.slug, t.plan, t.is_active,
       t.trial_ends_at, t.subscribed_at,
       u.name AS owner_name, u.email AS owner_email,
       t.created_at::date AS created
FROM tenants t
JOIN users u ON t.owner_id = u.id
ORDER BY t.id;
```

### Users por tenant con rol
```sql
SELECT t.id AS tenant_id, t.name AS tenant,
       u.id AS user_id, u.name, u.email,
       tu.role, u.last_login_at
FROM tenant_user tu
JOIN tenants t ON tu.tenant_id = t.id
JOIN users u ON tu.user_id = u.id
ORDER BY t.id, tu.role DESC;
```

### Users sin tenant asignado
```sql
SELECT u.id, u.name, u.email, u.is_super_admin
FROM users u
LEFT JOIN tenant_user tu ON u.id = tu.user_id
WHERE tu.id IS NULL;
```

### Super admins
```sql
SELECT id, name, email, last_login_at
FROM users
WHERE is_super_admin = true;
```

---

## 2. Ordenes & Status

### Resumen de ordenes por tenant
```sql
SELECT t.name AS tenant, o.status,
       COUNT(*) AS total,
       SUM(o.total) AS monto_total,
       SUM(o.total_paid) AS monto_pagado
FROM orders o
JOIN tenants t ON o.tenant_id = t.id
WHERE o.deleted_at IS NULL
GROUP BY t.name, o.status
ORDER BY t.name, o.status;
```

### Ordenes con balance pendiente
```sql
SELECT o.id, t.name AS tenant, o.customer_name,
       o.total, o.total_paid,
       (o.total - o.total_paid) AS balance,
       o.status, o.created_at::date
FROM orders o
JOIN tenants t ON o.tenant_id = t.id
WHERE o.total > o.total_paid
  AND o.status NOT IN ('cancelled', 'delivered')
  AND o.deleted_at IS NULL
ORDER BY balance DESC;
```

### Ordenes recientes (ultimas 24h)
```sql
SELECT o.id, t.name AS tenant, o.customer_name,
       o.status, o.total, o.priority,
       o.created_at
FROM orders o
JOIN tenants t ON o.tenant_id = t.id
WHERE o.created_at >= NOW() - INTERVAL '24 hours'
  AND o.deleted_at IS NULL
ORDER BY o.created_at DESC;
```

### Pipeline de operations
```sql
SELECT o.id, t.name AS tenant, o.customer_name,
       o.status, o.priority, o.estimated_delivery,
       COUNT(oi.id) AS items,
       SUM(oi.quantity) AS piezas
FROM orders o
JOIN tenants t ON o.tenant_id = t.id
LEFT JOIN order_lines oi ON o.id = oi.order_id
WHERE o.status IN ('confirmed', 'in_progress')
  AND o.deleted_at IS NULL
GROUP BY o.id, t.name
ORDER BY o.priority ASC, o.created_at ASC;
```

### Tiempo promedio por status (dias)
```sql
SELECT t.name AS tenant,
       ROUND(AVG(EXTRACT(EPOCH FROM (o.confirmed_at - o.created_at)) / 86400), 1) AS dias_a_confirmado,
       ROUND(AVG(EXTRACT(EPOCH FROM (o.in_production_at - o.confirmed_at)) / 86400), 1) AS dias_a_operations,
       ROUND(AVG(EXTRACT(EPOCH FROM (o.ready_at - o.in_production_at)) / 86400), 1) AS dias_a_listo,
       ROUND(AVG(EXTRACT(EPOCH FROM (o.delivered_at - o.ready_at)) / 86400), 1) AS dias_a_entregado
FROM orders o
JOIN tenants t ON o.tenant_id = t.id
WHERE o.deleted_at IS NULL
GROUP BY t.name;
```

---

## 3. Payments & Ingresos

### Ingresos por tenant (mes actual)
```sql
SELECT t.name AS tenant,
       COUNT(*) AS num_payments,
       SUM(p.amount) AS total_cobrado,
       p.method,
       ROUND(AVG(p.amount), 2) AS promedio
FROM payments p
JOIN tenants t ON p.tenant_id = t.id
WHERE p.received_at >= DATE_TRUNC('month', CURRENT_DATE)
GROUP BY t.name, p.method
ORDER BY t.name, total_cobrado DESC;
```

### Payments por metodo (global)
```sql
SELECT method,
       COUNT(*) AS total,
       SUM(amount) AS monto,
       ROUND(AVG(amount), 2) AS promedio
FROM payments
GROUP BY method
ORDER BY monto DESC;
```

### Ordenes sin ningun payment
```sql
SELECT o.id, t.name AS tenant, o.customer_name,
       o.total, o.status, o.created_at::date
FROM orders o
JOIN tenants t ON o.tenant_id = t.id
LEFT JOIN payments p ON o.id = p.order_id
WHERE p.id IS NULL
  AND o.status NOT IN ('cancelled')
  AND o.deleted_at IS NULL
ORDER BY o.created_at;
```

---

## 4. Items & Catalogo

### Items por tenant con model
```sql
SELECT p.id, t.name AS tenant, p.title,
       pm.code AS model, pm.name AS model_name,
       p.model_id, p.is_active
FROM products p
JOIN tenants t ON p.tenant_id = t.id
LEFT JOIN item_categories pm ON p.model_id = pm.id
WHERE p.deleted_at IS NULL
ORDER BY t.name, p.title;
```

### Items sin model_id (causa fallo en calculo de precios)
```sql
SELECT p.id, t.name AS tenant, p.title, p.model_id
FROM products p
JOIN tenants t ON p.tenant_id = t.id
WHERE p.model_id IS NULL
  AND p.deleted_at IS NULL;
```

### Available sizes por item
```sql
SELECT p.title, t.name AS tenant,
       STRING_AGG(s.code, ', ' ORDER BY s.display_order) AS tallas
FROM products p
JOIN tenants t ON p.tenant_id = t.id
JOIN product_sizes ps ON p.id = ps.product_id
JOIN sizes s ON ps.size_id = s.id
WHERE p.deleted_at IS NULL
GROUP BY p.id, p.title, t.name
ORDER BY t.name, p.title;
```

### Reglas de precios por model
```sql
SELECT pm.code AS model, pr.size_type,
       pr.min_qty, pr.max_qty, pr.price, pr.label,
       t.name AS tenant
FROM pricing_rules pr
JOIN item_categories pm ON pr.model_id = pm.id
JOIN tenants t ON pr.tenant_id = t.id
WHERE pr.deleted_at IS NULL
  AND pr.is_active = true
ORDER BY t.name, pm.code, pr.size_type, pr.min_qty;
```

---

## 5. Customers

### Customers por tenant
```sql
SELECT t.name AS tenant,
       COUNT(*) AS total_customers,
       COUNT(CASE WHEN c.email IS NOT NULL THEN 1 END) AS con_email,
       COUNT(CASE WHEN c.notifications_messaging THEN 1 END) AS con_messaging
FROM customers c
JOIN tenants t ON c.tenant_id = t.id
GROUP BY t.name;
```

### Customers con mas ordenes (top 10)
```sql
SELECT c.name, c.phone, t.name AS tenant,
       COUNT(o.id) AS ordenes,
       SUM(o.total) AS total_gastado
FROM customers c
JOIN tenants t ON c.tenant_id = t.id
JOIN orders o ON c.id = o.customer_id AND o.deleted_at IS NULL
GROUP BY c.id, c.name, c.phone, t.name
ORDER BY ordenes DESC
LIMIT 10;
```

### Telefono duplicado entre tenants (check scope)
```sql
SELECT phone, COUNT(DISTINCT tenant_id) AS tenants,
       STRING_AGG(DISTINCT t.name, ', ') AS tenant_names
FROM customers c
JOIN tenants t ON c.tenant_id = t.id
GROUP BY phone
HAVING COUNT(DISTINCT tenant_id) > 1;
```

---

## 6. Cross-Tenant Integrity

### Check order_lines con tenant_id distinto a su orden
```sql
SELECT oi.id AS item_id, oi.tenant_id AS item_tenant,
       o.id AS order_id, o.tenant_id AS order_tenant
FROM order_lines oi
JOIN orders o ON oi.order_id = o.id
WHERE oi.tenant_id != o.tenant_id;
```

### Check payments con tenant_id distinto a su orden
```sql
SELECT p.id AS payment_id, p.tenant_id AS payment_tenant,
       o.id AS order_id, o.tenant_id AS order_tenant
FROM payments p
JOIN orders o ON p.order_id = o.id
WHERE p.tenant_id != o.tenant_id;
```

### Registrations huerfanos (FK rota)
```sql
-- Order items sin orden
SELECT oi.id FROM order_lines oi
LEFT JOIN orders o ON oi.order_id = o.id WHERE o.id IS NULL;

-- Payments sin orden
SELECT p.id FROM payments p
LEFT JOIN orders o ON p.order_id = o.id WHERE o.id IS NULL;

-- Ordenes con customer_id invalido
SELECT o.id, o.customer_id FROM orders o
LEFT JOIN customers c ON o.customer_id = c.id
WHERE o.customer_id IS NOT NULL AND c.id IS NULL AND o.deleted_at IS NULL;
```

### Conteo de registrations por tenant (panorama general)
```sql
SELECT t.id, t.name, t.plan,
       (SELECT COUNT(*) FROM orders WHERE tenant_id = t.id AND deleted_at IS NULL) AS orders,
       (SELECT COUNT(*) FROM customers WHERE tenant_id = t.id) AS customers,
       (SELECT COUNT(*) FROM products WHERE tenant_id = t.id AND deleted_at IS NULL) AS products,
       (SELECT COUNT(*) FROM payments WHERE tenant_id = t.id) AS payments,
       (SELECT COUNT(*) FROM order_lines WHERE tenant_id = t.id) AS order_lines,
       (SELECT COUNT(*) FROM branches WHERE tenant_id = t.id) AS branches
FROM tenants t
ORDER BY t.id;
```

---

## 7. SaaS & Suscripciones

### Dias restantes de plan por tenant
```sql
SELECT t.id, t.name, t.plan,
       t.trial_ends_at,
       s.stripe_status,
       s.ends_at AS subscription_ends_at,
       -- Dias de trial restantes
       CASE
         WHEN t.trial_ends_at IS NOT NULL AND t.trial_ends_at > NOW()
           THEN EXTRACT(DAY FROM t.trial_ends_at - NOW())::int
         WHEN t.trial_ends_at IS NOT NULL
           THEN 0
         ELSE NULL
       END AS dias_trial_restantes,
       -- Dias de suscripcion restantes (ends_at se llena cuando se cancela)
       CASE
         WHEN s.ends_at IS NOT NULL AND s.ends_at > NOW()
           THEN EXTRACT(DAY FROM s.ends_at - NOW())::int
         WHEN s.ends_at IS NOT NULL
           THEN 0
         WHEN s.stripe_status = 'active' AND s.ends_at IS NULL
           THEN NULL  -- activa sin fecha de fin = renovacion automatica
         ELSE NULL
       END AS dias_suscripcion_restantes,
       -- Estado legible
       CASE
         WHEN s.stripe_status = 'active' AND s.ends_at IS NULL THEN 'Activa (auto-renew)'
         WHEN s.stripe_status = 'active' AND s.ends_at > NOW() THEN 'Cancelada, activa hasta ' || s.ends_at::date
         WHEN s.ends_at IS NOT NULL AND s.ends_at <= NOW() THEN 'Expirada'
         WHEN t.trial_ends_at IS NOT NULL AND t.trial_ends_at > NOW() THEN 'En trial'
         WHEN t.trial_ends_at IS NOT NULL AND t.trial_ends_at <= NOW() THEN 'Trial expirado'
         ELSE 'Sin suscripcion'
       END AS estado
FROM tenants t
LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.type = 'default'
ORDER BY t.id;
```

Para un tenant especifico, agregar `WHERE t.id = :tenant_id` o `WHERE t.slug = 'mi-tenant'`.

### Estado de suscripciones
```sql
SELECT t.name AS tenant, t.plan,
       s.type, s.stripe_status, s.stripe_price,
       s.trial_ends_at, s.ends_at
FROM tenants t
LEFT JOIN subscriptions s ON s.tenant_id = t.id
ORDER BY t.id;
```

### Tenants en trial
```sql
SELECT name, slug, plan, trial_ends_at,
       CASE
         WHEN trial_ends_at > NOW() THEN 'activo'
         ELSE 'expirado'
       END AS trial_status,
       trial_ends_at - NOW() AS tiempo_restante
FROM tenants
WHERE trial_ends_at IS NOT NULL
ORDER BY trial_ends_at;
```

### Uso vs limites del plan
```sql
SELECT t.id, t.name, t.plan,
       (SELECT COUNT(*) FROM orders WHERE tenant_id = t.id AND deleted_at IS NULL) AS orders_used,
       (SELECT COUNT(*) FROM customers WHERE tenant_id = t.id) AS customers_used,
       (SELECT COUNT(*) FROM products WHERE tenant_id = t.id AND deleted_at IS NULL) AS products_used,
       (SELECT COUNT(*) FROM branches WHERE tenant_id = t.id) AS branches_used,
       (SELECT COUNT(DISTINCT user_id) FROM tenant_user WHERE tenant_id = t.id) AS users_used
FROM tenants t
ORDER BY t.id;
```

### Referidos
```sql
SELECT rt.name AS referrer, rdt.name AS referred,
       r.converted_at, r.rewarded_at
FROM referrals r
JOIN tenants rt ON r.referrer_tenant_id = rt.id
JOIN tenants rdt ON r.referred_tenant_id = rdt.id
ORDER BY r.created_at DESC;
```

---

## 8. Activity Log & Audit

### Actividad reciente (ultimas 50)
```sql
SELECT a.id, t.name AS tenant,
       a.description, a.event,
       a.subject_type, a.subject_id,
       u.name AS causer,
       a.created_at
FROM activity_log a
LEFT JOIN tenants t ON a.tenant_id = t.id
LEFT JOIN users u ON a.causer_type = 'App\Models\User' AND a.causer_id = u.id
ORDER BY a.created_at DESC
LIMIT 50;
```

### Actividad por tenant (resumen diario)
```sql
SELECT t.name AS tenant,
       a.created_at::date AS fecha,
       COUNT(*) AS actions
FROM activity_log a
JOIN tenants t ON a.tenant_id = t.id
WHERE a.created_at >= NOW() - INTERVAL '7 days'
GROUP BY t.name, a.created_at::date
ORDER BY fecha DESC, actions DESC;
```

---

## 9. Quick Health Checks

### Resumen ejecutivo por tenant
```sql
SELECT t.id, t.name, t.plan, t.is_active,
       t.created_at::date AS creado,
       (SELECT COUNT(*) FROM orders WHERE tenant_id = t.id AND deleted_at IS NULL) AS ordenes,
       (SELECT COALESCE(SUM(total), 0) FROM orders WHERE tenant_id = t.id AND deleted_at IS NULL) AS revenue,
       (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tenant_id = t.id) AS cobrado,
       (SELECT MAX(created_at)::date FROM orders WHERE tenant_id = t.id) AS ultima_orden,
       (SELECT COUNT(DISTINCT user_id) FROM tenant_user WHERE tenant_id = t.id) AS users
FROM tenants t
ORDER BY t.id;
```

### Tables sin tenant_id (posible leak)
```sql
SELECT table_name
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name NOT IN (
    'migrations', 'cache', 'cache_locks', 'sessions', 'jobs',
    'failed_jobs', 'job_batches', 'password_reset_tokens',
    'personal_access_tokens', 'subscription_items'
  )
  AND table_name NOT LIKE 'telescope_%'
GROUP BY table_name
HAVING COUNT(*) FILTER (WHERE column_name = 'tenant_id') = 0
ORDER BY table_name;
```

### Indices existentes en tables principales
```sql
SELECT tablename, indexname, indexdef
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ('orders', 'order_lines', 'payments', 'customers', 'products')
ORDER BY tablename, indexname;
```

---

## Notas

- **Monetary fields**: `decimal(12,2)` — no dividir entre 100
- **SoftDeletes**: `orders`, `items`, `pricing_rules` tienen `deleted_at` — filtrar con `WHERE deleted_at IS NULL`
- **Catalogo global**: `item_categories`, `sizes`, `colors`, removed tienen `tenant_id` nullable (NULL = template global)
- **Status values**: usar strings exactos (`'pending'`, `'confirmed'`, `'in_progress'`, etc.)
- **Timestamps**: PostgreSQL usa timezone — `::date` para truncar, `AT TIME ZONE 'UTC'` para hora local

---

*Ultima actualizacion: 2026-02-25*
