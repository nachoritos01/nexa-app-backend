# Test Cases — Security: Fix Cross-Tenant Data Leaks

**Date:** 2026-02-24
**Branch:** security/fix-cross-tenant-data-leaks

## Datos de test

| Tenant | ID | Plan | User | Email |
|--------|----|------|---------|-------|
| SaaS Template | 1 | pro | Admin | admin@example.com |
| mixtore | 2 | starter | Jose Ignacio | jose@saas-template.com |

| Orden | Tenant | Customer | Total | Payment | Status |
|-------|--------|---------|-------|------|--------|
| #1 | 1 | Jose Ignacio | $200 | $100 (cash) | en_operations |

---

## TC-SEC-01: Tenant 2 no ve datos de Tenant 1 en Rentabilidad (Web)

| Campo | Valor |
|-------|-------|
| **Tipo** | Manual |
| **Prioridad** | P1 |
| **Precondicion** | Tenant 1 tiene orden #1 ($200). Tenant 2 no tiene ordenes. |

### Steps

1. Navegar a `http://localhost:8000/admin/login`
2. Login como `jose@saas-template.com` (tenant 2 — mixtore)
3. En dashboard, scroll al widget "Rentabilidad por Item"
4. Check contenido del widget

### Resultado esperado

- Reporte Mensual: Orders del mes = 0, Ingresos = $0.00
- Top 5 Items: "Sin datos este mes"
- Top 5 Customers: "Sin datos este mes"
- Rentabilidad por Item: "Sin datos para este periodo"

### Resultado actual

PASS — Todos los widgets muestran datos vacios para tenant 2.

---

## TC-SEC-02: Tenant 1 ve sus propios datos en Rentabilidad (Web)

| Campo | Valor |
|-------|-------|
| **Tipo** | Manual |
| **Prioridad** | P1 |
| **Precondicion** | Tenant 1 tiene orden #1 ($200) con 1 Product Basica Blanca. |

### Steps

1. Logout de tenant 2
2. Login como `admin@example.com` (tenant 1 — SaaS Template)
3. En dashboard, scroll al widget "Rentabilidad por Item"
4. Check contenido del widget

### Resultado esperado

- Reporte Mensual: Orders del mes = 1, Ingresos = $200.00
- Top 5 Items: Product Basica Blanca — Qty 1, $200.00
- Rentabilidad por Item: Product Basica Blanca — 1 unidad, $200.00, 100%

### Resultado actual

PASS — Todos los widgets muestran datos correctos exclusivamente de tenant 1.

---

## TC-SEC-03: Ruta legacy GET /api/orders eliminada

| Campo | Valor |
|-------|-------|
| **Tipo** | Manual |
| **Prioridad** | P1 |
| **Precondicion** | Servidor corriendo en localhost:8000 |

### Steps

1. Navegar a `http://localhost:8000/api/orders`

### Resultado esperado

HTTP 404 Not Found

### Resultado actual

PASS — Retorna 404 NOT FOUND.

---

## TC-SEC-04: V1 updateStatus requiere autenticacion

| Campo | Valor |
|-------|-------|
| **Tipo** | Manual |
| **Prioridad** | P1 |
| **Precondicion** | Orden #1 existe |

### Steps

1. Ejecutar request sin token de autenticacion:
```
PATCH /api/v1/orders/1/status
Content-Type: application/json
Body: {"status": "confirmado"}
```

### Resultado esperado

HTTP 401 Unauthenticated

### Resultado actual

PASS — Retorna `{"message": "Unauthenticated."}` con status 401.

---

## TC-SEC-05: App mobile — Tenant 1 ve sus ordenes

| Campo | Valor |
|-------|-------|
| **Tipo** | Manual |
| **Prioridad** | P1 |
| **Precondicion** | App mobile corriendo en localhost:8081, API redirigida a localhost:8000 |

### Steps

1. Abrir `http://localhost:8081/login`
2. Login como `admin@example.com` (tenant 1, plan pro)
3. Navegar a tab "Orders"

### Resultado esperado

- Lista muestra orden #1: Jose Ignacio, $200, En operations, Saldo $100
- Request `GET /api/v1/orders` retorna 200

### Resultado actual

PASS — Orden visible correctamente. Network request retorna 200.

---

## TC-SEC-06: App mobile — Tenant 2 no accede a API v1

| Campo | Valor |
|-------|-------|
| **Tipo** | Manual |
| **Prioridad** | P1 |
| **Precondicion** | Logout previo de tenant 1 |

### Steps

1. Login como `jose@saas-template.com` (tenant 2, plan starter)
2. Check requests de red a `/api/v1/*`

### Resultado esperado

- `POST /api/auth/login` retorna 200 con tenant mixtore (id 2, plan starter)
- `GET /api/v1/orders` retorna 403 (plan starter sin acceso a API v1)
- `GET /api/v1/dashboard/stats` retorna 403

### Resultado actual

PASS — Login retorna datos correctos de tenant 2. Todos los requests a API v1 retornan 403 con `{"error": "API access requires the Pro plan...", "code": "PLAN_UPGRADE_REQUIRED"}`. La app mobile maneja el 403 correctamente mostrando estados vacios ("No hay orders", "Sin datos"). El middleware `EnsureApiAccess` bloquea el acceso a planes no-pro como comportamiento esperado.

---

## TC-SEC-07: Tests automatizados pasan

| Campo | Valor |
|-------|-------|
| **Tipo** | Automatizado |
| **Prioridad** | P1 |

### Command

```bash
composer test
```

### Resultado esperado

278 tests, 714 assertions, 0 failures

### Resultado actual

PASS — `Tests: 278 passed (714 assertions) — Duration: 24.99s`

---

## TC-SEC-08: PHPStan sin errores nuevos

| Campo | Valor |
|-------|-------|
| **Tipo** | Automatizado |
| **Prioridad** | P2 |

### Command

```bash
./vendor/bin/phpstan analyse --memory-limit=512M
```

### Resultado esperado

Solo errores pre-existentes (~48 errores de pivot properties, Livewire computed). Sin errores nuevos en files modificados.

### Resultado actual

PASS — Mismos ~48 errores pre-existentes. Ninguno en files nuevos o modificados.

---

## Tests automatizados nuevos/modificados

| File | Tests | Descripcion |
|---------|-------|-------------|
| tests/Feature/Api/V1/OrderApiV1Test.php | +2 | `test_update_status_transitions_order`, `test_update_status_requires_auth` |
| tests/Feature/Api/QuoteApiTest.php | +2, -3 | `test_legacy_list_route_removed`, `test_legacy_delete_route_removed`; eliminados: index, show, delete |
| tests/Feature/Api/OrderApiTest.php | -8 (eliminado) | File completo eliminado (rutas legacy ya no existen) |
