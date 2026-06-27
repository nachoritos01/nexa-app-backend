# Test Plan: Billing Plan Swap (Feature 25)

**Feature:** Upgrade/Downgrade de suscripcion via `swap()`
**Branch:** feature/billing-plan-swap
**PR:** #51
**Date:** 2026-02-26

## Scope

Check que:
1. Tenants suscritos pueden cambiar de plan (upgrade/downgrade) sin crear suscripciones duplicadas
2. Downgrades son bloqueados si el uso excede los limites del plan destino
3. La UI refleja el cambio inmediatamente (sin esperar webhook)
4. Flash messages se muestran correctamente

## Prerequisitos

- Stripe CLI instalado y escuchando webhooks: `/tmp/stripe listen --forward-to localhost:8000/stripe/webhook`
- Stripe test mode configurado en `.env` con 6 Price IDs
- Tenant con suscripcion activa en Stripe

## Test Cases

### TC-SWAP-001: Upgrade Growth → Pro

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Navegar a /admin/billing | Plan Growth, boton "Upgrade" en Pro |
| 2 | Click "Upgrade" en Pro | Alert: "¿Cambiar a plan Pro? Se prorrateara el cobro." |
| 3 | Aceptar alert | Redirect a /admin/billing con flash "Plan actualizado exitosamente." |
| 4 | Check UI | Header: "Plan Pro", limites: ∞, Pro: "Plan actual" (disabled) |
| 5 | Check sidebar | Aparecen links "API" y "Webhooks" |
| 6 | Check DB | `tenants.plan = 'pro'`, `subscription_items.stripe_price = price_pro_monthly` |

### TC-SWAP-002: Downgrade Pro → Starter

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Navegar a /admin/billing (plan Pro) | Starter y Growth muestran "Cambiar plan" |
| 2 | Click "Cambiar plan" en Starter | Alert: "¿Bajar a plan Starter? Los limites se ajustaran inmediatamente." |
| 3 | Aceptar alert | Redirect con flash "Plan actualizado exitosamente." |
| 4 | Check UI | Header: "Plan Starter", limites: 50/2/1/20/200 |
| 5 | Check sidebar | Links "API" y "Webhooks" desaparecen |

### TC-SWAP-003: Downgrade bloqueado por uso excedido

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Estar en plan Growth con 3 users y 2 locationes | Uso excede starter (max 2 users, 1 branch) |
| 2 | Click "Cambiar plan" en Starter | Alert de confirmacion |
| 3 | Aceptar alert | Redirect con flash rojo: "No puedes bajar a este plan. Excedes los limites en: 3/2 users, 2/1 locationes." |
| 4 | Plan NO cambia | Sigue en Growth |

### TC-SWAP-004: Primera suscripcion (sin swap)

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Tenant sin suscripcion (trial o inactivo) | Botones muestran "Suscribirse" |
| 2 | Click "Suscribirse" en Growth | Redirect a Stripe Checkout |
| 3 | Completar checkout | Webhook actualiza tenant, redirect a billing con plan Growth |

### TC-SWAP-005: Plan actual deshabilitado

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Navegar a /admin/billing | Plan actual muestra boton disabled "Plan actual" |
| 2 | Intentar click | No sucede nada (boton deshabilitado) |

### TC-SWAP-006: Price ID faltante

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Remover STRIPE_PRICE_PRO_MONTHLY de .env | |
| 2 | Click Upgrade a Pro | Flash rojo: "Plan no disponible. Configura los price IDs de Stripe." |

### TC-SWAP-007: Flash messages visibilidad

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Swap exitoso | Banner verde con "Plan actualizado exitosamente." |
| 2 | Swap bloqueado | Banner rojo con mensaje de conflictos |
| 3 | Recargar pagina | Banners desaparecen (session flash) |

### TC-SWAP-008: Toggle mensual/anual en billing

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Navegar a /admin/billing | Toggle Mensual/Anual visible, "Mensual" activo por defecto |
| 2 | Check precios mensuales | Starter $299/mes, Growth $699/mes, Pro $1,299/mes |
| 3 | Click "Anual -20%" | Precios cambian: Starter $239/mes ($2,868/year), Growth $559/mes ($6,708/year), Pro $1,039/mes ($12,468/year) |
| 4 | Click "Mensual" | Precios regresan a mensuales |

### TC-SWAP-009: Links de checkout reflejan periodo seleccionado

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Toggle en "Mensual" | Links de Suscribirse/Upgrade usan period=monthly |
| 2 | Toggle en "Anual" | Links cambian a period=yearly |
| 3 | Click checkout en modo anual | Stripe Checkout muestra precio anual correcto |

### TC-SWAP-010: Toggle dark mode compatible

| Step | Accion | Resultado esperado |
|------|--------|--------------------|
| 1 | Navegar a /admin/billing (dark mode) | Toggle visible con colors dark-mode-aware |
| 2 | Click "Anual" | Toggle se resalta correctamente en dark mode |
| 3 | Precios visibles | Texto legible en dark mode |

## Automated Tests

| Test | Descripcion | Assertions |
|------|-------------|------------|
| `test_first_subscription_redirects_to_stripe_checkout` | Mock Cashier, sin suscripcion → redirect Stripe | 1 |
| `test_swap_plan_when_already_subscribed` | Mock subscription, swap valido → redirect con success | 2 |
| `test_downgrade_blocked_when_usage_exceeds_limits` | Uso excedido → redirect con error | 3 |
| `test_checkout_redirects_with_error_when_no_price_id` | Config sin price ID → error flash | 2 |
| `test_checkout_requires_authentication` | Guest → redirect login | 2 |

**Total:** 5 tests, 16 assertions
