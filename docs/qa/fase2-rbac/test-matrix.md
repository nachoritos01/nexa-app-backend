# Test Matrix — SaaS Fase 2: Roles y Permisos (RBAC)

**Version:** 1.0
**Date:** 2026-02-20
**Relacionado:** `fase2-rbac-test-plan.md`, `fase2-rbac-test-cases.md`

---

## 1. Matriz de Permisos por Rol (Automatizada)

Validada por `tests/Feature/RbacTest.php` — 10 tests.

| Permiso | owner | admin | sales | operations | finance |
|---------|:-----:|:-----:|:------:|:----------:|:------------:|
| orders.view | PASS | PASS | PASS | PASS | PASS |
| orders.create | PASS | PASS | PASS | DENY | DENY |
| orders.edit | PASS | PASS | PASS | DENY | DENY |
| orders.delete | PASS | PASS | DENY | DENY | DENY |
| production.view | PASS | PASS | DENY | PASS | DENY |
| production.mark | PASS | PASS | DENY | PASS | DENY |
| products.manage | PASS | PASS | DENY | DENY | DENY |
| customers.manage | PASS | PASS | PASS | DENY | DENY |
| payments.view | PASS | PASS | PASS | DENY | PASS |
| payments.create | PASS | PASS | PASS | DENY | DENY |
| pricing.manage | PASS | PASS | DENY | DENY | DENY |
| reports.export | PASS | PASS | DENY | DENY | PASS |
| settings.manage | PASS | PASS | DENY | DENY | DENY |
| users.manage | PASS | PASS | DENY | DENY | DENY |
| billing.manage | PASS | DENY | DENY | DENY | DENY |
| **Total** | **15/15** | **14/15** | **6/15** | **3/15** | **3/15** |

Leyenda: PASS = permiso concedido y verificado, DENY = permiso denegado y verificado.

---

## 2. Matriz de Sidebar (UI Manual)

Items del sidebar visibles por rol.

| Recurso Sidebar | owner | admin | sales | operations | finance |
|-----------------|:-----:|:-----:|:------:|:----------:|:------------:|
| Escritorio | OK | - | OK | - | - |
| Orders | OK | - | OK | - | - |
| Customers | OK | - | OK | - | - |
| Items | OK | - | N/A | - | - |
| Operations | OK | - | N/A | - | - |
| Precios | OK | - | N/A | - | - |
| Locationes | OK | - | N/A | - | - |
| Actividad | OK | - | N/A | - | - |

Leyenda: OK = probado y correcto, N/A = verificado que NO aparece, `-` = pendiente de probar.

---

## 3. Matriz de Acciones en Orders (UI Manual)

Acciones available en la table de orders por rol.

| Accion | Permiso requerido | owner | admin | sales | operations | finance |
|--------|-------------------|:-----:|:-----:|:------:|:----------:|:------------:|
| Editar | orders.edit | - | - | OK | - | - |
| Duplicar | orders.create | - | - | OK | - | - |
| Confirmar | orders.edit | - | - | - | - | - |
| Iniciar operations | orders.edit | - | - | - | - | - |
| Marcar listo | orders.edit | - | - | - | - | - |
| Entregar | orders.edit | - | - | - | - | - |
| Generar etiqueta | orders.edit | - | - | - | - | - |
| Link de Payment | payments.create | - | - | OK | - | - |
| Messaging | (ninguno) | - | - | OK | - | - |
| Eliminar | orders.delete | - | - | N/A | - | - |
| Restaurar | orders.delete | - | - | N/A | - | - |

Leyenda: OK = probado y visible, N/A = probado y oculto (correcto), `-` = pendiente.

---

## 4. Matriz E2E — Crear Order (Wizard)

Flujo completo del wizard de creacion de order.

| Step | Descripcion | owner | admin | sales | operations | finance |
|------|-------------|:-----:|:-----:|:------:|:----------:|:------------:|
| 1 | Customer (telefono, name, email) | - | - | OK | N/A | N/A |
| 2 | Items (item, talla, color, qty) | - | - | OK | N/A | N/A |
| 3 | Entrega (location o envio) | - | - | OK | N/A | N/A |
| 4 | Extras (notas, adjuntos, anticipo) | - | - | OK | N/A | N/A |
| 5 | Resumen (check totales) | - | - | OK | N/A | N/A |
| Submit | Crear order | - | - | OK | N/A | N/A |

Leyenda: OK = probado E2E, N/A = rol sin permiso (no aplica), `-` = pendiente.

---

## 5. Matriz de Funcionalidades Transversales

| Funcionalidad | owner | admin | sales | operations | finance | sin tenant |
|---------------|:-----:|:-----:|:------:|:----------:|:------------:|:----------:|
| canAccessPanel | PASS | - | - | - | - | PASS (false) |
| Tenant Switcher (1 tenant) | OK | - | - | - | - | N/A |
| Tenant Switcher (2+ tenants) | - | - | - | - | - | N/A |
| Activity Log visible | - | - | N/A | - | - | N/A |
| Acceso directo por URL denegado | N/A | N/A | - | - | - | N/A |

---

## 6. Resumen de Cobertura

| Matriz | Celdas totales | Probadas | Pendings | Cobertura |
|--------|:--------------:|:--------:|:----------:|:---------:|
| Permisos por rol | 75 | 75 | 0 | 100% |
| Sidebar | 40 | 16 | 24 | 40% |
| Acciones orders | 55 | 8 | 47 | 15% |
| Wizard E2E | 30 | 8 | 22 | 27% |
| Transversales | 30 | 4 | 26 | 13% |
| **Total** | **230** | **111** | **119** | **48%** |

> **Note:** La cobertura de permisos (la mas critica) esta al 100% via tests automatizados.
> Las tests de UI pendientes son para roles que aun no tienen user de test creado.
