# Test Cases — SaaS Fase 2: Roles y Permisos (RBAC)

**Version:** 1.0
**Date:** 2026-02-20
**Relacionado:** `fase2-rbac-test-plan.md`

---

## Convenciones

- **ID:** TC-RBAC-{numero secuencial}
- **Priority:** P1 (critica), P2 (alta), P3 (media), P4 (baja)
- **Tipo:** Funcional, Negativo, Regresion, E2E
- **Status:** Pendiente, En progreso, Pasado, Fallido, Bloqueado

---

## TC-RBAC-001: Owner tiene todos los permisos

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-001 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RbacTest::test_owner_has_all_permissions`) |
| **Precondiciones** | User con rol `owner` en tenant activo |
| **Steps** | 1. Crear user con rol owner en tenant<br>2. Check que tiene los 15 permisos |
| **Resultado esperado** | 15/15 permisos asignados |
| **Estado** | Pasado |

---

## TC-RBAC-002: Owner puede acceder al panel Filament

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-002 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RbacTest::test_owner_can_access_panel`) |
| **Precondiciones** | User owner con tenant activo |
| **Steps** | 1. Llamar `canAccessPanel()` |
| **Resultado esperado** | Retorna `true` |
| **Estado** | Pasado |

---

## TC-RBAC-003: Admin tiene todos los permisos excepto billing

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-003 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RbacTest::test_admin_has_all_permissions_except_billing`) |
| **Precondiciones** | User con rol `admin` en tenant |
| **Steps** | 1. Check 14 permisos positivos<br>2. Check `billing.manage` denegado |
| **Resultado esperado** | 14/15 permisos, sin `billing.manage` |
| **Estado** | Pasado |

---

## TC-RBAC-004: Sales puede gestionar orders y payments

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RbacTest::test_sales_can_manage_orders_and_payments`) |
| **Precondiciones** | User con rol `sales` en tenant |
| **Steps** | 1. Check: orders.view, orders.create, orders.edit, customers.manage, payments.view, payments.create |
| **Resultado esperado** | 6 permisos asignados |
| **Estado** | Pasado |

---

## TC-RBAC-005: Sales NO puede eliminar orders ni gestionar items

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-005 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RbacTest::test_sales_cannot_delete_orders_or_manage_products`) |
| **Precondiciones** | User con rol `sales` en tenant |
| **Steps** | 1. Check denegados: orders.delete, products.manage, settings.manage, production.view |
| **Resultado esperado** | 4 permisos denegados |
| **Estado** | Pasado |

---

## TC-RBAC-006: Operations puede ver orders y operations

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RbacTest::test_operations_can_view_orders_and_production`) |
| **Precondiciones** | User con rol `operations` en tenant |
| **Steps** | 1. Check: orders.view, production.view, production.mark |
| **Resultado esperado** | 3 permisos asignados |
| **Estado** | Pasado |

---

## TC-RBAC-007: Operations NO puede crear ni editar orders

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-007 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RbacTest::test_operations_cannot_create_or_edit_orders`) |
| **Precondiciones** | User con rol `operations` en tenant |
| **Steps** | 1. Check denegados: orders.create, orders.edit, orders.delete, products.manage, payments.view |
| **Resultado esperado** | 5 permisos denegados |
| **Estado** | Pasado |

---

## TC-RBAC-008: Finance puede ver orders, payments y exportar

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`RbacTest::test_finance_can_view_orders_and_payments`) |
| **Precondiciones** | User con rol `finance` en tenant |
| **Steps** | 1. Check: orders.view, payments.view, reports.export |
| **Resultado esperado** | 3 permisos asignados |
| **Estado** | Pasado |

---

## TC-RBAC-009: Finance NO puede crear ni editar

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-009 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RbacTest::test_finance_cannot_create_or_edit`) |
| **Precondiciones** | User con rol `finance` en tenant |
| **Steps** | 1. Check denegados: orders.create, orders.edit, payments.create, products.manage, settings.manage |
| **Resultado esperado** | 5 permisos denegados |
| **Estado** | Pasado |

---

## TC-RBAC-010: User sin tenant NO accede al panel

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-010 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`RbacTest::test_user_without_tenant_cannot_access_panel`) |
| **Precondiciones** | User creado sin asociar a ningun tenant |
| **Steps** | 1. Llamar `canAccessPanel()` |
| **Resultado esperado** | Retorna `false` |
| **Estado** | Pasado |

---

## TC-RBAC-011: Sales — Sidebar muestra solo recursos permitidos

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-011 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como `sales@test.com` / `password` |
| **Steps** | 1. Iniciar sesion<br>2. Observar sidebar |
| **Resultado esperado** | Visible: Escritorio, Orders, Customers<br>Oculto: Items, Operations, Precios, Locationes, Actividad |
| **Estado** | Pasado |

---

