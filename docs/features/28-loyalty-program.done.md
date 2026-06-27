# Programa de Lealtad (Loyalty Program)

**Fecha:** 2026-03-11
**Estado:** Done
**Prioridad:** Alta
**Dependencia:** Portal del Cliente (Fase 18), Plugin Marketplace (Fase 27)
**Branch:** feature/loyalty-program

---

## Problema Actual

No existe ningun incentivo para que un cliente/usuario regrese:
1. Usuario se suscribe, usa el servicio y no tiene razon para explorar mas
2. No hay forma de premiar usuarios frecuentes o de alto valor
3. Sin mecanismo de retencion ni referidos
4. Usuarios de alto volumen pagan lo mismo que usuarios nuevos
5. No hay datos de engagement post-registro

**Consecuencias:**
- Tasa de retencion depende 100% del producto — sin incentivos adicionales
- Sin diferenciacion: el usuario puede irse con la competencia sin perder nada
- Sin datos de engagement post-compra/accion
- Admin no sabe quienes son sus mejores usuarios

---

## Solucion: Programa de Lealtad

Sistema de **puntos + niveles + recompensas canjeables** integrado al Portal del Cliente, disponible como **plugin del marketplace**.

- Incluido en planes **Growth** y **Pro**
- Disponible como **add-on de pago** para plan Starter
- Gateado via `hasModule('loyalty')` + registro en `config/modules.php`

---

## Mecanica de Puntos

### Acumulacion

| Accion | Puntos | Condicion |
|--------|--------|-----------|
| Compra/Pago | 1 punto por cada $X gastados | Se acreditan al completar la transaccion (configurable por tenant) |
| Primera compra | +50 bonus | Una sola vez (flag `first_purchase_bonus`) |
| Referido exitoso | +100 al referidor | Cuando el referido completa su primera transaccion |
| Cumpleanos | +25 bonus | Si tiene fecha de nacimiento registrada |

> **Nota:** El valor de `$X` (puntos por monto), bonus de primera compra y bonus de referido son **configurables por tenant** via `tenants.settings` JSON.

**Reglas:**
- Los puntos se acreditan SOLO cuando la transaccion se completa (ej: pedido entregado, pago confirmado)
- Si una transaccion se cancela despues de acreditar, se restan los puntos (transaccion reversa)
- Los puntos NO expiran en el MVP (simplifica implementacion)
- El multiplicador del nivel aplica sobre los puntos base de compra

### Canje

- Cliente selecciona una recompensa disponible → se descuentan los puntos
- Se genera un **cupon unico** con codigo alfanumerico (ej: `ACME-LY-A7X3`)
- Prefijo configurable: usa `config('business.code_prefix')` o slug del tenant
- El cupon tiene validez de 90 dias
- El cupon se aplica manualmente por el admin al crear el pedido (en MVP)
- Un cupon por pedido (en MVP)

---

## Niveles (Tiers)

| # | Nivel | Icono | Rango Puntos | Multiplicador | Color | Beneficios |
|---|-------|-------|-------------|---------------|-------|------------|
| 1 | Bronce | medal-bronze | 0 – 499 | x1 | amber/yellow | Acceso al programa, puntos base |
| 2 | Plata | medal-silver | 500 – 1,499 | x1.5 | gray/silver | +5% descuento permanente |
| 3 | Oro | medal-gold | 1,500 – 2,999 | x2 | yellow/gold | +1 mes gratis al ano + soporte prioritario |
| 4 | VIP | trophy | 3,000+ | x3 | purple/violet | +20% descuento + features exclusivos + soporte dedicado |

**Regla de nivel:** El nivel se calcula por **puntos historicos acumulados** (lifetime), NO por puntos disponibles. Asi el cliente nunca "baja" de nivel por canjear puntos.

**Progreso:** Barra visual que muestra el avance hacia el siguiente nivel.

---

## Recompensas Canjeables

