# 03 - Models Eloquent y Base de Datos

Esta guide cubre los models Eloquent, relationships, migrations y operaciones de base de datos.

## Conceptos Fundamentales de Eloquent

### What es Eloquent?

Eloquent es el ORM (Object-Relational Mapping) de Laravel que permite interactuar con la base de datos usando objetos PHP en lugar de SQL directo.

```php
// Sin ORM (SQL directo)
$pdo->query("SELECT * FROM products WHERE is_active = true");

// Con Eloquent
Product::where('is_active', true)->get();
```

### Estructura Basic de un Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes; // Borrado suave

    // Table (por defecto: plural del name del model)
    protected $table = 'products';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'title',
        'description',
        'model_id',
        'is_active',
    ];

    // Campos protegidos (no asignables masivamente)
    protected $guarded = ['id'];

    // Campos ocultos en JSON
    protected $hidden = ['deleted_at'];

    // Casteo de tipos
    protected $casts = [
        'photos' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Valores por defecto
    protected $attributes = [
        'is_active' => true,
    ];
}
```

## Models del Proyecto

### 1. Product (Item)

```php
// app/Models/Product.php

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'model_id',
        'color_id',
        'title',
        'description',
        'technique',
        'material',
        'sizes',        // JSON legacy
        'colors',       // JSON legacy
        'tags',
        'photos',
        'is_active',
    ];

    protected $casts = [
        'sizes' => 'array',
        'colors' => 'array',
        'tags' => 'array',
        'photos' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationship: Item pertenece a un Model (tipo)
    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'model_id');
    }

    // Relationship: Item pertenece a un Color
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    // Relationship: Item tiene muchas Sizes (pivot)
    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_sizes')
            ->withPivot(['stock', 'price_modifier', 'is_active'])
            ->withTimestamps();
    }

    // Accessor: Foto principal
    public function getMainPhotoAttribute(): ?string
    {
        return $this->photos[0] ?? null;
    }

    // Accessor: Name del model
    public function getModelNameAttribute(): ?string
    {
        return $this->productModel?->name;
    }

    // Accessor: Code del model
    public function getModelCodeAttribute(): ?string
    {
        return $this->productModel?->code;
    }

    // Method: Calcular precio
    public function calculatePrice(string $sizeCode, int $quantity): int
    {
        $modelCode = $this->model_code ?? 'basicas';
        $sizeType = PricingRule::getSizeType($sizeCode);

        $rule = PricingRule::active()
            ->forModel($modelCode)
            ->forSizeType($sizeType)
            ->where('min_qty', '<=', $quantity)
            ->where(fn($q) => $q->whereNull('max_qty')
                               ->orWhere('max_qty', '>=', $quantity))
            ->first();

        return $rule?->price ?? 0;
    }

    // Method: Check si tiene una talla
    public function hasSize(string $code): bool
    {
        return $this->sizes->contains('code', $code);
    }

    // Scope: Solo activos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: Por technique
    public function scopeByTechnique($query, string $technique)
    {
        return $query->where('technique', $technique);
    }

    // Scope: Por model
    public function scopeByModel($query, string $modelCode)
    {
        return $query->whereHas('productModel', fn($q) =>
            $q->where('code', $modelCode)
        );
    }
}
```

**Examples de uso:**

```php
// Obtener todos los items activos
$products = Product::active()->get();

// Item con relationships
$product = Product::with(['productModel', 'color', 'sizes'])->find(1);

// Acceder a propiedades
echo $product->title;              // "Product Basic Blanca"
echo $product->main_photo;         // URL de la foto
echo $product->model_name;         // "Basic"
echo $product->productModel->code; // "basicas"

// Calcular precio
$price = $product->calculatePrice('MD', 10); // $170

// Filtrar por model
$basicas = Product::byModel('basicas')->get();

// Con tallas specifics
$product->sizes->each(fn($size) =>
    echo "{$size->code}: stock {$size->pivot->stock}"
);
```

### 2. Order (Order)

```php
// app/Models/Order.php

class Order extends Model
{
    use SoftDeletes;

    // Constantes de estado
    const STATUS_RECEIVED = 'received';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PRODUCTION = 'in_production';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'items',              // JSON legacy
        'subtotal',
        'deposit',
        'total',
        'status',
        'notes',
        'estimated_delivery',
        'confirmed_at',
        'production_started_at',
        'ready_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'integer',
        'deposit' => 'integer',
        'total' => 'integer',
        'estimated_delivery' => 'date',
        'confirmed_at' => 'datetime',
        'production_started_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // Relationship: Order tiene muchos Items
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    // Alias para items
    public function items(): HasMany
    {
        return $this->orderItems();
    }

