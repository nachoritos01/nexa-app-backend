# Test Cases — SaaS Fase 7 PR2: CFDI + Corte de Caja + Cola de Operations (7.3-7.5)

**Version:** 1.0
**Date:** 2026-02-22
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-PM-{numero} (PM = Premium, continua desde PR1)
- **Priority:** P1 / P2 / P3 / P4
- **Tipo:** Funcional / Negativo / Regresion / E2E / Security
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 7.3 — Facturacion CFDI

### TC-PM-016: Invoice se crea correctamente para orden

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-016 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CfdiTest::test_invoice_created_correctly_for_order`) |
| **Precondiciones** | Orden con subtotal=1000, tenant plan Pro |
| **Steps** | 1. Crear orden<br>2. CfdiService::createInvoice() con RFC y datos fiscales<br>3. Check invoice creado |
| **Resultado esperado** | Invoice draft, RFC=XAXX010101000, tax=160 (16%), total=1160, relacion order+tenant |
| **Estado** | Pasado |

### TC-PM-017: Solo plan Pro puede facturar

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-017 |
| **Prioridad** | P1 |
| **Tipo** | Negativo |
| **Automatizado** | Si (`CfdiTest::test_only_pro_plan_can_invoice`) |
| **Precondiciones** | Tenant con plan Starter |
| **Steps** | 1. Check plan es starter<br>2. Check que plan no es pro |
| **Resultado esperado** | Feature gate: plan starter no puede acceder a facturacion |
| **Estado** | Pasado |

### TC-PM-018: Invoice tiene relacion con order y tenant

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-018 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CfdiTest::test_invoice_has_relation_with_order_and_tenant`) |
| **Precondiciones** | Invoice creado via CfdiService |
| **Steps** | 1. Crear invoice<br>2. Check $invoice->order->id<br>3. Check $invoice->tenant->id |
| **Resultado esperado** | Relationships Eloquent correctas |
| **Estado** | Pasado |

---

## 7.4 — Corte de Caja

### TC-PM-019: Cash register accesible con permiso

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-019 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CashRegisterTest::test_cash_register_accessible_with_permission`) |
| **Precondiciones** | User owner con permiso payments.view |
| **Steps** | 1. GET /admin/cash-register |
| **Resultado esperado** | Status 200 |
| **Estado** | Pasado |

### TC-PM-020: Totales por metodo correctos

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-020 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CashRegisterTest::test_totals_by_method_correct`) |
| **Precondiciones** | Payments: cash $500, transferencia $300 hoy |
| **Steps** | 1. Crear payments<br>2. Query sum por metodo hoy |
| **Resultado esperado** | Cash=500, Transfer=300 |
| **Estado** | Pasado |

### TC-PM-021: Filtro de fechas funciona

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-021 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`CashRegisterTest::test_date_filter_works`) |
| **Precondiciones** | Payment hoy $100 + payment hace 10 dias $200 |
| **Steps** | 1. Filtrar solo hoy → $100<br>2. Filtrar ultimos 30 dias → $300 |
| **Resultado esperado** | Filtros retornan totales correctos |
| **Estado** | Pasado |

### TC-PM-022: Preset "Hoy" (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-022 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin, payments del dia existentes |
| **Steps** | 1. Navegar a /admin/cash-register<br>2. Click preset "Hoy"<br>3. Check cards por metodo<br>4. Check gran total<br>5. Check comparativo vs dia anterior |
| **Resultado esperado** | Cash $300 (1 payment), -70% vs anterior |
| **Estado** | Pasado |

### TC-PM-023: Preset "Esta semana" (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-023 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Payments de esta semana existentes |
| **Steps** | 1. Click preset "Esta semana"<br>2. Check totales actualizados |
| **Resultado esperado** | Cash $1,300 (2 payments), +100% vs anterior |
| **Estado** | Pasado |

### TC-PM-024: Preset "Este mes" (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-024 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Payments del mes existentes |
| **Steps** | 1. Click preset "Este mes"<br>2. Check rango de fechas correcto (01/02 - 22/02)<br>3. Check totales |
| **Resultado esperado** | $1,300 con rango correcto |
| **Estado** | Pasado |

---

## 7.5 — Cola de Operations con Prioridades

### TC-PM-025: Priority default es Normal

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-025 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderPriorityTest::test_priority_default_is_normal`) |
| **Precondiciones** | Crear orden sin especificar prioridad |
| **Steps** | 1. Order::factory()->create()<br>2. Check priority == OrderPriority::Normal |
| **Resultado esperado** | Default: Normal (3) |
| **Estado** | Pasado |

### TC-PM-026: ProductionBoard ordena por prioridad

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-026 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderPriorityTest::test_production_board_sorts_by_priority`) |
| **Precondiciones** | Ordenes con prioridad Low y Urgent |
| **Steps** | 1. Crear orden Low y Urgent<br>2. Query ORDER BY priority ASC<br>3. Check Urgent antes de Low |
| **Resultado esperado** | Urgent (1) aparece antes de Low (4) |
| **Estado** | Pasado |

### TC-PM-027: OrderPriority enum valores correctos

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-027 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | Si (`OrderPriorityTest::test_priority_enum_has_correct_values`) |
| **Precondiciones** | Ninguna |
| **Steps** | 1. Check 5 valores: Urgent=1, High=2, Normal=3, Low=4, Deferred=5<br>2. Check labels y colors |
| **Resultado esperado** | Urgent label="Urgente", color="danger" |
| **Estado** | Pasado |

### TC-PM-028: ProductionBoard renderiza correctamente (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-028 |
| **Prioridad** | P1 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Ordenes confirmadas con items |
| **Steps** | 1. Navegar a /admin/production-board<br>2. Check table carga sin error<br>3. Check group title incluye prioridad: "[Normal] #46 — Customer Test — 0/1 items"<br>4. Check column Prioridad con badge "Normal"<br>5. Check columns: Diseno, Item, Talla, Color, Qty, Notas, Producido |
| **Resultado esperado** | Table renderiza correctamente con sort por prioridad |
| **Estado** | Pasado |

### TC-PM-029: Priority select en Order Wizard Step 4 (manual UI)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-029 |
| **Prioridad** | P2 |
| **Tipo** | E2E |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | Login como admin |
| **Steps** | 1. Navegar a /admin/orders/create<br>2. Avanzar a Step 4 (Extras)<br>3. Check select de prioridad con 5 opciones<br>4. Check opciones: Urgente, Alta, Normal, Baja, Diferida |
| **Resultado esperado** | Select con 5 opciones, default Normal |
| **Estado** | Pasado |

### TC-PM-030: ProductionBoard SQL sort corregido (regresion)

| Campo | Valor |
|-------|-------|
| **ID** | TC-PM-030 |
| **Prioridad** | P1 |
| **Tipo** | Regresion |
| **Automatizado** | No (manual, Chrome DevTools MCP) |
| **Precondiciones** | PostgreSQL como BD |
| **Steps** | 1. Navegar a /admin/production-board<br>2. Check que no hay error SQL |
| **Resultado esperado** | Pagina carga sin error (bug order.priority → orders.priority corregido) |
| **Estado** | Pasado |

---

## Resumen de cobertura

| Estado | Cantidad |
|--------|----------|
| Pasado | 15 |
| Total | **15** |

### Por tipo

| Tipo | Cantidad |
|------|----------|
| Automatizado (PHPUnit) | 9 |
| Manual (Chrome DevTools MCP) | 6 |