| # | Recompensa | Tipo | Puntos | Valor Aprox. | Icono |
|---|-----------|------|--------|-------------|-------|
| 1 | 5% descuento en proxima compra | discount_percent | 50 | ~$25 | tag |
| 2 | 10% descuento en proxima compra | discount_percent | 150 | ~$50 | tag |
| 3 | 1 mes gratis del plan actual | free_month | 200 | variable | calendar |
| 4 | Upgrade de almacenamiento (+10 GB) | storage_upgrade | 300 | ~$5/mes | cloud-arrow-up |
| 5 | 15% descuento en proxima compra | discount_percent | 500 | ~$75 | tag |
| 6 | Desbloqueo de feature premium (30 dias) | feature_unlock | 500 | variable | lock-open |
| 7 | Upgrade de plan (1 mes) | plan_upgrade | 750 | variable | arrow-trending-up |
| 8 | 20% descuento en proxima compra | discount_percent | 1,000 | ~$200 | fire |
| 9 | 3 meses gratis del plan actual | free_months | 1,500 | variable | sparkles |

**Notas:**
- Las recompensas se configuran en Filament (CRUD) — el admin puede agregar/editar/desactivar
- Cada recompensa tiene un `min_tier` opcional (ej: plan upgrade solo disponible para Oro+)
- Stock ilimitado en MVP (no hay limite de canjes por recompensa)
- Los tipos de recompensa son extensibles — cada tenant puede crear recompensas custom (plan Pro)

---

## UI del Portal: Tab "Lealtad"

El tab se agrega al sidebar del portal entre las secciones existentes, con un badge **[Nuevo]** en texto naranja.

### Seccion 1: Card de Nivel (header)

Card con gradiente segun nivel (amber para Bronce, gray para Plata, yellow para Oro, purple para VIP):

```
┌──────────────────────────────────────────────────┐
│  ★ Programa de Lealtad                           │
│                                                  │
│    250                        NIVEL BRONCE       │
│  Puntos Disponibles           Medallista ★       │
│                                                  │
│  Progreso al siguiente nivel:                    │
│  ██████████░░░░░░░░░░░░░░░░  250/500 pts        │
│                               250 puntos mas     │
└──────────────────────────────────────────────────┘
```

- Lado izquierdo: puntos disponibles (numero grande)
- Lado derecho: icono de nivel + nombre + subtitulo
- Abajo: barra de progreso con porcentaje hacia el siguiente nivel
- Texto debajo de la barra: "X puntos mas para [siguiente nivel]"

### Seccion 2: Beneficios de tu Nivel

Card blanca con lista de beneficios del nivel actual:

```
┌──────────────────────────────────────────────────┐
│  ✓ Beneficios de tu Nivel                        │
│                                                  │
│  • Puntos base por cada compra (x1)              │
│  • Acceso a recompensas basicas                  │
│                                                  │
│  Siguiente nivel (Plata): descuento permanente   │
│  del 5% y multiplicador x1.5                     │
└──────────────────────────────────────────────────┘
```

### Seccion 3: Recompensas Disponibles

Solo aparece si el cliente tiene puntos suficientes para al menos una recompensa:

```
┌──────────────────────────────────────────────────┐
│  🎁 Recompensas Disponibles (2)                  │
│                                                  │
│  ┌──────────┐  ┌──────────┐                      │
│  │ 5% Desc. │  │ 10% Desc │                      │
│  │  50 pts  │  │ 150 pts  │                      │
│  │ [Canjear]│  │ [Canjear]│                      │
│  └──────────┘  └──────────┘                      │
└──────────────────────────────────────────────────┘
```

Si no tiene puntos suficientes:
```
Necesitas mas puntos para canjear recompensas.
¡Sigue usando la plataforma para ganar mas puntos!
```

### Seccion 4: Recompensas Bloqueadas

Grid de cards con las recompensas que aun no puede canjear (opacity reducida, candado):

```
┌────────────┐  ┌────────────┐  ┌────────────┐
│ 🔒 1 Mes   │  │ 🔒 Storage │  │ 🔒 15%     │
│   Gratis   │  │  Upgrade   │  │ Descuento  │
│  200 pts   │  │  300 pts   │  │  500 pts   │
│            │  │            │  │            │
│ Necesitas  │  │ Necesitas  │  │ Necesitas  │
│ 150 pts mas│  │ 250 pts mas│  │ 450 pts mas│
└────────────┘  └────────────┘  └────────────┘
```