    // === METHODS DE TRANSITION DE ESTADO ===

    public function confirm(): self
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        return $this;
    }

    public function startProduction(): self
    {
        $this->update([
            'status' => self::STATUS_IN_PRODUCTION,
            'production_started_at' => now(),
        ]);
        return $this;
    }

    public function markReady(): self
    {
        $this->update([
            'status' => self::STATUS_READY,
            'ready_at' => now(),
        ]);
        return $this;
    }

    public function deliver(): self
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
        return $this;
    }

    public function cancel(): self
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
        return $this;
    }

    // Recalcular totales desde items
    public function recalculateTotals(): self
    {
        $subtotal = $this->orderItems()->sum('subtotal');
        $this->update([
            'subtotal' => $subtotal,
            'deposit' => (int) ($subtotal * 0.5),
            'total' => $subtotal,
        ]);
        return $this;
    }

    // === SCOPES ===

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_CANCELLED,
            self::STATUS_DELIVERED,
        ]);
    }
}
```

**Examples de uso:**

```php
// Crear orden con items
$order = Order::create([
    'customer_name' => 'Juan Perez',
    'customer_phone' => '5551234567',
    'customer_email' => 'juan@email.com',
]);

$order->orderItems()->createMany([
    [
        'product_id' => 1,
        'size' => 'MD',
        'color' => 'Blanco',
        'quantity' => 5,
        'unit_price' => 200,
    ],
    [
        'product_id' => 2,
        'size' => 'GD',
        'color' => 'Negro',
        'quantity' => 3,
        'unit_price' => 200,
    ],
]);

$order->recalculateTotals();

// Workflow de estados
$order->confirm();           // pending -> confirmed
$order->startProduction();   // confirmed -> in_production
$order->markReady();         // in_production -> ready
$order->deliver();           // ready -> delivered

// Consultas
$pendingOrders = Order::pending()->get();
$activeOrders = Order::active()->with('orderItems.product')->get();
```

### 3. PricingRule (Regla de Precio)

```php
// app/Models/PricingRule.php

class PricingRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'model_id',     // FK normalizado
        'model',        // Legacy string
        'size_type',    // 'standard' o 'twoXG'
        'min_qty',
        'max_qty',
        'price',
        'label',
        'savings',
        'is_active',
    ];

    protected $casts = [
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationship con ItemCategory
    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'model_id');
    }

    // Determinar tipo de talla
    public static function getSizeType(string $size): string
    {
        return $size === 'XX' ? 'twoXG' : 'standard';
    }

    // Calcular precio (method istico)
    public static function calculatePrice(
        string $model,
        string $sizeType,
        int $quantity
    ): ?int {
        $rule = static::active()
            ->forModel($model)
            ->forSizeType($sizeType)
            ->where('min_qty', '<=', $quantity)
            ->where(fn($q) => $q->whereNull('max_qty')
                               ->orWhere('max_qty', '>=', $quantity))
            ->orderBy('min_qty', 'desc')
            ->first();

        return $rule?->price;
    }

    // Obtener todos los tiers
    public static function getTiers(string $model, string $sizeType): array
    {
        return static::active()
            ->forModel($model)
            ->forSizeType($sizeType)
            ->orderBy('min_qty')
            ->get()
            ->toArray();
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForModel($query, string $model)
    {
        return $query->where('model', $model);
    }

    public function scopeForModelId($query, int $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    public function scopeForSizeType($query, string $sizeType)
    {
        return $query->where('size_type', $sizeType);
    }
}
```

**Examples de uso:**

```php
// Calcular precio
$price = PricingRule::calculatePrice('basicas', 'standard', 10);
// Retorna: 170

// Obtener tipo de talla
$sizeType = PricingRule::getSizeType('XX');  // 'twoXG'
$sizeType = PricingRule::getSizeType('MD');  // 'standard'

// Obtener todos los tiers
$tiers = PricingRule::getTiers('basicas', 'standard');
// [
//   ['min_qty' => 1, 'max_qty' => 5, 'price' => 200, 'label' => '1-5 piezas'],
//   ['min_qty' => 6, 'max_qty' => 12, 'price' => 185, 'label' => '6-12 piezas'],
//   ['min_qty' => 13, 'max_qty' => null, 'price' => 175, 'label' => '13+ piezas'],
// ]
```

### 4. Models de Catalog

#### ItemCategory (Tipo de Product)

```php
class ItemCategory extends Model
{
    protected $fillable = ['code', 'name', 'description', 'display_order', 'is_active'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'model_id');
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'model_id');
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}

