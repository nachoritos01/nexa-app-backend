# QA Summary: Order Form Responsive + Delivery Step Improvements

**Date:** 2026-03-03
**Branch:** bugfix/order-form-responsive-and-quoter-mobile
**PR:** #64

## Test Results

```
composer analyse → 0 errors (PHPStan level 5)
MCP Chrome DevTools → 16 test cases verified manually
```

### Test Cases Summary

| Area | Total | Pasado | Fallido |
|------|-------|--------|---------|
| Responsive Step 2 | 3 | 3 | 0 |
| Quoter mobile fix | 2 | 2 | 0 |
| Origin branch selector | 4 | 4 | 0 |
| Customer saved addresses | 6 | 6 | 0 |
| PHPStan | 1 | 1 | 0 |
| **Total** | **16** | **16** | **0** |

## Verification Checklist

### Responsive Step 2
- [x] Mobile (< 640px): 1 column, campos stacked sin solapamiento
- [x] Tablet (>= 640px): 2 columns, Talla/Color y Cantidad/Precio emparejados
- [x] Desktop (>= 1024px): 4 columns, layout original preservado
- [x] `columnSpan(['default' => 'full', 'lg' => 2])` en Item, Diseno, Notas

### Quoter Mobile Fix
- [x] Eliminado `import Alpine` duplicado de `bootstrap.js`
- [x] Livewire gestiona Alpine internamente via `@livewireScripts`
- [x] Botones +/- de cantidad funcionan (DOM se actualiza correctamente)
- [x] Sin warning "Detected multiple instances of Alpine running" en consola

### Origin Branch Selector
- [x] Select visible solo cuando `Branch::active()->count() > 1`
- [x] Default: primera location activa
- [x] Helper text: "Location desde donde se envia el paquete"
- [x] Cotizador usa `origin_branch_id` seleccionada (no hardcoded `first()`)
- [x] Precios cambian segun origen: CDMX→Merida $180 vs GDL→Merida $185 (DHL)
- [x] Migracion `origin_branch_id` nullable FK a `locations`
- [x] Relacion `originBranch()` en Order model

### Customer Saved Addresses
- [x] Dropdown muestra direcciones del customer + "+ Nueva direccion"
- [x] Direccion default marcada con ⭐
- [x] Auto-fill: street, district, city, state, zip
- [x] Cambiar entre direcciones actualiza todos los campos
- [x] "Nueva direccion" limpia todos los campos
- [x] Checkbox "Guardar" visible solo para nuevas direcciones
- [x] `CreateOrder::afterCreate()` persiste nueva direccion si checkbox marcado
- [x] Primera direccion guardada se marca como `is_default`

## Issues Found

Ninguno relacionado a los cambios. Note: "location merida" de tenant 3 aparece en el select de pickup/origen — esto es un tema pre-existente de scope `BelongsToTenant` que no se activa correctamente en el contexto del wizard (fuera de alcance de este PR).

## Files Changed

### Created (4)
- `database/migrations/2026_03_03_224557_add_origin_branch_id_to_orders_table.php` — FK nullable
- `docs/features/34-order-delivery-improvements.pending.md` — Feature spec (Fase 1 + 2)
- `docs/qa/order-delivery-improvements/` — QA docs (plan, cases, summary)

### Modified (3)
- `app/Filament/Resources/OrderResource.php` — Responsive grid, origin branch select, address selector
- `app/Filament/Resources/OrderResource/Pages/CreateOrder.php` — Save address logic
- `app/Models/Order.php` — `origin_branch_id` fillable + `originBranch()` relation
- `resources/js/bootstrap.js` — Removed duplicate Alpine.js import

## Commits

| Hash | Message |
|------|---------|
| `16183a2` | fix: responsive order form grid and quoter mobile quantity buttons |
| `6ec54ee` | feat: add origin branch selector for shipping quotes |
| `59ba740` | feat: add customer saved addresses selector in delivery step |

## Final Status

**16 test cases, 16 passed, 0 failed, 0 regressions**