### Seccion 5: Historial de Puntos

Tabla con las ultimas transacciones:

| Fecha | Concepto | Puntos | Saldo |
|-------|----------|--------|-------|
| 11 mar 2026 | Pago mensual completado | +100 | 250 |
| 01 mar 2026 | Bonus primera compra | +50 | 50 |
| 01 mar 2026 | Bienvenida al programa | 0 | 0 |

### Seccion 6: Mis Cupones

Lista de cupones activos (generados por canjes):

```
┌──────────────────────────────────────────────────┐
│  🎫 Mis Cupones                                  │
│                                                  │
│  ACME-LY-A7X3 — 5% descuento                    │
│  Valido hasta: 09 jun 2026                       │
│  Estado: ● Disponible                            │
│                                                  │
│  ACME-LY-B2K9 — 1 mes gratis                    │
│  Usado en Pedido #45 — 20 mar 2026              │
│  Estado: ○ Usado                                 │
└──────────────────────────────────────────────────┘
```

---

## Sidebar: Badge [Nuevo]

En el sidebar del portal y en los tabs mobile, el tab "Lealtad" muestra un badge naranja:

```html
Lealtad <span class="text-[10px] font-bold text-orange-500 bg-orange-50 px-1.5 py-0.5 rounded-full ml-1">Nuevo</span>
```

Este badge se muestra siempre (es un feature nuevo). Se puede remover con una fecha hardcodeada o config.

---

## Flujo Completo (Ejemplo)

```
1. Juan se suscribe al plan Growth y su primer pago es de $100/mes
   → Pago confirmado por Stripe webhook

2. Sistema detecta transaccion completada
   → Observer detecta pago completado
   → LoyaltyService::creditPoints($transaction)
   → 10 pts base ($100 / $10) + 50 pts bonus primera compra = 60 pts
   → loyalty_transactions: [+10 compra, +50 bonus]
   → customer.loyalty_points = 60, customer.loyalty_lifetime_points = 60

3. Juan entra a Mi Cuenta → Tab "Lealtad"
   → Ve: 60 pts disponibles, Nivel Bronce (60/500 para Plata)
   → Recompensas disponibles: 5% desc (50 pts)
   → Recompensas bloqueadas: 10% desc (150 pts), etc.

4. Juan canjea "5% descuento" por 50 pts
   → LoyaltyService::redeemReward($customer, $reward)
   → customer.loyalty_points = 10 (60 - 50)
   → customer.loyalty_lifetime_points = 60 (no cambia)
   → Se genera cupon ACME-LY-A7X3, tipo: discount_percent, valor: 5%, valido 90 dias
   → loyalty_transactions: [-50, canje 5% descuento, coupon: ACME-LY-A7X3]

5. Juan usa el cupon en su proximo pedido
   → Admin busca el cupon en Filament → valido, tipo discount_percent
   → Aplica 5% descuento
   → Marca cupon como usado (order_id, used_at)

6. Tras varios meses, Juan acumula 550 lifetime points → ¡SUBE A PLATA!
   → Siguiente compra: multiplicador x1.5
   → Acceso a 5% descuento permanente
```

---

## Migraciones

### 1. Agregar campos de lealtad a `customers`

```php
Schema::table('customers', function (Blueprint $table) {
    $table->unsignedInteger('loyalty_points')->default(0);
    $table->unsignedInteger('loyalty_lifetime_points')->default(0);
    $table->string('referral_code', 10)->nullable()->unique();
    $table->foreignId('referred_by')->nullable()->constrained('customers')->nullOnDelete();
    $table->date('birthday')->nullable();
    $table->boolean('first_purchase_bonus')->default(false);
});
```

### 2. Crear `loyalty_rewards` (catalogo de recompensas)

```php
Schema::create('loyalty_rewards', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');                          // "5% descuento"
    $table->text('description')->nullable();         // "Aplica en tu proxima compra"
    $table->string('type', 30);                      // discount_percent, free_month, storage_upgrade, feature_unlock, plan_upgrade
    $table->unsignedInteger('points_cost');           // 50, 150, 200...
    $table->decimal('value', 8, 2)->nullable();      // 5.00 (para descuentos %)
    $table->string('icon', 50)->default('gift');     // heroicon name
    $table->unsignedTinyInteger('min_tier')->default(0); // 0=todos, 1=plata+, 2=oro+, 3=vip
    $table->boolean('is_active')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index('tenant_id');
});
```