## TC-RBAC-012: Sales — Acciones de order visibles/ocultas

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-012 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como sales, navegar a lista de orders |
| **Steps** | 1. Ir a Orders<br>2. Check actions available en un order |
| **Resultado esperado** | Visible: Editar, Duplicar, Link de Payment, Messaging<br>Oculto: Eliminar, Restaurar |
| **Estado** | Pasado |

---

## TC-RBAC-013: Sales — Crear order E2E via wizard

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-013 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como sales, datos de test available |
| **Steps** | 1. Ir a Orders > Crear<br>2. Step 1 (Customer): tel 5551112222, name "Customer Sales Test", email customersales@test.com<br>3. Step 2 (Items): Product Basica Colors, Mediana, Blanco, qty 15<br>4. Step 3 (Entrega): Recoger en location > Location Principal<br>5. Step 4 (Extras): Nota "Order de test creado por rol sales"<br>6. Step 5 (Resumen): Check totales<br>7. Click Crear |
| **Resultado esperado** | Order creado, redirige a vista del order con estado "Recibido", subtotal $2,625 |
| **Estado** | Pasado |

---

## TC-RBAC-014: Operations — Sidebar muestra solo recursos permitidos

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-014 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como user con rol `operations` |
| **Steps** | 1. Iniciar sesion<br>2. Observar sidebar |
| **Resultado esperado** | Visible: Escritorio, Orders, Operations<br>Oculto: Items, Customers, Precios, Locationes, Actividad |
| **Estado** | Pending |

---

## TC-RBAC-015: Operations — NO puede crear orders

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-015 |
| **Prioridad** | P2 |
| **Tipo** | Negativo (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como user con rol `operations` |
| **Steps** | 1. Ir a Orders<br>2. Check que no existe boton "Crear" |
| **Resultado esperado** | Boton "Crear" no visible |
| **Estado** | Pending |

---

## TC-RBAC-016: Operations — Puede marcar items como producidos

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-016 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como operations, order en estado "En Operations" |
| **Steps** | 1. Ir a Operations<br>2. Buscar order<br>3. Marcar item como producido |
| **Resultado esperado** | Toggle funciona, item marcado como producido |
| **Estado** | Pending |

---

## TC-RBAC-017: Finance — Sidebar muestra solo recursos permitidos

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-017 |
| **Prioridad** | P2 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como user con rol `finance` |
| **Steps** | 1. Iniciar sesion<br>2. Observar sidebar |
| **Resultado esperado** | Visible: Escritorio, Orders<br>Oculto: Items, Customers, Operations, Precios, Locationes, Actividad |
| **Estado** | Pending |

---

## TC-RBAC-018: Finance — NO puede crear ni editar orders

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-018 |
| **Prioridad** | P2 |
| **Tipo** | Negativo (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como finance, navegar a orders |
| **Steps** | 1. Ir a Orders<br>2. Check que no hay boton Crear<br>3. Check que no hay accion Editar en un order |
| **Resultado esperado** | Sin boton Crear, sin accion Editar |
| **Estado** | Pending |

---

## TC-RBAC-019: Owner — Crear order E2E via wizard

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-019 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner |
| **Steps** | Mismos pasos que TC-RBAC-013 |
| **Resultado esperado** | Order creado exitosamente |
| **Estado** | Pending |

---

## TC-RBAC-020: Tenant Switcher visible con multiples tenants

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-020 |
| **Prioridad** | P3 |
| **Tipo** | Funcional (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | User asociado a 2+ tenants activos |
| **Steps** | 1. Login<br>2. Observar header junto al menu de user |
| **Resultado esperado** | Dropdown de tenant switcher visible |
| **Estado** | Pending |

---

## TC-RBAC-021: Tenant Switcher oculto con un solo tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-021 |
| **Prioridad** | P3 |
| **Tipo** | Negativo (UI) |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | User asociado a 1 solo tenant |
| **Steps** | 1. Login<br>2. Observar header |
| **Resultado esperado** | No se muestra dropdown de tenant switcher |
| **Estado** | Pasado |

---

## TC-RBAC-022: Activity Log registra cambios en order

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-022 |
| **Prioridad** | P3 |
| **Tipo** | Funcional |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como owner, crear o editar un order |
| **Steps** | 1. Crear/editar un order<br>2. Ir a Actividad en sidebar<br>3. Check registration del cambio |
| **Resultado esperado** | Entrada en log con model, evento, user, old/new values |
| **Estado** | Pending |

---

## TC-RBAC-023: Acceso directo por URL denegado para rol sin permiso

| Campo | Valor |
|-------|-------|
| **ID** | TC-RBAC-023 |
| **Prioridad** | P2 |
| **Tipo** | Security |
| **Automatizado** | No (manual, browser) |
| **Precondiciones** | Login como sales |
| **Steps** | 1. Navegar directamente a `/admin/products`<br>2. Navegar directamente a `/admin/production-board` |
| **Resultado esperado** | 403 Forbidden o redirect a dashboard |
| **Estado** | Pending |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 13 |
| Pending | 10 |
| Fallido | 0 |
| Bloqueado | 0 |
| **Total** | **23** |
