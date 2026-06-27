# 02 - Arquitectura del Proyecto

Esta guide explica la estructura del proyecto, patrones de design y decisiones architectural.

## Vision General

SaaS Template es una application de e-commerce para venta de products con SaaS services (Direct to Film). El proyecto sigue la arquitectura MVC de Laravel con capas adicionales de services y componentes interactivos.

## Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENTE                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │   Browser    │  │   Mobile     │  │   API Consumers      │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CAPA DE PRESENTATION                        │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    Laravel Router                         │   │
│  │              routes/web.php  |  routes/api.php            │   │
│  └──────────────────────────────────────────────────────────┘   │
│         │                    │                    │              │
│         ▼                    ▼                    ▼              │
│  ┌─────────────┐    ┌──────────────┐    ┌───────────────────┐   │
│  │  Livewire   │    │  Controllers │    │  Filament Admin   │   │
│  │  Components │    │(API/Web/     │    │    Resources      │   │
│  └─────────────┘    │ Portal)      │    └───────────────────┘   │
│                     └──────────────┘                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CAPA DE NEGOCIO                             │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                      Services                             │   │
│  │  PricingCalculator | PdfGenerator | EnviaShippingService  │   │
│  │  Payment GatewayPaymentService | NotificationService              │   │
│  └──────────────────────────────────────────────────────────┘   │
│                              │                                   │
│                              ▼                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                   Eloquent Models                         │   │
│  │   Product | Order | Customer | Payment | Branch | etc. (15)            │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CAPA DE DATOS                               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    PostgreSQL 15                          │   │
│  │                    30+ tables                             │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

## Estructura de Directorios