### 3. Crear `loyalty_transactions` (historial de puntos)

```php
Schema::create('loyalty_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->string('type', 20);                      // credit, debit, reversal, bonus
    $table->integer('points');                        // +250, -200 (con signo)
    $table->unsignedInteger('balance_after');         // saldo despues de la transaccion
    $table->string('description');                    // "Pago completado", "Canje: 5% descuento"
    $table->nullableMorphs('reference');              // order_id, loyalty_reward_id, etc.
    $table->timestamps();

    $table->index('tenant_id');
    $table->index(['customer_id', 'created_at']);
});
```

### 4. Crear `loyalty_coupons` (cupones generados por canjes)

```php
Schema::create('loyalty_coupons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->foreignId('loyalty_reward_id')->constrained()->onDelete('cascade');
    $table->string('code', 15)->unique();            // ACME-LY-A7X3
    $table->string('type', 30);                      // discount_percent, free_month, etc.
    $table->decimal('value', 8, 2)->nullable();      // 5.00, 10.00, null (free month)
    $table->date('expires_at');
    $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('used_at')->nullable();
    $table->timestamps();

    $table->index('tenant_id');
    $table->index(['customer_id', 'used_at']);
    $table->index('code');
});
```

### 5. Agregar `loyalty` a `config/modules.php`

```php
// config/modules.php
'loyalty' => (bool) env('MODULE_LOYALTY', true),
```

---

## Modelos

### Customer.php (modificar)

```php
// Agregar a fillable:
'loyalty_points', 'loyalty_lifetime_points', 'referral_code', 'referred_by', 'birthday', 'first_purchase_bonus'

// Agregar casts:
'loyalty_points' => 'integer',
'loyalty_lifetime_points' => 'integer',
'birthday' => 'date',
'first_purchase_bonus' => 'boolean',

// Nuevas relaciones:
public function loyaltyTransactions(): HasMany
{
    return $this->hasMany(LoyaltyTransaction::class);
}

public function loyaltyCoupons(): HasMany
{
    return $this->hasMany(LoyaltyCoupon::class);
}

public function referrer(): BelongsTo
{
    return $this->belongsTo(Customer::class, 'referred_by');
}

public function referrals(): HasMany
{
    return $this->hasMany(Customer::class, 'referred_by');
}

// Accessor para nivel:
public function getLoyaltyTierAttribute(): int
{
    return match(true) {
        $this->loyalty_lifetime_points >= 3000 => 3, // VIP
        $this->loyalty_lifetime_points >= 1500 => 2, // Oro
        $this->loyalty_lifetime_points >= 500  => 1, // Plata
        default => 0,                                // Bronce
    };
}

public function getLoyaltyTierNameAttribute(): string
{
    return ['Bronce', 'Plata', 'Oro', 'VIP'][$this->loyalty_tier];
}

public function getLoyaltyMultiplierAttribute(): float
{
    return [1.0, 1.5, 2.0, 3.0][$this->loyalty_tier];
}
```

### LoyaltyReward.php (crear)

```php
class LoyaltyReward extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'type', 'points_cost',
        'value', 'icon', 'min_tier', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'value' => 'decimal:2',
        'min_tier' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableFor(Builder $query, Customer $customer): Builder
    {
        return $query->active()
            ->where('points_cost', '<=', $customer->loyalty_points)
            ->where('min_tier', '<=', $customer->loyalty_tier);
    }
}
```

### LoyaltyTransaction.php (crear)

```php
class LoyaltyTransaction extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'customer_id', 'type', 'points', 'balance_after',
        'description', 'reference_type', 'reference_id',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
```

### LoyaltyCoupon.php (crear)

```php
class LoyaltyCoupon extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'customer_id', 'loyalty_reward_id', 'code', 'type',
        'value', 'expires_at', 'order_id', 'used_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'date',
        'used_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class, 'loyalty_reward_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public static function generateCode(?string $prefix = null): string
    {
        $prefix = $prefix ?? config('business.code_prefix', 'APP');

        do {
            $code = $prefix . '-LY-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
```

