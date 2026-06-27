# Test Cases — Order Form Responsive + Delivery Step Improvements

**Version:** 1.0
**Date:** 2026-03-03
**Relacionado:** `test-plan.md`

---

## Convenciones

- **ID:** TC-ORD-{numero}
- **Priority:** P1 / P2 / P3
- **Tipo:** Funcional / Visual / Regresion
- **Status:** Pendiente / Pasado / Fallido / Bloqueado

---

## 1. Responsive Step 2 — Grid de Items

### TC-ORD-001: Mobile (< 640px) — campos stacked

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-001 |
| **Prioridad** | P1 |
| **Tipo** | Visual |
| **Automatizado** | No (verificacion visual MCP) |
| **Precondiciones** | Viewport 390x844 |
| **Steps** | 1. Navegar a `/admin/orders/create`<br>2. Avanzar a Step 2<br>3. Check layout del repeater |
| **Resultado esperado** | Todos los campos en 1 column, sin solapamiento |
| **Estado** | Pasado |

### TC-ORD-002: Tablet (>= 640px) — pares de campos

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-002 |
| **Prioridad** | P1 |
| **Tipo** | Visual |
| **Automatizado** | No |
| **Precondiciones** | Viewport 768x1024 |
| **Steps** | 1. Navegar a `/admin/orders/create`<br>2. Avanzar a Step 2<br>3. Check layout: Item full, Talla/Color en par, Cantidad/Precio en par |
| **Resultado esperado** | Grid de 2 columns con campos emparejados |
| **Estado** | Pasado |

### TC-ORD-003: Desktop (>= 1024px) — layout original preservado

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-003 |
| **Prioridad** | P1 |
| **Tipo** | Visual / Regresion |
| **Automatizado** | No |
| **Precondiciones** | Viewport 1280x800 |
| **Steps** | 1. Navegar a `/admin/orders/create`<br>2. Avanzar a Step 2<br>3. Check layout de 4 columns |
| **Resultado esperado** | Item (2 cols), Talla, Color en fila 1; Precio, Diseno (2 cols), Notas en fila 2 |
| **Estado** | Pasado |

---

## 2. Quoter Mobile Fix

### TC-ORD-004: Botones +/- funcionan en mobile

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-004 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No (verificacion MCP) |
| **Precondiciones** | `/quote` en viewport mobile |
| **Steps** | 1. Navegar a `/quote`<br>2. Click boton "+"<br>3. Check que cantidad incrementa a 2<br>4. Click boton "+" otra vez<br>5. Check cantidad = 3 |
| **Resultado esperado** | Cantidad se actualiza en el DOM al hacer click |
| **Estado** | Pasado |

### TC-ORD-005: No hay warnings de Alpine duplicado en consola

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-005 |
| **Prioridad** | P2 |
| **Tipo** | Regresion |
| **Automatizado** | No |
| **Precondiciones** | Consola del navegador abierta |
| **Steps** | 1. Navegar a `/quote`<br>2. Revisar consola |
| **Resultado esperado** | Sin warning "Detected multiple instances of Alpine running" |
| **Estado** | Pasado |

---

## 3. Origin Branch Selector

### TC-ORD-006: 1 location — select oculto, auto-seleccionada

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-006 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Tenant con 1 sola location activa |
| **Steps** | 1. Crear order<br>2. Ir a Step 3<br>3. Seleccionar "Envio a domicilio" |
| **Resultado esperado** | Select "Location de Origen" no se muestra; cotizador usa la unica location |
| **Estado** | Pasado |

### TC-ORD-007: 2+ locationes — select visible con opciones

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-007 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Tenant con 2+ locationes activas |
| **Steps** | 1. Crear order<br>2. Ir a Step 3<br>3. Seleccionar "Envio a domicilio"<br>4. Check select "Location de Origen" visible |
| **Resultado esperado** | Select muestra todas las locationes, primera pre-seleccionada |
| **Estado** | Pasado |