// Uso
$basicas = ItemCategory::findByCode('basicas');
$types = ItemCategory::active()->ordered()->get();
```

#### Size (Talla)

```php
class Size extends Model
{
    protected $fillable = ['code', 'name', 'size_type', 'display_order', 'is_active'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_sizes')
            ->withPivot(['stock', 'price_modifier', 'is_active']);
    }

    public function isStandard(): bool
    {
        return $this->size_type === 'standard';
    }

    public function isTwoXG(): bool
    {
        return $this->size_type === 'twoXG';
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}

// Uso
$sizes = Size::active()->ordered()->get();
$md = Size::findByCode('MD');
```

#### Color

```php
class Color extends Model
{
    protected $fillable = ['name', 'hex_code', 'display_order', 'is_active'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }
}

// Uso
$colors = Color::active()->ordered()->get();
$blanco = Color::findByName('Blanco');
```

## Relationships en Eloquent

### Tipos de Relationships

```
┌───────────────────────────────────────────────────────────────┐
│                    TIPOS DE RELACIONES                        │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  1:1 (hasOne / belongsTo)                                     │
│  ┌────────┐          ┌────────┐                              │
│  │  User  │─────────▶│Profile │                              │
│  └────────┘          └────────┘                              │
│                                                               │
│  1:N (hasMany / belongsTo)                                    │
│  ┌────────┐          ┌────────┐                              │
│  │  Order │─────────▶│ Items  │                              │
│  └────────┘          └────────┘                              │
│                                                               │
│  M:N (belongsToMany)                                          │
│  ┌────────┐  ┌───────┐  ┌────────┐                          │
│  │Product │─▶│ pivot │◀─│  Size  │                          │
│  └────────┘  └───────┘  └────────┘                          │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

### Examples del Proyecto

```php
// === belongsTo (N:1) ===
// Un item pertenece a un model
class Product extends Model
{
    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'model_id');
    }
}

// Uso
$product->productModel->name; // "Basic"


// === hasMany (1:N) ===
// Un model tiene muchos items
class ItemCategory extends Model
{
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'model_id');
    }
}

// Uso
$basicas->products; // Collection de items


// === belongsToMany (M:N) ===
// Item tiene muchas tallas (y viceversa)
class Product extends Model
{
    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_sizes')
            ->withPivot(['stock', 'price_modifier', 'is_active'])
            ->withTimestamps();
    }
}

// Uso
$product->sizes;                    // Collection de tallas
$product->sizes->first()->pivot->stock; // Acceso a datos pivot
```

### Eager Loading (Carga Anticipada)

```php
// ❌ Problema N+1 (una consulta por cada relationship)
$products = Product::all();
foreach ($products as $product) {
    echo $product->productModel->name; // Consulta adicional cada vez
}

// ✅ Eager loading (una sola consulta)
$products = Product::with('productModel')->get();
foreach ($products as $product) {
    echo $product->productModel->name; // Ya is cargado
}

// Multiple relationships
$orders = Order::with(['orderItems', 'orderItems.product'])->get();

// Relationships anidadas
$products = Product::with([
    'productModel',
    'color',
    'sizes' => fn($q) => $q->where('is_active', true),
])->get();

// En el controlador API
public function index()
{
    return Product::with(['productModel', 'color'])
        ->active()
        ->get();
}
```

## Migrations

### Estructura de una Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Clave primaria
            $table->id();

            // Foreign keys
            $table->foreignId('model_id')
                  ->nullable()
                  ->constrained('item_categories')
                  ->nullOnDelete();

            // Campos de texto
            $table->string('title', 100);
            $table->text('description')->nullable();

            // Campos numeric
            $table->integer('price')->default(0);
            $table->decimal('discount', 5, 2)->default(0);

            // Campos booleanos
            $table->boolean('is_active')->default(true);

            // Campos JSON
            $table->json('photos')->nullable();
            $table->json('tags')->nullable();

            // Timestamps automatics
            $table->timestamps();

            // Soft deletes
            $table->softDeletes();