### LoyaltyTier Enum (crear)

```php
enum LoyaltyTier: int
{
    case Bronze = 0;
    case Silver = 1;
    case Gold = 2;
    case VIP = 3;

    public function label(): string
    {
        return match($this) {
            self::Bronze => 'Bronce',
            self::Silver => 'Plata',
            self::Gold => 'Oro',
            self::VIP => 'VIP',
        };
    }

    public function multiplier(): float
    {
        return match($this) {
            self::Bronze => 1.0,
            self::Silver => 1.5,
            self::Gold => 2.0,
            self::VIP => 3.0,
        };
    }

    public function minPoints(): int
    {
        return match($this) {
            self::Bronze => 0,
            self::Silver => 500,
            self::Gold => 1500,
            self::VIP => 3000,
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Bronze => 'warning',
            self::Silver => 'gray',
            self::Gold => 'success',
            self::VIP => 'primary',
        };
    }
}
```

---

## Service: LoyaltyService

```php
class LoyaltyService
{
    /**
     * Configuracion default (overridable por tenant settings).
     */
    private function getConfig(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];

        return [
            'points_per_amount' => $settings['loyalty']['points_per_amount'] ?? 10,
            'first_purchase_bonus' => $settings['loyalty']['first_purchase_bonus'] ?? 50,
            'referral_bonus' => $settings['loyalty']['referral_bonus'] ?? 100,
            'birthday_bonus' => $settings['loyalty']['birthday_bonus'] ?? 25,
            'coupon_validity_days' => $settings['loyalty']['coupon_validity_days'] ?? 90,
        ];
    }

    /**
     * Acreditar puntos cuando una transaccion se completa.
     */
    public function creditTransactionPoints(Order $order): void;

    /**
     * Revertir puntos si una transaccion se cancela despues de acreditar.
     */
    public function reverseTransactionPoints(Order $order): void;

    /**
     * Acreditar bonus de primera compra.
     */
    public function creditFirstPurchaseBonus(Customer $customer): void;

    /**
     * Acreditar bonus de referido.
     */
    public function creditReferralBonus(Customer $referrer): void;

    /**
     * Acreditar bonus de cumpleanos.
     */
    public function creditBirthdayBonus(Customer $customer): void;

    /**
     * Canjear una recompensa y generar cupon.
     */
    public function redeemReward(Customer $customer, LoyaltyReward $reward): LoyaltyCoupon;

    /**
     * Registrar transaccion de puntos (privado).
     */
    private function recordTransaction(
        Customer $customer,
        string $type,
        int $points,
        string $description,
        ?Model $reference = null
    ): LoyaltyTransaction;

    /**
     * Calcular puntos base para un monto.
     */
    public function calculatePoints(int $amount, Customer $customer): int
    {
        $config = $this->getConfig(currentTenant());
        $base = intdiv($amount, $config['points_per_amount']);
        return (int) floor($base * $customer->loyalty_multiplier);
    }
}
```

### Integracion con Order Observer

```php
// En Order::boot() o un Observer dedicado
static::updated(function (Order $order) {
    if (! hasModule('loyalty')) {
        return;
    }

    if ($order->isDirty('status')) {
        $loyaltyService = app(LoyaltyService::class);

        if ($order->status === Order::STATUS_DELIVERED) {
            $loyaltyService->creditTransactionPoints($order);
        }

        if ($order->status === Order::STATUS_CANCELLED && $order->getOriginal('status') === Order::STATUS_DELIVERED) {
            $loyaltyService->reverseTransactionPoints($order);
        }
    }
});
```

---

## Filament (Admin)

### LoyaltyRewardResource

CRUD para gestionar el catalogo de recompensas:
- Nombre, descripcion, tipo, costo en puntos, valor, icono, tier minimo, activo/inactivo
- Ordenable por sort_order
- Acceso gateado: `hasModule('loyalty')`
- Recompensas custom solo disponibles en plan Pro

### CustomerResource — Seccion Lealtad