### TC-ORD-008: Cotizacion usa location de origen seleccionada

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-008 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Tenant con 2 locationes (GDL y CDMX) |
| **Steps** | 1. Seleccionar "Location CDMX" como origen<br>2. Llenar direccion destino en Merida<br>3. Cotizar envio<br>4. Check precios |
| **Resultado esperado** | Precios reflejan origen CDMX ($180 DHL) vs GDL ($185 DHL) |
| **Estado** | Pasado |

### TC-ORD-009: Pickup muestra ambas locationes

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-009 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Tenant con 2+ locationes |
| **Steps** | 1. Step 3 con "Recoger en location"<br>2. Check select de location |
| **Resultado esperado** | Ambas locationes available para pickup |
| **Estado** | Pasado |

---

## 4. Customer Saved Addresses

### TC-ORD-010: Selector muestra direcciones guardadas + "Nueva direccion"

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-010 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Customer con 2 direcciones guardadas (Casa default, Oficina) |
| **Steps** | 1. Crear order con telefono del customer<br>2. Ir a Step 3, seleccionar Envio<br>3. Check dropdown "Direccion del customer" |
| **Resultado esperado** | 3 opciones: "⭐ Casa: ...", "Oficina: ...", "+ Nueva direccion" |
| **Estado** | Pasado |

### TC-ORD-011: Auto-fill al seleccionar direccion guardada

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-011 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Direccion "Casa" con datos completos |
| **Steps** | 1. Seleccionar "Casa" del dropdown<br>2. Check campos auto-llenados |
| **Resultado esperado** | Direccion, Colonia, Ciudad, Estado, C.P. llenados con datos de "Casa" |
| **Estado** | Pasado |

### TC-ORD-012: Cambiar entre direcciones actualiza campos

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-012 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Steps** | 1. Seleccionar "Casa" — campos se llenan<br>2. Cambiar a "Oficina" — campos se actualizan<br>3. Cambiar a "Nueva direccion" — campos se limpian |
| **Resultado esperado** | Campos reflejan la direccion seleccionada en cada cambio |
| **Estado** | Pasado |

### TC-ORD-013: Checkbox "Guardar" visible solo para nueva direccion

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-013 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Steps** | 1. Seleccionar "Casa" — check checkbox oculto<br>2. Seleccionar "Nueva direccion" — check checkbox visible |
| **Resultado esperado** | Checkbox solo aparece cuando selected_address_id === 'new' |
| **Estado** | Pasado |

### TC-ORD-014: Customer sin direcciones — solo "Nueva direccion"

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-014 |
| **Prioridad** | P2 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Customer sin direcciones guardadas |
| **Steps** | 1. Crear order con customer sin direcciones<br>2. Step 3, Envio |
| **Resultado esperado** | Dropdown solo muestra "+ Nueva direccion" |
| **Estado** | Pasado |

### TC-ORD-015: Guardar nueva direccion persiste en CustomerAddress

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-015 |
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Automatizado** | No |
| **Precondiciones** | Customer existente, "Nueva direccion" seleccionada |
| **Steps** | 1. Llenar nueva direccion<br>2. Marcar "Guardar esta direccion"<br>3. Completar y crear order<br>4. Check en DB que `customer_addresses` tiene nuevo registration |
| **Resultado esperado** | Nueva direccion guardada con datos correctos, is_default=true si es primera |
| **Estado** | Pasado |

---

## 5. PHPStan

### TC-ORD-016: Analisis estatico sin errores

| Campo | Valor |
|-------|-------|
| **ID** | TC-ORD-016 |
| **Prioridad** | P1 |
| **Tipo** | Regresion |
| **Automatizado** | Si (`composer analyse`) |
| **Steps** | 1. Ejecutar `composer analyse` |
| **Resultado esperado** | 0 errores PHPStan nivel 5 |
| **Estado** | Pasado |
