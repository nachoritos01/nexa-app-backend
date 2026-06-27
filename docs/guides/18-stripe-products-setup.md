# Configurar Productos y Precios en Stripe

Guia paso a paso para crear los productos de suscripcion en Stripe Dashboard y configurar los Price IDs en el proyecto.

---

## Resumen de productos

| Plan | Mensual | Anual | Ahorro anual |
|------|---------|-------|--------------|
| Starter | $299/mes | $2,868/ano ($239/mes) | 20% |
| Growth | $699/mes | $6,708/ano ($559/mes) | 20% |
| Pro | $1,299/mes | $12,468/ano ($1,039/mes) | 20% |

> Ajusta precios y moneda segun tu proyecto en `config/saas.php`.

---

## 1. Crear productos en Stripe Dashboard

Ir a **Stripe Dashboard > Products > + Add product**

### Producto 1: Starter

| Campo | Valor |
|-------|-------|
| **Name** | `[Tu App] Starter` |
| **Description** | Copiar de `config/saas.php` → `plans.starter.description` |

**Precio mensual:**

| Campo | Valor |
|-------|-------|
| Pricing model | Standard pricing |
| Price | `299` (o el valor de tu plan) |
| Currency | Segun tu config (USD, MXN, etc.) |
| Recurring | **Recurring** |
| Billing period | Monthly |

Click **Add another price** para el precio anual:

| Campo | Valor |
|-------|-------|
| Price | `2868` (mensual x 12 x 0.80) |
| Billing period | Yearly |

Click **Save product**.

### Producto 2: Growth

| Campo | Valor |
|-------|-------|
| **Name** | `[Tu App] Growth` |
| **Description** | Copiar de `config/saas.php` → `plans.growth.description` |

- Precio mensual: `699`
- Precio anual: `6708`

### Producto 3: Pro

| Campo | Valor |
|-------|-------|
| **Name** | `[Tu App] Pro` |
| **Description** | Copiar de `config/saas.php` → `plans.pro.description` |

- Precio mensual: `1299`
- Precio anual: `12468`

---

## 2. Obtener los Price IDs

Despues de crear los productos, ve a cada uno y copia los Price IDs.

**Navegacion:** Products > click en el producto > seccion **Pricing** > columna con el ID que empieza con `price_`

Cada producto tiene 2 Price IDs (mensual y anual). Necesitas los 6 IDs.

### Estructura ideal (1 producto = 2 precios)

```
[Tu App] Starter (prod_XXX)
  ├── $299/mes   (price_XXX) → STRIPE_PRICE_STARTER_MONTHLY
  └── $2,868/ano (price_XXX) → STRIPE_PRICE_STARTER_YEARLY

[Tu App] Growth (prod_XXX)
  ├── $699/mes   (price_XXX) → STRIPE_PRICE_GROWTH_MONTHLY
  └── $6,708/ano (price_XXX) → STRIPE_PRICE_GROWTH_YEARLY

[Tu App] Pro (prod_XXX)
  ├── $1,299/mes  (price_XXX) → STRIPE_PRICE_PRO_MONTHLY
  └── $12,468/ano (price_XXX) → STRIPE_PRICE_PRO_YEARLY
```

> **Nota:** Los IDs de test mode y live mode son diferentes. Al pasar a produccion necesitaras crear nuevos productos y obtener nuevos Price IDs.

---

## 3. Configurar .env

Agregar los 6 Price IDs mas las API keys en tu `.env`:

```env
# Stripe API Keys
# Dashboard > Developers > API Keys
STRIPE_KEY=pk_test_XXXXX
STRIPE_SECRET=sk_test_XXXXX

# Price IDs de cada plan
STRIPE_PRICE_STARTER_MONTHLY=price_XXXXX
STRIPE_PRICE_STARTER_YEARLY=price_XXXXX
STRIPE_PRICE_GROWTH_MONTHLY=price_XXXXX
STRIPE_PRICE_GROWTH_YEARLY=price_XXXXX
STRIPE_PRICE_PRO_MONTHLY=price_XXXXX
STRIPE_PRICE_PRO_YEARLY=price_XXXXX
```

### Donde se usan

Los Price IDs se leen en `config/saas.php`:

```php
'starter' => [
    'stripe_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
    'stripe_yearly'  => env('STRIPE_PRICE_STARTER_YEARLY'),
],
'growth' => [
    'stripe_monthly' => env('STRIPE_PRICE_GROWTH_MONTHLY'),
    'stripe_yearly'  => env('STRIPE_PRICE_GROWTH_YEARLY'),
],
'pro' => [
    'stripe_monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
    'stripe_yearly'  => env('STRIPE_PRICE_PRO_YEARLY'),
],
```

Y se usan en `BillingController::checkout()`:

```php
$priceId = config("saas.plans.{$plan}.stripe_{$period}");
```

---

## 4. Verificar configuracion

```bash
php artisan tinker --execute="
    \$plans = ['starter','growth','pro'];
    \$periods = ['monthly','yearly'];
    foreach (\$plans as \$p) {
        foreach (\$periods as \$d) {
            \$id = config(\"saas.plans.{\$p}.stripe_{\$d}\");
            echo \"{\$p} {\$d}: \" . (\$id ?: 'NO CONFIGURADO') . PHP_EOL;
        }
    }
"
```

Si alguno muestra `NO CONFIGURADO`, revisa tu `.env` y ejecuta `php artisan config:clear`.

---

## 5. Plugins con precio en Stripe (Add-ons)

Si tu proyecto usa el Plugin Marketplace con plugins de pago, cada plugin necesita su propio precio en Stripe.

### Crear precio para un plugin

1. Products > **Add product**
2. Name: `[Tu App] Plugin - [Nombre del plugin]`
3. Pricing: **Recurring**, misma moneda que los planes
4. Billing period: Monthly

Copiar el Price ID y configurarlo en SuperAdmin > Plugins > campo `stripe_price_id`.

Los plugins pagos se agregan como **subscription items** a la suscripcion existente del tenant.

---

## 6. Checklist produccion

- [ ] Crear 3 productos en **live mode** (no test)
- [ ] Cada producto con 2 precios (mensual + anual)
- [ ] Copiar los 6 Price IDs de live mode
- [ ] Configurar en Railway/hosting: `STRIPE_KEY`, `STRIPE_SECRET`, 6 Price IDs
- [ ] Configurar endpoint webhook en Stripe Dashboard (ver guia 17)
- [ ] Configurar `STRIPE_WEBHOOK_SECRET` del endpoint (NO de stripe listen)
- [ ] Verificar con `php artisan tinker` que los Price IDs estan cargados
- [ ] Probar checkout completo con tarjeta real

---

*Referencia: https://docs.stripe.com/products-prices/manage-prices*
