# Configurar Stripe CLI (Webhooks locales)

Guia para instalar Stripe CLI y recibir webhooks en desarrollo local.

---

## 1. Instalar Stripe CLI

### macOS (Homebrew)

```bash
brew install stripe/stripe-cli/stripe
stripe version
```

### Linux (binario directo)

```bash
ASSET_URL=$(curl -s https://api.github.com/repos/stripe/stripe-cli/releases/latest | grep "browser_download_url.*linux_x86_64.tar.gz" | cut -d '"' -f 4)

curl -L "$ASSET_URL" -o /tmp/stripe-cli.tar.gz
tar -xzf /tmp/stripe-cli.tar.gz -C /tmp
rm /tmp/stripe-cli.tar.gz

/tmp/stripe version
```

### Windows (Scoop)

```powershell
scoop bucket add stripe https://github.com/nicovell3/scoop-bucket
scoop install stripe
stripe version
```

## 2. Autenticar con tu cuenta Stripe

```bash
stripe login
```

Se abrira un enlace en el navegador. Confirma el codigo de emparejamiento. Listo cuando veas:

```
Done! The Stripe CLI is configured for Tu Empresa with account id acct_XXXX
```

> La sesion expira periodicamente. Si ves errores de autenticacion, repite `stripe login`.

## 3. Escuchar webhooks en localhost

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

Salida esperada:

```
Ready! Your webhook signing secret is whsec_XXXXX (^C to quit)
```

### Copiar el webhook secret

Copia el valor `whsec_XXXXX` y agregalo en tu `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_XXXXX
```

> Este secret cambia cada vez que inicias `stripe listen`. Actualiza tu `.env` si lo reinicias.

### Ejecutar en background

```bash
# Background con log
stripe listen --forward-to localhost:8000/stripe/webhook > /tmp/stripe_output.txt 2>&1 &
echo $!  # Guarda el PID para detenerlo despues

# Ver logs
tail -f /tmp/stripe_output.txt

# Detener
kill <PID>
```

## 4. Configurar .env completo para Stripe

```env
# Stripe API Keys (Dashboard > Developers > API Keys)
STRIPE_KEY=pk_test_XXXXX
STRIPE_SECRET=sk_test_XXXXX

# Webhook Secret (de stripe listen en local, o de Stripe Dashboard en produccion)
STRIPE_WEBHOOK_SECRET=whsec_XXXXX

# Price IDs (Dashboard > Products > cada producto > Price ID)
STRIPE_PRICE_STARTER_MONTHLY=price_XXXXX
STRIPE_PRICE_STARTER_YEARLY=price_XXXXX
STRIPE_PRICE_GROWTH_MONTHLY=price_XXXXX
STRIPE_PRICE_GROWTH_YEARLY=price_XXXXX
STRIPE_PRICE_PRO_MONTHLY=price_XXXXX
STRIPE_PRICE_PRO_YEARLY=price_XXXXX
```

### Donde se usan los Price IDs

Los Price IDs se leen en `config/saas.php`:

```php
'starter' => [
    'stripe_monthly' => env('STRIPE_PRICE_STARTER_MONTHLY'),
    'stripe_yearly'  => env('STRIPE_PRICE_STARTER_YEARLY'),
],
```

Y se usan en `BillingController::checkout()`:

```php
$priceId = config("saas.plans.{$plan}.stripe_{$period}");
```

### Donde obtener los Price IDs

1. Dashboard Stripe > **Products**
2. Click en el producto (ej. SaaS Template Starter)
3. En la seccion **Pricing**, copiar el ID que empieza con `price_`
4. Cada producto tiene 2 precios: mensual y anual

## 5. Probar webhooks

### Trigger manual de eventos

```bash
stripe trigger customer.subscription.created
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed
```

### Verificar que los webhooks llegan

En la terminal de `stripe listen` deberas ver:

