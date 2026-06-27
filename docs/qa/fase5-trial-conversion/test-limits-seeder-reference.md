# TestLimitsSeeder — Referencia

Seeder de test para llenar tenants cerca de sus limites de plan.
NO se ejecuta en operations — solo para testing local.

**File:** `database/seeders/TestLimitsSeeder.php`

## Uso

```bash
# Default: seed primer tenant en trial
php artisan db:seed --class=TestLimitsSeeder

# Seed por plan especifico (crea tenant si no existe)
SEED_PLAN=starter php artisan db:seed --class=TestLimitsSeeder
SEED_PLAN=growth  php artisan db:seed --class=TestLimitsSeeder
SEED_PLAN=pro     php artisan db:seed --class=TestLimitsSeeder

# Seed los tres planes
SEED_PLAN=all php artisan db:seed --class=TestLimitsSeeder
```

## Targets por plan

### Starter (max_orders:50, max_users:2, max_branches:1, max_products:20, max_customers:200)

| Recurso | Target | Example |
|---------|--------|---------|
| Users | 100% | 2/2 |
| Orders | 90% | 45/50 |
| Items | 90% | 18/20 |
| Customers | 85% | 170/200 |
| Locationes | 100% | 1/1 |

### Growth (max_orders:200, max_users:5, max_branches:3, max_products:100, max_customers:1000)

| Recurso | Target | Example |
|---------|--------|---------|
| Users | 80% | 4/5 |
| Orders | 85% | 170/200 |
| Items | 90% | 90/100 |
| Customers | 85% | 850/1000 |
| Locationes | 100% | 3/3 |

### Pro (todo ilimitado — cantidades fijas)

| Recurso | Cantidad fija |
|---------|---------------|
| Users | 3 extra |
| Orders | 50 |
| Items | 30 |
| Customers | 100 |
| Locationes | 2 |

## Notas

- El seeder es idempotente: verifica conteos actuales antes de crear
- Si no existe un tenant para el plan solicitado, lo crea automaticamente
- El campo `state` en branches usa codigos cortos (YUC, CDMX, etc.) por varchar(5)
- Requiere factories: `OrderFactory`, `ProductFactory`, `TenantFactory`, `UserFactory`
- Limpia cache de usage counts despues de crear datos
