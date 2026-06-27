# Test Cases — Bugfix: Customer Address Label Column Too Short

**Date:** 2026-03-04

## TC-ADDR-01: Crear order con distrito largo (>20 chars) — bug original

| Campo | Valor |
|-------|-------|
| **Prioridad** | P1 |
| **Tipo** | Regresion |
| **Precondiciones** | User admin logueado, migracion aplicada |

### Steps

1. Ir a `/admin/orders/create`
2. Buscar customer por telefono `9991234567` — autocompletado
3. Avanzar a Items — seleccionar Product Basica Colors, Mediana, Blanco
4. Avanzar a Entrega — seleccionar "Envio a domicilio"
5. Llenar direccion: Calle 23 #344, Colonia "Salvador Alvarado Sur" (21 chars), Merida, Yucatan, 97199
6. Marcar "Guardar esta direccion para el customer"
7. Avanzar hasta Resumen y hacer clic en "Crear"

### Resultado esperado

Order se crea exitosamente. Redirige a pagina de edicion del order.

### Resultado actual

**PASS** — Order #20 creado. Redirigido a `/admin/orders/20/edit`.

---

## TC-ADDR-02: Crear order con envio cotizado y guardar direccion

| Campo | Valor |
|-------|-------|
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Precondiciones** | Customer 9991234567 existente |

### Steps

1. Ir a `/admin/orders/create`
2. Buscar customer `9991234567` — autocompletado como "Customer Test"
3. Avanzar a Items — seleccionar Product Basica Blanca, Grande, Blanco
4. Avanzar a Entrega — seleccionar "Envio a domicilio"
5. Llenar: Calle 50 #200, Colonia "Centro", Merida, Yucatan, 97000
6. Marcar "Guardar esta direccion para el customer"
7. Clic en "Cotizar Envio" — se abre modal con opciones
8. Seleccionar DHL Economy Select Domestic ($185.00, 1-4 dias)
9. Clic en "Seleccionar"
10. Check que costo de envio se lleno ($185) y tiempo estimado (1-4 dias)
11. Avanzar hasta Resumen — check total incluye envio
12. Clic en "Crear"

### Resultado esperado

- Modal muestra opciones de envio de multiples carriers
- Costo y tiempo se autocompletar al seleccionar opcion
- Order se crea con total = subtotal + envio
- Direccion se guarda en `customer_addresses`

### Resultado actual

**PASS** — Order #21 creado. Total $385 ($200 + $185 envio). Direccion guardada visible como "Centro: Calle 50 #200, Centro, Merida" en dropdown.

---

## TC-ADDR-03: Reutilizar direccion guardada en nuevo order

| Campo | Valor |
|-------|-------|
| **Prioridad** | P1 |
| **Tipo** | Funcional |
| **Precondiciones** | Customer 9991234567 tiene direccion guardada de TC-ADDR-02 |

### Steps

1. Ir a `/admin/orders/create`
2. Buscar customer `9991234567` — autocompletado
3. Avanzar a Items — seleccionar Product Basica Negra, Mediana, Negro (x20)
4. Avanzar a Entrega — seleccionar "Envio a domicilio"
5. En dropdown "Direccion del customer" check que aparece la direccion guardada
6. Seleccionar "Centro: Calle 50 #200, Centro, Merida"
7. Check que campos se autocompletar (direccion, colonia, ciudad, estado, CP)
8. Avanzar hasta Resumen y clic en "Crear"

### Resultado esperado

- Dropdown muestra direccion guardada con icono estrella
- Al seleccionar, todos los campos se llenan automaticamente
- Order se crea sin errores

### Resultado actual

**PASS** — Order #22 creado. Direccion autocompletada correctamente: Calle 50 #200, Centro, Merida, Yucatan, 97000. Total $3,500.

---

## Resumen de resultados

| Test Case | Estado | Notas |
|-----------|--------|-------|
| TC-ADDR-01 | PASS | Bug original corregido — distrito >20 chars ya no causa truncation |
| TC-ADDR-02 | PASS | Cotizador + guardar direccion funcionan correctamente |
| TC-ADDR-03 | PASS | Reutilizacion de direccion guardada con autocompletado |