```
saas-template-laravel/
│
├── app/                              # Code de application
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                  # Controladores REST
│   │   │   │   ├── PricingController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── QuoteController.php
│   │   │   │   └── ContentController.php
│   │   │   ├── CustomerAuthController.php      # Login/logout customer
│   │   │   ├── CustomerPortalController.php    # Portal Mi Cuenta
│   │   │   ├── CustomerAddressController.php   # CRUD direcciones
│   │   │   ├── Payment GatewayWebhookController.php    # Webhooks de payment
│   │   │   └── PageController.php              # Pages web
│   │   └── Requests/                 # Form Requests (validation)
│   │
│   ├── Models/                       # Models Eloquent (15)
│   │   ├── Product.php               # Item principal
│   │   ├── ItemCategory.php          # Tipos: basic, premium
│   │   ├── Order.php                 # Orders con status flow
│   │   ├── OrderLine.php             # Lines de order normalizadas
│   │   ├── Customer.php              # Customers (Authenticatable)
│   │   ├── CustomerAddress.php       # Direcciones de entrega
│   │   ├── Payment.php               # Payments (cash, tarjeta, Wire Transfer)
│   │   ├── Branch.php                # Locationes
│   │   ├── Quote.php                 # Quotes
│   │   ├── PricingRule.php           # Reglas de precio por volumen
│   │   ├── removed.php        # Service limits
│   │   ├── Size.php                  # Sizes normalizadas
│   │   ├── Color.php                 # Colors normalizados
│   │   ├── BusinessConfig.php        # Configuration dynamic
│   │   └── User.php                  # Users admin (Filament)
│   │
│   ├── Services/                     # Logic de negocio
│   │   ├── PricingCalculator.php     # Calculation de precios
│   │   ├── PdfGenerator.php          # Generation de PDFs
│   │   ├── EnviaShippingService.php  # Quote de shipments (Envia.com)
│   │   ├── Payment GatewayPaymentService.php # Payment links (Payment Gateway)
│   │   └── NotificationService.php   # Notificaciones Messaging
│   │
│   ├── Livewire/                     # Componentes interactivos
│   │   ├── QuoteCalculator.php       # Cotizador completo
│   │   ├── QuickCalculator.php       # Cotizador fast
│   │   ├── ProductCatalog.php        # Catalog
│   │   ├── ShoppingCart.php          # Carrito
│   │   └── Layout/
│   │       ├── Header.php            # Header con carrito
│   │       └── Footer.php            # Footer
│   │
│   ├── Filament/                     # Panel administrativo
│   │   ├── Resources/                # CRUD Resources (9)
│   │   │   ├── OrderResource.php     # Orders + Wizard 5 pasos
│   │   │   ├── ProductResource.php
│   │   │   ├── CustomerResource.php  # Customers
│   │   │   ├── BranchResource.php    # Locationes
│   │   │   ├── ItemCategoryResource.php
│   │   │   ├── SizeResource.php
│   │   │   ├── ColorResource.php
│   │   │   ├── PricingRuleResource.php
│   │   │   └── removedResource.php
│   │   └── Widgets/                  # Dashboard widgets (7)
│   │
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── Filament/
│           └── AdminPanelProvider.php
│
├── database/
│   ├── migrations/                   # 30+ migrations
│   ├── seeders/                      # Datos iniciales
│   │   ├── DatabaseSeeder.php        # Orquestador
│   │   ├── ItemCategorySeeder.php
│   │   ├── SizeSeeder.php
│   │   ├── ColorSeeder.php
│   │   ├── PricingRuleSeeder.php
│   │   ├── removedSeeder.php
│   │   ├── ProductSeeder.php
│   │   └── NormalizationSeeder.php
│   └── factories/                    # Factories para testing
│
├── resources/
│   ├── views/
│   │   ├── components/               # Componentes Blade
│   │   │   └── layouts/
│   │   │       └── app.blade.php     # Layout principal
│   │   ├── livewire/                 # Vistas Livewire
│   │   │   ├── quote-calculator.blade.php
│   │   │   ├── quick-calculator.blade.php
│   │   │   ├── product-catalog.blade.php
│   │   │   ├── shopping-cart.blade.php
│   │   │   └── layout/
│   │   ├── customer/                 # Portal del Customer (Mi Cuenta)
│   │   │   ├── portal.blade.php      # Layout principal
│   │   │   ├── login.blade.php       # Login por phone
│   │   │   └── tabs/                 # Tabs del portal
│   │   │       ├── orders.blade.php
│   │   │       ├── order-detail.blade.php
│   │   │       ├── profile.blade.php
│   │   │       ├── addresses.blade.php
│   │   │       ├── payments.blade.php
│   │   │       └── settings.blade.php
│   │   ├── pages/                    # Pages completas
│   │   │   ├── home.blade.php
│   │   │   ├── cotizar.blade.php
│   │   │   ├── catalogo.blade.php
│   │   │   ├── item.blade.php
│   │   │   ├── carrito.blade.php
│   │   │   ├── contacto.blade.php
│   │   │   ├── payment-exitoso.blade.php
│   │   │   └── payment-fallido.blade.php
│   │   └── pdf/                      # Plantillas PDF
│   │       └── quote.blade.php
│   ├── css/                          # Tailwind CSS
│   ├── js/                           # JavaScript
│   └── markdown/                     # Contenido FAQ/policies
│
├── routes/
│   ├── web.php                       # Rutas web
│   └── api.php                       # Rutas API REST
│
├── config/                           # Configuration Laravel
├── tests/                            # PHPUnit tests
├── public/                           # Web root
├── storage/                          # Uploads, logs, cache
│
├── scripts/
│   └── serve.sh                      # Script de desarrollo
│
├── docs/                             # Documentation
│   ├── guides/                       # Guides (este directorio)
│   ├── quick-start.md
│   ├── api-reference.md
│   └── filament-guide.md
│
├── composer.json                     # Dependencias PHP
├── package.json                      # Dependencias Node
├── .env.example                      # Plantilla de environment
└── phpunit.xml                       # Configuration tests
```

## Patrones de Design Utilizados

