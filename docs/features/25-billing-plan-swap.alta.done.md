# 25 — Fix Billing: Upgrade/Downgrade de Planes

**Complejidad:** Alta
**Status:** Done
**Dependencia:** Stripe configurado (price IDs, webhook secret)
**Estimado:** 1-2 dias

---

## Problema

`BillingController::checkout()` usa `newSubscription('default', $priceId)` para todos los casos. Esto crea una suscripcion nueva, pero si el tenant ya tiene una suscripcion activa y quiere cambiar de plan (starter → growth, growth → pro, o downgrade), deberia hacer `swap()` en vez de `newSubscription()`. Sin esto:

- Upgrade: crea suscripcion duplicada en Stripe (cobro doble)
- Downgrade: misma situacion
- El unico flujo que funciona correctamente es la primera suscripcion

### Codigo actual (problema)

```php
// app/Http/Controllers/BillingController.php:31
$checkout = $tenant->newSubscription('default', $priceId)
    ->allowPromotionCodes();

return $checkout->checkout([...]);
```

Esto siempre crea una nueva suscripcion, sin check si ya existe una.

## Solucion

### 1. Modificar BillingController::checkout()

**File:** `app/Http/Controllers/BillingController.php`

Separar en 3 flujos:

```php
public function checkout(Request $request)
{
    $request->validate([
        'plan' => 'required|in:starter,growth,pro',
        'period' => 'required|in:monthly,yearly',
    ]);

    $tenant = currentTenant();
    if (! $tenant) {
        abort(403);
    }

    $plan = $request->input('plan');
    $period = $request->input('period');
    $priceId = config("saas.plans.{$plan}.stripe_{$period}");

    if (! $priceId) {
        return redirect()->route('filament.admin.pages.billing')
            ->with('error', 'Plan no disponible. Configura los price IDs de Stripe.');
    }

    // Caso 1: Ya tiene suscripcion activa → swap
    if ($tenant->subscribed('default')) {
        $tenant->subscription('default')->swap($priceId);

        return redirect()->route('filament.admin.pages.billing')
            ->with('success', 'Plan actualizado exitosamente.');
    }

    // Caso 2: Primera suscripcion → Stripe Checkout
    $checkout = $tenant->newSubscription('default', $priceId)
        ->allowPromotionCodes();

    if ($tenant->isOnTrial() && $tenant->trial_ends_at) {
        $checkout->trialUntil($tenant->trial_ends_at);
    }

    return $checkout->checkout([
        'success_url' => route('filament.admin.pages.billing') . '?checkout=success',
        'cancel_url' => route('filament.admin.pages.billing') . '?checkout=cancelled',
    ]);
}
```

**Comportamiento de `swap()`** (Laravel Cashier):
- Prorratea automaticamente (cobra/acredita la diferencia)
- Cambia el price ID en Stripe inmediatamente
- Dispara webhook `customer.subscription.updated` → `syncTenantPlan()` actualiza `tenants.plan`
- No requiere nueva sesion de checkout (el metodo de payment ya esta guardado)

### 2. Actualizar la vista de billing

**File:** `resources/views/filament/pages/billing.blade.php`

Cambiar los botones para distinguir upgrade vs downgrade:

```php
@if($isCurrent)
    <button disabled class="...">Plan actual</button>
@elseif($isSubscribed)
    @php
        $planOrder = ['starter' => 0, 'growth' => 1, 'pro' => 2];
        $isUpgrade = ($planOrder[$planKey] ?? 0) > ($planOrder[$currentPlan] ?? 0);
    @endphp
    <a href="{{ route('billing.checkout', ['plan' => $planKey, 'period' => 'monthly']) }}"
       class="..."
       onclick="return confirm('{{ $isUpgrade ? '¿Cambiar a plan ' . ($plan['label'] ?? $planKey) . '? Se will be prorated el cobro.' : '¿Bajar a plan ' . ($plan['label'] ?? $planKey) . '? Los limits se will adjust inmediatamente.' }}')">
        {{ $isUpgrade ? 'Upgrade' : 'Cambiar plan' }}
    </a>
@else
    <a href="{{ route('billing.checkout', ['plan' => $planKey, 'period' => 'monthly']) }}" class="...">
        Suscribirse
    </a>
@endif
```

### 3. Validar downgrade contra uso actual

**File:** `app/Http/Controllers/BillingController.php`

Antes de hacer swap a un plan inferior, check que el uso actual no exceda los limites del plan nuevo:

```php
private function validateDowngrade(Tenant $tenant, string $newPlan): ?string
{
    $newLimits = config("saas.plans.{$newPlan}", []);
    $usage = $tenant->usageCounts();

    $conflicts = [];

    $checks = [
        'orders' => ['max_orders', 'orders'],
        'users' => ['max_users', 'users'],
        'branches' => ['max_branches', 'locationes'],
        'products' => ['max_products', 'items'],
        'customers' => ['max_customers', 'customers'],
    ];

    foreach ($checks as $resource => [$limitKey, $label]) {
        $max = $newLimits[$limitKey] ?? null;
        if ($max !== null && ($usage[$resource] ?? 0) > $max) {
            $conflicts[] = "{$usage[$resource]}/{$max} {$label}";
        }
    }

    if (empty($conflicts)) {
        return null;
    }

    return 'No puedes bajar a este plan. Excedes los limites en: ' . implode(', ', $conflicts) . '.';
}
```

### 4. Manejar periodo (mensual/anual)

La vista actual solo linkea a `period=monthly`. Agregar toggle mensual/anual:

**File:** `resources/views/filament/pages/billing.blade.php`

Agregar un toggle simple arriba de la comparacion de planes y pasar el periodo seleccionado a los links de checkout. Esto es optional para v1 — se puede implementar despues.

### 5. StripeWebhookController — ya esta listo

`handleCustomerSubscriptionUpdated()` ya llama `syncTenantPlan()` que resuelve el plan desde el price ID. No requiere cambios.

### 6. Tests

**File:** `tests/Feature/BillingSwapTest.php`

```php
// - test_new_subscription_creates_checkout_session (caso actual, sin regresion)
// - test_swap_plan_when_already_subscribed
// - test_downgrade_blocked_when_usage_exceeds_limits
// - test_swap_updates_tenant_plan_via_webhook (integration)
// - test_checkout_redirects_with_error_when_no_price_id
```

Note: Los tests de Cashier/Stripe requieren mocking de la API de Stripe. Usar `Cashier::fake()` o mock del Stripe client.

## Dependencias externas (no-code)

Antes de que esto funcione, se necesita configurar en Stripe:

| Item | Env var | Estado |
|------|---------|--------|
| Stripe API keys | `STRIPE_KEY`, `STRIPE_SECRET` | Pending |
| Stripe Webhook Secret | `STRIPE_WEBHOOK_SECRET` | Pending |
| Price ID: Starter mensual | `STRIPE_PRICE_STARTER_MONTHLY` | Pending |
| Price ID: Starter anual | `STRIPE_PRICE_STARTER_YEARLY` | Pending |
| Price ID: Growth mensual | `STRIPE_PRICE_GROWTH_MONTHLY` | Pending |
| Price ID: Growth anual | `STRIPE_PRICE_GROWTH_YEARLY` | Pending |
| Price ID: Pro mensual | `STRIPE_PRICE_PRO_MONTHLY` | Pending |
| Price ID: Pro anual | `STRIPE_PRICE_PRO_YEARLY` | Pending |

Los items y precios se crean en [Stripe Dashboard](https://dashboard.stripe.com/products) o via CLI de Stripe.

## Criterios de Aceptacion

- [ ] Primera suscripcion (sin suscripcion previa) → Stripe Checkout (sin regresion)
- [ ] Upgrade con suscripcion activa → `swap()` con prorrateo
- [ ] Downgrade con suscripcion activa → `swap()` con validacion de limites
- [ ] Downgrade bloqueado si uso excede limites del plan destino
- [ ] Webhook `subscription.updated` actualiza `tenants.plan` correctamente
- [ ] Confirmacion antes de cambiar plan (JS confirm o modal)
- [ ] Flash message de exito/error despues del swap
- [ ] Tests con mock de Stripe

## Files a Crear/Modificar

| File | Accion |
|---------|--------|
| `app/Http/Controllers/BillingController.php` | Modificar — agregar logica de swap + validacion downgrade |
| `resources/views/filament/pages/billing.blade.php` | Modificar — botones upgrade/downgrade con confirmacion |
| `tests/Feature/BillingSwapTest.php` | Crear |

## Referencia

- [Laravel Cashier — Swapping Prices](https://laravel.com/docs/billing#swapping-prices)
- `Tenant` usa `Billable` trait (linea 17 de `app/Models/Tenant.php`)
- Config: `config/cashier.php` (moneda USD, locale es_MX)
- Webhook handler: `app/Http/Controllers/StripeWebhookController.php`
- Planes: `config/saas.php` (starter, growth, pro — no hay table en DB)