Agregar seccion al form de Customer en Filament:
- **Puntos disponibles** (readonly)
- **Puntos lifetime** (readonly)
- **Nivel actual** (badge con color)
- **Codigo referido** (readonly, auto-generado)
- **Referido por** (select, opcional)
- **Cumpleanos** (date picker)
- Boton: "Ajustar puntos manualmente" (modal con motivo)

### OrderResource — Cupon aplicado

En el detalle del pedido, seccion para buscar/aplicar cupon:
- Input de codigo de cupon
- Validacion: existe, no usado, no expirado, pertenece al cliente, mismo tenant
- Al guardar: marca cupon como usado, aplica descuento

---

## Rutas

```php
// routes/web.php — dentro del grupo auth:customer
// Solo si el modulo loyalty esta habilitado

Route::middleware('module:loyalty')->group(function () {
    Route::get('/lealtad', [CustomerPortalController::class, 'loyalty'])->name('customer.loyalty');
    Route::post('/lealtad/canjear/{reward}', [CustomerPortalController::class, 'redeemReward'])->name('customer.loyalty.redeem');
});
```

---

## Integracion con Plugin Marketplace

### Registro del Plugin

Agregar entrada en `PluginSeeder`:

```php
[
    'slug' => 'loyalty',
    'name' => 'Programa de Lealtad',
    'description' => 'Sistema de puntos, niveles y recompensas para fidelizar clientes',
    'icon' => 'heroicon-o-star',
    'category' => 'engagement',
    'is_active' => true,
    'is_free' => false,
    'price_monthly' => 9.99,           // Precio para plan Starter (add-on)
    'included_in_plans' => ['growth', 'pro'],
    'required_modules' => ['loyalty'],  // Requiere config/modules.php
    'sort_order' => 10,
],
```

### Gating en Codigo

```php
// En controladores y vistas
if (! hasModule('loyalty')) {
    abort(403);
}

// En Filament resources
public static function canAccess(): bool
{
    return hasModule('loyalty');
}

public static function shouldRegisterNavigation(): bool
{
    return hasModule('loyalty');
}

// En el portal del cliente (tab condicional)
@if(hasModule('loyalty'))
    <x-tab name="lealtad" label="Lealtad" />
@endif
```

### Activacion/Desactivacion

- Al **activar** el plugin: se ejecuta `LoyaltyRewardSeeder` para el tenant (recompensas default)
- Al **desactivar** el plugin: los datos permanecen (soft-disable), el tab y las rutas se ocultan
- Los puntos acumulados se conservan aunque el plugin se desactive

---

## Archivos a Crear

| Archivo | Descripcion |
|---------|-------------|
| `app/Models/LoyaltyReward.php` | Catalogo de recompensas (BelongsToTenant) |
| `app/Models/LoyaltyTransaction.php` | Historial de puntos (BelongsToTenant) |
| `app/Models/LoyaltyCoupon.php` | Cupones generados (BelongsToTenant) |
| `app/Enums/LoyaltyTier.php` | Enum con niveles, multiplicadores, colores |
| `app/Services/LoyaltyService.php` | Logica de puntos, niveles, canjes |
| `app/Services/LoyaltyStatsService.php` | Estadisticas y metricas de lealtad |
| `app/Filament/Resources/LoyaltyRewardResource.php` | CRUD recompensas en admin |
| `database/migrations/xxx_add_loyalty_to_customers.php` | Campos loyalty en customers |
| `database/migrations/xxx_create_loyalty_rewards_table.php` | Tabla recompensas |
| `database/migrations/xxx_create_loyalty_transactions_table.php` | Tabla transacciones |
| `database/migrations/xxx_create_loyalty_coupons_table.php` | Tabla cupones |
| `database/migrations/xxx_add_loyalty_to_modules_config.php` | Agregar loyalty a modules |
| `database/seeders/LoyaltyRewardSeeder.php` | Seed de 9 recompensas genericas |
| `resources/views/customer/tabs/loyalty.blade.php` | Tab de lealtad en portal |

## Archivos a Modificar