### 1. MVC (Model-View-Controller)

Laravel implementa MVC de forma nativa:

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Request   │ ──▶ │ Controller  │ ──▶ │    View     │
└─────────────┘     └─────────────┘     └─────────────┘
                          │
                          ▼
                    ┌─────────────┐
                    │    Model    │
                    └─────────────┘
```

**Example en el proyecto:**

```php
// Controller (app/Http/Controllers/Api/ProductController.php)
public function index(Request $request)
{
    $products = Product::with(['productModel', 'color'])
        ->active()
        ->get();

    return response()->json(['data' => $products]);
}

// Model (app/Models/Product.php)
class Product extends Model
{
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

// View (resources/views/pages/catalogo.blade.php)
@foreach($products as $product)
    <x-product-card :product="$product" />
@endforeach
```

### 2. Service Layer Pattern

Logic de negocio compleja se extrae a services:

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│ Controller  │ ──▶ │   Service   │ ──▶ │    Model    │
└─────────────┘     └─────────────┘     └─────────────┘
```

**Example:**

```php
// app/Services/PricingCalculator.php
class PricingCalculator
{
    public function calculate(string $model, string $size, int $quantity): array
    {
        $sizeType = PricingRule::getSizeType($size);
        $rule = PricingRule::active()
            ->forModel($model)
            ->forSizeType($sizeType)
            ->where('min_qty', '<=', $quantity)
            ->where(fn($q) => $q->whereNull('max_qty')->orWhere('max_qty', '>=', $quantity))
            ->first();

        return [
            'unitPrice' => $rule->price,
            'subtotal' => $rule->price * $quantity,
            'deposit50' => ($rule->price * $quantity) / 2,
        ];
    }
}

// Uso en Controller
public function calculate(Request $request, PricingCalculator $calculator)
{
    return $calculator->calculate(
        $request->model,
        $request->size,
        $request->quantity
    );
}
```

### 3. Repository Pattern (Implicit via Eloquent)

Eloquent acts como repositorio con Active Record:

```php
// Consultas encapsuladas en el model
class Order extends Model
{
    // Scopes como methods de repositorio
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'delivered']);
    }
}

// Uso
$pendingOrders = Order::pending()->get();
$activeOrders = Order::active()->with('items')->get();
```

### 4. State Machine Pattern (Orders)

El model Order implementa un workflow de estados:

```
┌──────────┐    confirm()    ┌───────────┐   startProduction()   ┌───────────────┐
│ pending  │ ──────────────▶ │ confirmed │ ────────────────────▶ │ in_production │
└──────────┘                 └───────────┘                       └───────────────┘
     │                                                                  │
     │ cancel()                                               markReady()│
     ▼                                                                  ▼
┌───────────┐                                                   ┌───────────┐
│ cancelled │                                                   │   ready   │
└───────────┘                                                   └───────────┘
                                                                       │
                                                              deliver()│
                                                                       ▼
                                                               ┌───────────┐
                                                               │ delivered │
                                                               └───────────┘
```

**Implementation:**

```php
class Order extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PRODUCTION = 'in_production';
    const STATUS_READY = 'ready';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

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

    // ... more transiciones
}
```

### 5. Component Pattern (Livewire)

Componentes auto-contenidos con estado y comportamiento:

```php
// app/Livewire/QuoteCalculator.php
class QuoteCalculator extends Component
{
    // Estado
    public string $model = 'basicas';
    public string $size = 'MD';
    public int $quantity = 1;

    // Propiedades computadas
    #[Computed]
    public function unitPrice(): int
    {
        return PricingCalculator::calculate($this->model, $this->size, $this->quantity);
    }

    // Acciones
    public function increment(): void
    {
        $this->quantity++;
    }

    public function addToCart(): void
    {
        // Logic de carrito
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.quote-calculator');
    }
}
```

## Model de Datos (Normalizado)

### Diagrama Entidad-Relationship

```
┌─────────────────┐       ┌─────────────────┐
│  ItemCategory   │       │      Size       │
│─────────────────│       │─────────────────│
│ id              │       │ id              │
│ code (unique)   │       │ code (unique)   │
│ name            │       │ name            │
│ description     │       │ size_type       │
│ display_order   │       │ display_order   │
│ is_active       │       │ is_active       │
└────────┬────────┘       └────────┬────────┘
         │                         │
         │ 1:N                     │ M:N
         │                         │
         ▼                         ▼
┌─────────────────┐       ┌─────────────────┐
│    Product      │◀──────│  product_sizes  │
│─────────────────│       │─────────────────│
│ id              │       │ product_id (FK) │
│ model_id (FK)   │       │ size_id (FK)    │
│ color_id (FK)   │       │ stock           │
│ title           │       │ price_modifier  │
│ description     │       └─────────────────┘
│ technique       │
│ material        │
│ photos (JSON)   │       ┌─────────────────┐
│ is_active       │       │     Color       │
└────────┬────────┘       │─────────────────│
         │                │ id              │
         │ 1:N            │ name (unique)   │
         │                │ hex_code        │
         ▼                │ display_order   │
┌─────────────────┐       │ is_active       │
│   OrderLine     │       └─────────────────┘
│─────────────────│
│ id              │
│ order_id (FK)   │       ┌─────────────────┐
│ product_id (FK) │       │  PricingRule    │
│ size            │       │─────────────────│
│ color           │       │ id              │
│ quantity        │       │ model_id (FK)   │
│ unit_price      │       │ size_type       │
│ subtotal        │       │ min_qty         │
│ customization   │       │ max_qty         │
└────────┬────────┘       │ price           │
         │                │ label           │
         │ N:1            │ savings         │
         │                │ is_active       │
         ▼                └─────────────────┘
┌─────────────────┐
│     Order       │       ┌─────────────────┐
│─────────────────│       │ removed  │
│ id              │       │─────────────────│
│ customer_name   │       │ id              │
│ customer_phone  │       │ size            │
│ customer_email  │       │ side            │
│ subtotal        │       │ max_width       │
│ deposit         │       │ max_height      │
│ total           │       │ label           │
│ status          │       │ is_active       │
│ notes           │       └─────────────────┘
│ estimated_delivery │
└─────────────────┘
```

## Flujo de Datos Principal

### Flujo de Quote

```
1. User ingresa datos en QuoteCalculator (Livewire)
   │
   ▼
2. Componente llama a PricingCalculator::calculate()
   │
   ▼
3. Service consulta PricingRule y removed
   │
   ▼
4. Retorna precios y validaciones
   │
   ▼
5. User agrega al carrito (Session)
   │
   ▼
6. Carrito dispara evento 'cart-updated'
   │
   ▼
7. Header actualiza contador de items
```

### Flujo de Order (API)

```
1. Customer sends POST /api/orders
   │
   ▼
2. OrderController valida datos
   │
   ▼
3. Crea Order con OrderLines
   │
   ▼
4. Auto-calcula subtotal, deposit, total
   │
   ▼
5. Retorna orden creada (status: pending)
   │
   ▼
6. Admin usa Filament para cambiar estados
   │
   ▼
7. Order::confirm(), Order::startProduction(), etc.
```

## Configuration Dynamic

El sistema usa `BusinessConfig` como key-value store:

```php
// Obtener configuration
$messaging = BusinessConfig::get('messaging_number', '5212345678901');
$terms = BusinessConfig::get('payment_terms', 'Payment al 50% por adelantado');

// Establecer configuration
BusinessConfig::set('delivery_days', 5, 'integer', 'shipping');

// Obtener grupo completo
$shippingConfig = BusinessConfig::getGroup('shipping');
```

## Siguiente Step

Continue con [03-models-database.md](./03-models-database.md) para profundizar en los models y base de datos.