            // Indexs
            $table->index('is_active');
            $table->index(['model_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Commands de Migration

```bash
# Crear nueva migration
php artisan make:migration create_products_table
php artisan make:migration add_model_id_to_products_table

# Ejecutar migrations pendientes
php artisan migrate

# Revertir last migration
php artisan migrate:rollback

# Revertir todas las migrations
php artisan migrate:reset

# Resetear y volver a ejecutar
php artisan migrate:refresh

# Borrar todo y recrear (¡CUIDADO!)
php artisan migrate:fresh

# Con seeders
php artisan migrate:fresh --seed

# Ver estado de migrations
php artisan migrate:status
```

### Modificar Tables Existentes

```php
// Agregar column
Schema::table('products', function (Blueprint $table) {
    $table->foreignId('model_id')
          ->nullable()
          ->after('id')
          ->constrained('item_categories');
});

// Modificar column (requiere doctrine/dbal)
Schema::table('products', function (Blueprint $table) {
    $table->string('title', 200)->change();
});

// Eliminar column
Schema::table('products', function (Blueprint $table) {
    $table->dropColumn('old_column');
});

// Agregar index
Schema::table('orders', function (Blueprint $table) {
    $table->index('status');
    $table->index(['customer_phone', 'status']);
});

// Agregar soft deletes
Schema::table('products', function (Blueprint $table) {
    $table->softDeletes();
});
```

## Seeders

### Estructura de un Seeder

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemCategory;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            [
                'code' => 'basicas',
                'name' => 'Basic',
                'description' => 'Products de cotton aredar',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'premium',
                'name' => 'Premium',
                'description' => 'Products de alta calidad',
                'display_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($models as $model) {
            ItemCategory::updateOrCreate(
                ['code' => $model['code']],
                $model
            );
        }
    }
}
```

### DatabaseSeeder (Orquestador)

```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Orden importante por dependencias
        $this->call([
            ItemCategorySeeder::class,  // Primero catalogs
            SizeSeeder::class,
            ColorSeeder::class,
            PricingRuleSeeder::class,   // Depende de ItemCategory
            removedSeeder::class,
            ProductSeeder::class,       // Depende de catalogs
            NormalizationSeeder::class, // Vincula relationships
        ]);
    }
}
```

### Commands de Seeding

```bash
# Ejecutar todos los seeders
php artisan db:seed

# Ejecutar seeder specific
php artisan db:seed --class=ProductSeeder

# Migrar y sembrar
php artisan migrate:fresh --seed
```

## Query Builder y Consultas

### Consultas Basic

```php
// Obtener todos
$products = Product::all();

// Obtener por ID
$product = Product::find(1);
$product = Product::findOrFail(1); // Lanza 404 si no existe

// Primera coincidencia
$product = Product::where('is_active', true)->first();
$product = Product::where('is_active', true)->firstOrFail();

// Contar
$count = Product::where('is_active', true)->count();

// Existe?
$exists = Product::where('title', 'Product Basic')->exists();
```

### Condiciones WHERE

```php
// Igualdad
Product::where('is_active', true)->get();
Product::where('is_active', '=', true)->get();

// Comparaciones
Product::where('price', '>', 100)->get();
Product::where('price', '>=', 100)->get();
Product::where('price', '<', 200)->get();
Product::where('price', '!=', 0)->get();

// LIKE
Product::where('title', 'like', '%Basic%')->get();

// IN
Product::whereIn('model_id', [1, 2])->get();
Product::whereNotIn('status', ['cancelled', 'delivered'])->get();

// NULL
Product::whereNull('deleted_at')->get();
Product::whereNotNull('model_id')->get();

// Between
PricingRule::whereBetween('price', [150, 200])->get();

// Fechas
Order::whereDate('created_at', today())->get();
Order::whereMonth('created_at', 1)->get();
Order::whereYear('created_at', 2024)->get();
```

### Condiciones Compuestas

```php
// AND (encadenado)
Product::where('is_active', true)
       ->where('model_id', 1)
       ->get();

// OR
Product::where('model_id', 1)
       ->orWhere('model_id', 2)
       ->get();

// Grouping con closures
Product::where('is_active', true)
       ->where(function ($query) {
           $query->where('model_id', 1)
                 ->orWhere('model_id', 2);
       })
       ->get();

// SQL: WHERE is_active = true AND (model_id = 1 OR model_id = 2)
```

### Ordenamiento y Limits

```php
// Ordenar
Product::orderBy('created_at', 'desc')->get();
Product::orderBy('title', 'asc')->get();
Product::latest()->get(); // orderBy('created_at', 'desc')
Product::oldest()->get(); // orderBy('created_at', 'asc')