| Archivo | Cambio |
|---------|--------|
| `app/Models/Customer.php` | +fillable, +casts, +relaciones, +accessors tier/multiplier |
| `app/Models/Order.php` | +observer/boot para acreditar puntos (si hasModule) |
| `app/Http/Controllers/CustomerPortalController.php` | +metodos `loyalty()` y `redeemReward()` |
| `resources/views/customer/portal.blade.php` | +tab "Lealtad" condicional con badge [Nuevo] |
| `routes/web.php` | +rutas loyalty con middleware module |
| `config/modules.php` | +entrada `'loyalty' => true` |
| `database/seeders/PluginSeeder.php` | +entrada del plugin loyalty |
| `app/Filament/Resources/CustomerResource.php` | +seccion lealtad (puntos, nivel, referral) |

---

## Sub-fases de Implementacion

### L1: Migraciones + Modelos (base)
1. Migracion: agregar `loyalty_points`, `loyalty_lifetime_points`, `referral_code`, `referred_by`, `birthday`, `first_purchase_bonus` a customers
2. Migracion: crear `loyalty_rewards` con `tenant_id`
3. Migracion: crear `loyalty_transactions` con `tenant_id`
4. Migracion: crear `loyalty_coupons` con `tenant_id`
5. Crear enum: `LoyaltyTier`
6. Crear modelos: `LoyaltyReward`, `LoyaltyTransaction`, `LoyaltyCoupon` (todos con `BelongsToTenant`)
7. Modificar `Customer.php`: fillable, casts, relaciones, accessors
8. Agregar `'loyalty' => true` a `config/modules.php`
9. Agregar plugin loyalty a `PluginSeeder`

### L2: LoyaltyService + Observer
10. Crear `LoyaltyService` con metodos: creditTransactionPoints, reverseTransactionPoints, redeemReward, calculatePoints
11. Implementar configuracion por tenant (`getConfig` lee de `tenants.settings`)
12. Integrar con Order observer/boot: acreditar al entregar, revertir al cancelar (guarded por `hasModule('loyalty')`)
13. Bonus primera compra: detectar y acreditar
14. Tests unitarios del servicio

### L3: Tab Lealtad en Portal
15. Vista `customer/tabs/loyalty.blade.php` (card nivel, progreso, beneficios, recompensas, historial)
16. Metodo `loyalty()` en `CustomerPortalController` (guarded por `hasModule('loyalty')`)
17. Metodo `redeemReward()` con validacion y generacion de cupon (prefijo configurable)
18. Agregar tab "Lealtad" condicional con badge [Nuevo] al sidebar y mobile tabs
19. Seccion "Mis Cupones" con estados (disponible, usado, expirado)

### L4: Filament Admin
20. `LoyaltyRewardResource` — CRUD de recompensas (gateado por `hasModule`)
21. Seccion lealtad en `CustomerResource` (puntos, nivel, referral code, ajuste manual)
22. Buscar/aplicar cupon en `OrderResource` (input codigo, validar, marcar usado)

### L5: Referidos + Pulido
23. Sistema de referidos: generar codigo unico por cliente, validar al registrar referido
24. Acreditar bonus al referidor cuando referido completa primera transaccion
25. `LoyaltyStatsService` + widget Filament: "Top Clientes por Puntos" en dashboard
26. Responsive: verificar tab loyalty en mobile
27. Tests de integracion del flujo completo

---

## Seeder: LoyaltyRewardSeeder