```
2026-03-16 15:02:42   --> customer.subscription.created [evt_XXXXX]
2026-03-16 15:02:42  <--  [200] POST http://localhost:8000/stripe/webhook [evt_XXXXX]
```

- `-->` = evento recibido de Stripe
- `<-- [200]` = tu servidor respondio OK
- `<-- [500]` = error en tu webhook handler (revisar `storage/logs/laravel.log`)

### Tarjetas de prueba

| Tarjeta | Numero | Resultado |
|---------|--------|-----------|
| Visa (exito) | `4242 4242 4242 4242` | Pago exitoso |
| Visa (rechazada) | `4000 0000 0000 0002` | Pago rechazado |
| 3D Secure | `4000 0025 0000 3155` | Requiere autenticacion |
| Fondos insuficientes | `4000 0000 0000 9995` | Fondos insuficientes |

Cualquier fecha futura y CVC de 3 digitos.

## 6. Webhook en produccion

En Stripe Dashboard > Developers > Webhooks > **Add endpoint**:

| Campo | Valor |
|-------|-------|
| URL | `https://tu-dominio.com/stripe/webhook` |
| Eventos | `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_failed` |

Copiar el **Signing secret** del endpoint → variable `STRIPE_WEBHOOK_SECRET` en Railway/hosting.

> En produccion NO se usa `stripe listen`. El secret viene del endpoint configurado en Stripe Dashboard.

## 7. Flujo completo de pago

```
Usuario clickea "Subscribe" en /admin/billing
    → BillingController::checkout() crea sesion Stripe Checkout
    → Redirige a checkout.stripe.com
    → Usuario paga con tarjeta
    → Stripe envia webhook customer.subscription.created
    → stripe listen lo reenvia a localhost:8000/stripe/webhook
    → StripeWebhookController::handleCustomerSubscriptionCreated()
        → Cashier crea registro en tabla subscriptions
        → syncTenantPlan() actualiza plan + subscribed_at en tenant
        → recordSubscriptionStarted() crea billing event
        → processReferralReward() si aplica
    → Usuario vuelve a /admin/billing?checkout=success
    → Ve su plan actualizado + billing history
```

## 8. Desarrollo local — Quick start

Abrir 2 terminales:

```bash
# Terminal 1 — Servidor
DB_PORT=5433 php artisan serve

# Terminal 2 — Stripe webhooks
stripe listen --forward-to localhost:8000/stripe/webhook
```

Verificar que el `whsec_` de `stripe listen` coincida con `STRIPE_WEBHOOK_SECRET` en `.env`.

## 9. Troubleshooting

### "No signatures found matching the expected signature"

El `STRIPE_WEBHOOK_SECRET` en tu `.env` no coincide con el que muestra `stripe listen`. Copia el nuevo valor y ejecuta `php artisan config:clear`.

### "stripe: command not found"

Usa la ruta completa (`/tmp/stripe`) o mueve el binario a `/usr/local/bin/`.

### Webhooks llegan con 500

```bash
tail -50 storage/logs/laravel.log
```

Causas comunes:
- Falta columna `stripe_id` en tabla `tenants` (ejecutar migraciones pendientes)
- Price ID no encontrado en `config/saas.php`
- Tenant no encontrado por `stripe_id`

### "column stripe_id does not exist"

Falta la migracion de columnas de Cashier. Verificar que exista la migracion `add_stripe_columns_to_tenants_table` y ejecutar:

```bash
php artisan migrate
```

### Suscripciones duplicadas en Stripe

Si ves multiples suscripciones, es porque se llamo `newSubscription()` varias veces en lugar de `swap()`:

```bash
# Listar suscripciones del customer
stripe subscriptions list --customer cus_XXXXX

# Cancelar una suscripcion especifica
echo "yes" | stripe subscriptions cancel sub_XXXXX
```

---

*Referencia: https://docs.stripe.com/stripe-cli*