// Limitar
Product::take(10)->get();
Product::limit(10)->get();

// Offset (pagination manual)
Product::skip(10)->take(10)->get();

// Pagination automatic
$products = Product::paginate(15);
// En vista: {{ $products->links() }}
```

### Agregaciones

```php
// Contar
$count = Order::count();

// Suma
$total = Order::sum('total');

// Promedio
$avg = Order::avg('total');

// Maximum/Minimum
$max = PricingRule::max('price');
$min = PricingRule::min('price');

// Agrupar
$byStatus = Order::selectRaw('status, count(*) as total')
                 ->groupBy('status')
                 ->get();
```

## Scopes (Consultas Reutilizables)

### Scopes Locales

```php
// En el model
class Product extends Model
{
    // Scope sin parameters
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope con parameters
    public function scopeByModel($query, string $modelCode)
    {
        return $query->whereHas('productModel', fn($q) =>
            $q->where('code', $modelCode)
        );
    }

    // Scope con multiple condiciones
    public function scopeFeatured($query)
    {
        return $query->where('is_active', true)
                     ->where('is_featured', true)
                     ->orderBy('display_order');
    }
}

// Uso
$products = Product::active()->get();
$basicas = Product::active()->byModel('basicas')->get();
$featured = Product::featured()->take(6)->get();

// Encadenar scopes
$products = Product::active()
                   ->byModel('premium')
                   ->orderBy('title')
                   ->get();
```

### Scopes Globales

```php
// Para aplicar automaticmente a todas las consultas
class Product extends Model
{
    protected static function booted(): void
    {
        // Siempre excluir items inactivos
        static::addGlobalScope('active', function ($query) {
            $query->where('is_active', true);
        });
    }
}

// Ahora Product::all() solo trae activos

// Para incluir inactivos temporalmente
Product::withoutGlobalScope('active')->get();
```

## Accessors y Mutators

```php
class Product extends Model
{
    // Accessor: Transformar al leer
    public function getMainPhotoAttribute(): ?string
    {
        return $this->photos[0] ?? null;
    }

    // Accessor con formato
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    // Mutator: Transformar al escribir
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = ucfirst(strtolower($value));
    }

    // Laravel 9+ con Attribute class
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucfirst($value),
            set: fn (string $value) => strtolower($value),
        );
    }
}

// Uso
$product->main_photo;       // Accede al accessor
$product->formatted_price;  // "$200.00"
$product->title = "PLAYERA"; // Se guarda como "product"
```

## Models del Order Wizard

### Customer (Customer)

```php
class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'address', 'city', 'zip', 'notes',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
```

Patron clave: **Upsert por telefono**. Los customers se buscan por telefono (campo unique) y se actualizan/crean automaticamente al crear un order.

### Branch (Location)

```php
class Branch extends Model
{
    protected $fillable = [
        'name', 'address', 'city', 'state', 'zip',
        'phone', 'schedule', 'lat', 'lng', 'maps_url', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];
}
```

El campo `state` usa codigos de 2-3 letras (JA, NL, CMX) requeridos por la API de Envia.com para cotizar envios.

### Order — Campos de Envio

El model Order incluye campos de delivery y shipping agregados por el wizard:

```php
// Relationships
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class);
}

public function branch(): BelongsTo
{
    return $this->belongsTo(Branch::class);
}

// Accessor: saldo pendiente
public function getBalanceAttribute(): float
{
    return $this->total - $this->deposit;
}
```

Campos de envio en `$fillable`: `delivery_type`, `branch_id`, `shipping_address`, `shipping_district`, `shipping_city`, `shipping_state`, `shipping_zip`, `shipping_carrier`, `shipping_service`, `shipping_cost`, `shipping_delivery_estimate`.

---

## Eventos del Model

```php
class OrderLine extends Model
{
    protected static function booted(): void
    {
        // Antes de crear
        static::creating(function (OrderLine $item) {
            $item->subtotal = $item->quantity * $item->unit_price;
        });

        // After de crear
        static::created(function (OrderLine $item) {
            $item->order->recalculateTotals();
        });

        // Antes de actualizar
        static::updating(function (OrderLine $item) {
            $item->subtotal = $item->quantity * $item->unit_price;
        });

        // After de eliminar
        static::deleted(function (OrderLine $item) {
            $item->order->recalculateTotals();
        });
    }
}
```

## Siguiente Step

Continue con [04-api-development.md](./04-api-development.md) para aprender sobre desarrollo de APIs.