```php
// Recompensas genericas SaaS — adaptables por cada tenant
$rewards = [
    ['name' => '5% descuento', 'type' => 'discount_percent', 'points_cost' => 50, 'value' => 5.00, 'icon' => 'tag', 'sort_order' => 1],
    ['name' => '10% descuento', 'type' => 'discount_percent', 'points_cost' => 150, 'value' => 10.00, 'icon' => 'tag', 'sort_order' => 2],
    ['name' => '1 mes gratis', 'type' => 'free_month', 'points_cost' => 200, 'value' => null, 'icon' => 'calendar', 'sort_order' => 3],
    ['name' => 'Upgrade almacenamiento (+10 GB)', 'type' => 'storage_upgrade', 'points_cost' => 300, 'value' => 10.00, 'icon' => 'cloud-arrow-up', 'sort_order' => 4],
    ['name' => '15% descuento', 'type' => 'discount_percent', 'points_cost' => 500, 'value' => 15.00, 'icon' => 'tag', 'sort_order' => 5],
    ['name' => 'Feature premium (30 dias)', 'type' => 'feature_unlock', 'points_cost' => 500, 'value' => null, 'icon' => 'lock-open', 'sort_order' => 6],
    ['name' => 'Upgrade de plan (1 mes)', 'type' => 'plan_upgrade', 'points_cost' => 750, 'value' => null, 'icon' => 'arrow-trending-up', 'sort_order' => 7],
    ['name' => '20% descuento', 'type' => 'discount_percent', 'points_cost' => 1000, 'value' => 20.00, 'icon' => 'fire', 'sort_order' => 8],
    ['name' => '3 meses gratis', 'type' => 'free_months', 'points_cost' => 1500, 'value' => null, 'icon' => 'sparkles', 'sort_order' => 9],
];

// Usar updateOrCreate con tenant_id (idempotent, tenant-aware)
foreach ($rewards as $reward) {
    LoyaltyReward::updateOrCreate(
        ['tenant_id' => $tenant->id, 'name' => $reward['name']],
        $reward
    );
}
```

---

## Consideraciones Multi-Tenant

> **Importante:** Este feature debe implementarse con soporte multi-tenant desde el inicio.

### Migraciones

Todas las tablas nuevas incluyen `tenant_id`:

```php
// loyalty_rewards, loyalty_transactions, loyalty_coupons
$table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
$table->index('tenant_id');
```

Los campos en `customers` ya estan scoped por tenant (customers tiene tenant_id).

### Modelos

- `LoyaltyReward`, `LoyaltyTransaction`, `LoyaltyCoupon` usan trait `BelongsToTenant`
- Las queries pasan por el global scope de tenant automaticamente
- `LoyaltyService` opera dentro del tenant actual (`currentTenant()`)

### Configuracion por Tenant

Almacenado en `tenants.settings` JSON:

```json
{
    "loyalty": {
        "enabled": true,
        "points_per_amount": 10,
        "first_purchase_bonus": 50,
        "referral_bonus": 100,
        "birthday_bonus": 25,
        "coupon_validity_days": 90
    }
}
```

Cada tenant puede personalizar la mecanica de puntos sin afectar a otros tenants.

### Plugin Gating

| Feature | Plan Minimo | Justificacion |
|---------|-------------|---------------|
| Programa de lealtad basico | Growth (incluido) / Starter (add-on $9.99/mes) | Diferenciador de retención |
| Recompensas custom (CRUD admin) | Pro | Feature premium |
| Referidos | Growth | Crecimiento organico |
| Widget dashboard "Top Clientes" | Growth | Analytics |

### Seeders

- `LoyaltyRewardSeeder` es tenant-aware: crea recompensas default para cada tenant
- Se ejecuta al activar el plugin loyalty para un tenant
- Usa `updateOrCreate` para idempotencia (safe en redeploy)

---

## Verificacion

1. `php artisan migrate` — 4 migraciones sin error
2. `php artisan db:seed --class=LoyaltyRewardSeeder` — 9 recompensas creadas por tenant
3. Plugin loyalty visible en marketplace, activable/desactivable
4. `hasModule('loyalty')` retorna `true` solo cuando el plugin esta activo para el tenant
5. Completar transaccion → puntos acreditados automaticamente al cliente
6. Portal → Tab Lealtad visible solo si plugin activo, muestra nivel, puntos, progreso, recompensas
7. Canjear recompensa → cupon generado con prefijo de tenant, puntos descontados
8. Cancelar transaccion completada → puntos revertidos
9. Filament → CRUD de recompensas funcional (gateado por hasModule)
10. Filament → Seccion lealtad en Customer visible
11. Badge [Nuevo] visible en sidebar y mobile
12. Todas las tablas tienen `tenant_id` + modelos usan `BelongsToTenant`
13. Configuracion de puntos leida de `tenants.settings` JSON
14. Desactivar plugin → datos se conservan, UI se oculta
15. `composer analyse` sin errores nuevos
16. `composer format` limpio

---

*Documento generado: 2026-03-11*
