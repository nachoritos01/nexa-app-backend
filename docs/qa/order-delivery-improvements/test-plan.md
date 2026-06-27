# Test Plan — Order Form Responsive + Delivery Step Improvements

**Version:** 1.0
**Date:** 2026-03-03
**Feature:** Responsive order form, quoter mobile fix, origin branch selector, customer saved addresses
**Branch:** bugfix/order-form-responsive-and-quoter-mobile
**PR:** #64

## 1. Objetivo

Validar que el formulario de creacion de orders es responsivo en mobile/tablet/desktop, que el cotizador publico funciona en mobile, y que el Step 3 "Entrega" permite seleccionar location de origen para envios y reutilizar direcciones guardadas del customer.

## 2. Alcance

### En alcance

| Area | Descripcion |
|------|-------------|
| Responsive Step 2 | Grid del repeater de items con breakpoints responsive (1/2/4 columns) |
| Quoter mobile fix | Botones +/- de cantidad funcionan en `/quote` (fix duplicate Alpine.js) |
| Origin branch selector | Select de location de origen cuando tenant tiene 2+ locationes |
| Customer addresses | Dropdown de direcciones guardadas con auto-fill y opcion de guardar nueva |
| Save address | Checkbox para persistir nueva direccion en `CustomerAddress` al crear order |

### Fuera de alcance

- Edicion de orders existentes (usa mismo schema, pero no se testa explicitamente)
- Direcciones en portal del customer (solo admin panel)
- Performance de la API de Envia.com

## 3. Estrategia de tests

| Tipo | Herramienta | Cobertura |
|------|-------------|-----------|
| Manual (MCP) | Chrome DevTools via MCP | Responsive layout, address selector, origin branch |
| PHPStan | `composer analyse` | Analisis estatico nivel 5 |
| Visual | Screenshots en 3 viewports | Mobile 390px, Tablet 768px, Desktop 1280px |

## 4. Criterios de entrada

- [x] Branch basado en develop
- [x] Responsive grid implementado en `getItemsSchema()`
- [x] Alpine.js duplicate removido de `bootstrap.js`
- [x] Migracion `origin_branch_id` creada
- [x] Relacion `originBranch()` en Order model
- [x] Select de origen en `getEntregaSchema()`
- [x] Select de direcciones del customer en `getEntregaSchema()`
- [x] Logica de guardar direccion en `CreateOrder::afterCreate()`
- [x] `composer analyse` sin errores

## 5. Criterios de salida

- [x] Todos los test cases P1 pasados
- [x] PHPStan sin errores
- [x] Screenshots en 3 viewports verificados
- [x] Cotizacion de envio funcional con location de origen seleccionada
- [x] Auto-fill de direccion guardada verificado
