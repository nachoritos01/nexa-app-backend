# QA: Billing Plan Swap (Feature 25)

**Date:** 2026-02-26
**Branch:** feature/billing-plan-swap
**PR:** #51

## Test Results

```
composer test
Tests: 300 passed (814 assertions)
Duration: 47.25s
```

### New Tests Added (5)

| Test File | Tests | Assertions | Status |
|-----------|-------|------------|--------|
| tests/Feature/BillingSwapTest.php | 5 | 16 | PASS |

### Existing Tests (295) — No Regressions

All previous tests pass without modification.

## Manual Verification (Stripe Test Mode)

### Stripe Setup

- Stripe CLI: `/tmp/stripe listen --forward-to localhost:8000/stripe/webhook`
- Webhook secret: configurado en `.env` (STRIPE_WEBHOOK_SECRET)
- 6 Price IDs configurados (3 planes x 2 periodos)
- Tarjeta de test: 4242 4242 4242 4242

### TC-SWAP-001: Upgrade Growth → Pro

- [x] Boton "Upgrade" visible en Pro (color primario)
- [x] Alert: "¿Cambiar a plan Pro? Se prorrateara el cobro."
- [x] Redirect con flash verde: "Plan actualizado exitosamente."
- [x] Header actualizado: "Plan Pro"
- [x] Limites: todos muestran "∞"
- [x] Pro: boton "Plan actual" (disabled)
- [x] Sidebar: "API" y "Webhooks" visibles
- [x] DB: `tenants.plan = 'pro'`

### TC-SWAP-002: Downgrade Pro → Starter

- [x] Botones "Cambiar plan" en Starter y Growth (color gris)
- [x] Alert: "¿Bajar a plan Starter? Los limites se ajustaran inmediatamente."
- [x] Redirect con flash verde: "Plan actualizado exitosamente."
- [x] Header: "Plan Starter"
- [x] Limites actualizados: 50/2/1/20/200
- [x] DB: `tenants.plan = 'starter'`

### TC-SWAP-004: Primera suscripcion (Growth)

- [x] Tenant en trial, boton "Suscribirse"
- [x] Redirect a Stripe Checkout
- [x] Checkout muestra: SaaS Template Growth, 13 dias trial, USD 699/mes
- [x] Checkout completado → webhook procesa `customer.subscription.created`
- [x] Webhooks: todos 200 OK (payment_method.attached, subscription.created, checkout.session.completed, invoice.*)

### TC-SWAP-005: Plan actual deshabilitado

- [x] Boton "Plan actual" disabled, cursor not-allowed

### TC-SWAP-008: Toggle mensual/anual en billing

- [x] Toggle Mensual/Anual visible en seccion "Planes available"
- [x] "Mensual" activo por defecto con bg-white y shadow
- [x] Precios mensuales: Starter $299, Growth $699, Pro $1,299
- [x] Click "Anual -20%": precios cambian a $239, $559, $1,039 /mes con total /year
- [x] Click "Mensual": precios regresan a mensuales
- [x] Toggle dark-mode-aware (bg-gray-700 fondo, bg-gray-600 activo)

### TC-SWAP-009: Links de checkout reflejan periodo

- [x] Modo mensual: links usan `period=monthly`
- [x] Modo anual: links cambian a `period=yearly`
- [x] Stripe Checkout en modo anual muestra precio anual correcto (USD 12,468/year para Pro)
- [x] Trial days se preservan en checkout (trialUntil con trial_ends_at del tenant)

## Bug Found and Fixed

### BUG: UI no se actualiza despues del swap

**Sintoma:** Despues de confirmar upgrade/downgrade, la pagina muestra el plan anterior. No aparece flash message.

**Causa raiz:** `BillingController::swapPlan()` solo llamaba `$tenant->subscription('default')->swap($priceId)` en Stripe pero NO actualizaba `$tenant->plan` en la DB local. Dependia del webhook asincrono (`customer.subscription.updated`) que llega con delay.

**Fix:** Agregar actualizacion local inmediata en `swapPlan()`:
```php
$tenant->subscription('default')->swap($priceId);

// Update plan locally (webhook will confirm later)
$tenant->update(['plan' => $newPlan]);
$tenant->clearUsageCache();
```

**Status:** Corregido. La UI ahora se actualiza al instante. El webhook confirma/sincroniza despues.

## Files Changed

### Modified (1)

| File | Cambio |
|------|--------|
| `app/Http/Controllers/BillingController.php` | `swapPlan()`: agregar `$tenant->update(['plan'])` + `clearUsageCache()` para update inmediato |

### Created (1)

| File | Descripcion |
|------|-------------|
| `tests/Feature/BillingSwapTest.php` | 5 tests para swap, downgrade, primera suscripcion, auth |

### Renamed (1)

| De | A |
|----|---|
| `docs/features/25-billing-plan-swap.alta.pending.md` | `docs/features/25-billing-plan-swap.alta.done.md` |

## PHPStan

```
composer analyse -- --memory-limit=512M
No new errors (48 pre-existing)
```

## Webhook Log (Stripe CLI)

```
payment_method.attached      → 200
customer.subscription.created → 200
setup_intent.created          → 200
setup_intent.succeeded        → 200
checkout.session.completed    → 200
invoice.created               → 200
invoice.finalized             → 200
invoice.paid                  → 200
invoice.payment_succeeded     → 200
customer.subscription.updated → 200 (x4 — swaps)
```

Todos los webhooks procesados con HTTP 200.
