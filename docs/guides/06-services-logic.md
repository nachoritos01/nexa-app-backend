# 06 - Services y Logic de Negocio

Esta guide cubre la capa de services, patrones de design y organization de la logic de negocio.

## ¿Por what una Capa de Services?

La capa de services separa la logic de negocio de los controladores y models:

```
┌─────────────────────────────────────────────────────────────┐
│                    SIN SERVICIOS ❌                          │
│                                                             │
│   Controller                                                │
│   ├── Validation                                            │
│   ├── Logic de negocio (mezclada)                         │
│   ├── Operaciones de BD                                     │
│   └── Formateo de response                                 │
│                                                             │
│   Problemas:                                                │
│   - Controladores enormes                                   │
│   - Code duplicado                                        │
│   - Difficult de testear                                      │
│   - Difficult de reutilizar                                   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    CON SERVICIOS ✅                          │
│                                                             │
│   Controller          Service              Model            │
│   ├── Validation      ├── Logic          ├── Persistencia │
│   ├── Llamar service │   de negocio      ├── Relationships   │
│   └── Response       ├── Calculations        └── Queries      │
│                       └── Reglas                            │
│                                                             │
│   Beneficios:                                               │
│   - Responsabilidad unique                                   │
│   - Easy de testear                                        │
│   - Reutilizable                                            │
│   - Code limpio                                           │
└─────────────────────────────────────────────────────────────┘
```

## Services del Proyecto

### PricingCalculator

```php
<?php

namespace App\Services;

use App\Models\PricingRule;

class PricingCalculator
{
    /**
     * Calcular precio para una configuration
     *
     * @throws \InvalidArgumentException Si no hay regla de precio
     */
    public function calculate(string $model, string $size, int $quantity): array
    {
        $sizeType = PricingRule::getSizeType($size);

        $rule = PricingRule::active()
            ->forModel($model)
            ->forSizeType($sizeType)
            ->where('min_qty', '<=', $quantity)
            ->where(fn($q) => $q->whereNull('max_qty')
                               ->orWhere('max_qty', '>=', $quantity))
            ->orderBy('min_qty', 'desc')
            ->first();

        if (!$rule) {
            throw new \InvalidArgumentException(
                "No se found regla de precio para {$model}/{$sizeType}/{$quantity}"
            );
        }

        $subtotal = $rule->price * $quantity;

        return [
            'model' => $model,
            'size' => $size,
            'sizeType' => $sizeType,
            'quantity' => $quantity,
            'unitPrice' => $rule->price,
            'subtotal' => $subtotal,
            'deposit50' => (int) ($subtotal * 0.5),
            'deliveryDays' => 5,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtener sugerencia de cantidad para mejor precio
     */
    public function getSuggestion(
        string $model,
        string $size,
        int $currentQty
    ): ?array {
        $sizeType = PricingRule::getSizeType($size);
        $tiers = $this->getTiers($model, $size);

        // Encontrar tier actual
        $currentTier = null;
        foreach ($tiers['tiers'] as $tier) {
            if ($currentQty >= $tier['min_qty'] &&
                ($tier['max_qty'] === null || $currentQty <= $tier['max_qty'])) {
                $currentTier = $tier;
                break;
            }
        }

        if (!$currentTier) {
            return null;
        }

        // Buscar siguiente tier
        foreach ($tiers['tiers'] as $tier) {
            if ($tier['min_qty'] > $currentQty) {
                $savingsPerPiece = $currentTier['price'] - $tier['price'];
                $additionalPieces = $tier['min_qty'] - $currentQty;
                $totalSavings = $savingsPerPiece * $tier['min_qty'];

                return [
                    'currentQuantity' => $currentQty,
                    'suggestedQuantity' => $tier['min_qty'],
                    'currentUnitPrice' => $currentTier['price'],
                    'newUnitPrice' => $tier['price'],
                    'savingsPerPiece' => $savingsPerPiece,
                    'totalSavings' => $totalSavings,
                    'additionalPieces' => $additionalPieces,
                    'message' => sprintf(
                        'Agrega %d piezas more y ahorra $%d por pieza',
                        $additionalPieces,
                        $savingsPerPiece
                    ),
                ];
            }
        }

        return null;
    }

    /**
     * Obtener todos los niveles de precio
     */
    public function getTiers(string $model, string $size): array
    {
        $sizeType = PricingRule::getSizeType($size);

        $tiers = PricingRule::active()
            ->forModel($model)
            ->forSizeType($sizeType)
            ->orderBy('min_qty')
            ->get()
            ->map(fn($rule) => [
                'min_qty' => $rule->min_qty,
                'max_qty' => $rule->max_qty,
                'price' => $rule->price,
                'label' => $rule->label,
                'savings' => $rule->savings,
            ])
            ->toArray();

        return [
            'model' => $model,
            'size' => $size,
            'sizeType' => $sizeType,
            'tiers' => $tiers,
            'recommendedRange' => $this->getRecommendedRange($tiers),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Obtener rango recommended (mejor precio)
     */
    private function getRecommendedRange(array $tiers): ?array
    {
        if (empty($tiers)) {
            return null;
        }

        // El last tier suele tener el mejor precio
        $bestTier = end($tiers);

        return [
            'minQuantity' => $bestTier['min_qty'],
            'price' => $bestTier['price'],
            'label' => $bestTier['label'],
        ];
    }
}
```

### PdfGenerator

```php
<?php

namespace App\Services;

use App\Models\BusinessConfig;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfGenerator
{
    /**
     * Generar PDF de quote y guardarlo
     */
    public function generateQuote(Quote $quote): string
    {
        $pdf = $this->generateQuoteInline($quote);

        // Crear directorio si no existe
        $directory = 'quotes';
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Name unique del file
        $filename = sprintf(
            '%s/cotizacion-%s-%s.pdf',
            $directory,
            $quote->id,
            now()->format('Ymd-His')
        );

        // Guardar PDF
        Storage::put($filename, $pdf->output());

        // Actualizar quote con la ruta
        $quote->update(['pdf_path' => $filename]);

        return $filename;
    }

    /**
     * Generar PDF en memoria (para descarga directa)
     */
    public function generateQuoteInline(Quote $quote): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'quote' => $quote,
            'business' => $this->getBusinessInfo(),
            'generatedAt' => now(),
        ];

        return Pdf::loadView('pdf.quote', $data)
            ->setPaper('letter')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
            ]);
    }

    /**
     * Obtener information del negocio para el PDF
     */
    public function getBusinessInfo(): array
    {
        return [
            'name' => BusinessConfig::get('brand_name', 'SaaS Template SaaS'),
            'slogan' => BusinessConfig::get('brand_slogan', 'Service SaaS de calidad'),
            'messaging' => BusinessConfig::get('messaging_number', '5212345678901'),
            'email' => BusinessConfig::get('contact_email', 'contacto@saas-template.com'),
            'address' => BusinessConfig::get('pickup_address', 'Ciudad de Mexico'),
            'terms' => [
                'deposit' => BusinessConfig::get('deposit_percentage', 50) . '% de anticipo',
                'delivery' => BusinessConfig::get('delivery_days', 5) . ' days business',
                'validity' => BusinessConfig::get('quote_validity_days', 7) . ' days de vigencia',
            ],
            'payment' => [
                'bank' => BusinessConfig::get('bank_name', 'BBVA'),
                'account' => BusinessConfig::get('bank_account', '**** **** **** 1234'),
                'clabe' => BusinessConfig::get('bank_clabe', '012345678901234567'),
            ],
        ];
    }

    /**
     * Descargar PDF existente
     */
    public function download(Quote $quote): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (!$quote->pdf_path || !Storage::exists($quote->pdf_path)) {
            $this->generateQuote($quote);
        }

        return Storage::download(
            $quote->pdf_path,
            "cotizacion-{$quote->id}.pdf"
        );
    }
}
```

## Crear un Nuevo Service

### Step 1: Crear el File

```bash
# Crear directorio si no existe
mkdir -p app/Services

# Crear file
touch app/Services/OrderProcessor.php
```

### Step 2: Implementar el Service

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderProcessor
{
    public function __construct(
        private PricingCalculator $pricingCalculator
    ) {}

    /**
     * Crear una orden completa con items
     */
    public function createOrder(array $customerData, array $items): Order
    {
        return DB::transaction(function () use ($customerData, $items) {
            // Crear orden
            $order = Order::create([
                'customer_name' => $customerData['name'],
                'customer_phone' => $customerData['phone'],
                'customer_email' => $customerData['email'] ?? null,
                'status' => Order::STATUS_PENDING,
            ]);

            // Procesar cada item
            foreach ($items as $itemData) {
                $this->addItem($order, $itemData);
            }

            // Recalcular totales
            $order->recalculateTotals();

            return $order->fresh(['orderItems.product']);
        });
    }

    /**
     * Agregar item a una orden
     */
    public function addItem(Order $order, array $itemData): OrderLine
    {
        $product = Product::findOrFail($itemData['product_id']);

        // Calcular precio
        $pricing = $this->pricingCalculator->calculate(
            $product->model_code ?? 'basicas',
            $itemData['size'],
            $itemData['quantity']
        );

        return $order->orderItems()->create([
            'product_id' => $product->id,
            'size' => $itemData['size'],
            'color' => $itemData['color'],
            'quantity' => $itemData['quantity'],
            'unit_price' => $pricing['unitPrice'],
            'subtotal' => $pricing['subtotal'],
            'customization' => $itemData['customization'] ?? null,
        ]);
    }

    /**
     * Actualizar cantidad de un item
     */
    public function updateItemQuantity(OrderLine $item, int $newQuantity): OrderLine
    {
        $product = $item->product;

        $pricing = $this->pricingCalculator->calculate(
            $product->model_code ?? 'basicas',
            $item->size,
            $newQuantity
        );

        $item->update([
            'quantity' => $newQuantity,
            'unit_price' => $pricing['unitPrice'],
            'subtotal' => $pricing['subtotal'],
        ]);

        $item->order->recalculateTotals();

        return $item->fresh();
    }

    /**
     * Procesar payment de anticipo
     */
    public function processDeposit(Order $order, float $amount): Order
    {
        if ($amount < $order->deposit) {
            throw new \InvalidArgumentException(
                "El monto minimum de anticipo es \${$order->deposit}"
            );
        }

        $order->update([
            'deposit_paid' => $amount,
            'deposit_paid_at' => now(),
        ]);

        // Confirmar automaticmente si se paid el anticipo
        if ($order->status === Order::STATUS_PENDING) {
            $order->confirm();
        }

        return $order->fresh();
    }

    /**
     * Cancelar orden con validaciones
     */
    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        // Validar que se puede cancelar
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            throw new \InvalidArgumentException(
                "No se puede cancelar una orden {$order->status}"
            );
        }

        // Si ya is en operations, requerir reason
        if ($order->status === Order::STATUS_IN_PRODUCTION && !$reason) {
            throw new \InvalidArgumentException(
                "Debe proporcionar una reason para cancelar una orden en operations"
            );
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $order->fresh();
    }

    /**
     * Obtener statistics de orders
     */
    public function getStatistics(): array
    {
        return [
            'total' => Order::count(),
            'pending' => Order::pending()->count(),
            'in_production' => Order::byStatus(Order::STATUS_IN_PRODUCTION)->count(),
            'ready' => Order::byStatus(Order::STATUS_READY)->count(),
            'delivered_today' => Order::byStatus(Order::STATUS_DELIVERED)
                ->whereDate('delivered_at', today())
                ->count(),
            'revenue_today' => Order::byStatus(Order::STATUS_DELIVERED)
                ->whereDate('delivered_at', today())
                ->sum('total'),
            'revenue_month' => Order::byStatus(Order::STATUS_DELIVERED)
                ->whereMonth('delivered_at', now()->month)
                ->sum('total'),
        ];
    }
}
```

### Step 3: Registrar en Service Provider (Optional)

Para services simples, Laravel los resuelve automaticmente. Para configuraciones especiales:

```php
// app/Providers/AppServiceProvider.php

use App\Services\OrderProcessor;
use App\Services\PricingCalculator;

public function register(): void
{
    // Singleton: misma instancia en toda la application
    $this->app->singleton(PricingCalculator::class);

    // Bind con dependencias personalizadas
    $this->app->bind(OrderProcessor::class, function ($app) {
        return new OrderProcessor(
            $app->make(PricingCalculator::class)
        );
    });
}
```

### Step 4: Usar el Service

```php
// En controlador (injection en constructor)
class OrderController extends Controller
{
    public function __construct(
        private OrderProcessor $orderProcessor
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer.name' => 'required|string',
            'customer.phone' => 'required|string',
            'items' => 'required|array|min:1',
        ]);

        $order = $this->orderProcessor->createOrder(
            $validated['customer'],
            $validated['items']
        );

        return response()->json([
            'message' => 'Orden creada',
            'data' => $order,
        ], 201);
    }
}

// En Livewire
class OrderForm extends Component
{
    public function submit(OrderProcessor $processor): void
    {
        $order = $processor->createOrder(
            $this->customerData,
            $this->items
        );

        $this->dispatch('order-created', orderId: $order->id);
    }
}

// En Tinker o tests
$processor = app(OrderProcessor::class);
$order = $processor->createOrder($customer, $items);
```

## Patrones Comunes

### Repository Pattern

Para abstraer still more las consultas de BD:

```php
<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function all(): Collection
    {
        return Product::with(['productModel', 'color'])->get();
    }

    public function active(): Collection
    {
        return Product::active()
            ->with(['productModel', 'color'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(int $id): ?Product
    {
        return Product::with(['productModel', 'color', 'sizes'])->find($id);
    }

    public function findByModel(string $modelCode): Collection
    {
        return Product::byModel($modelCode)->active()->get();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function search(string $query): Collection
    {
        return Product::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->active()
            ->get();
    }
}
```

### Action Classes

Para operaciones specifics y complejas:

```php
<?php

namespace App\Actions;

use App\Models\Order;
use App\Notifications\OrderConfirmedNotification;

class ConfirmOrderAction
{
    public function execute(Order $order): Order
    {
        // Validar
        if ($order->status !== Order::STATUS_PENDING) {
            throw new \InvalidArgumentException(
                "Solo se pueden confirmar orders pendientes"
            );
        }

        // Actualizar estado
        $order->confirm();

        // Enviar notification
        $order->notify(new OrderConfirmedNotification());

        // Log
        activity()
            ->performedOn($order)
            ->log('Orden confirmada');

        return $order->fresh();
    }
}

// Uso
$action = new ConfirmOrderAction();
$order = $action->execute($order);

// O con resolve
app(ConfirmOrderAction::class)->execute($order);
```

### DTOs (Data Transfer Objects)

Para estructurar datos:

```php
<?php

namespace App\DTOs;

readonly class PricingResult
{
    public function __construct(
        public string $model,
        public string $size,
        public string $sizeType,
        public int $quantity,
        public int $unitPrice,
        public int $subtotal,
        public int $deposit,
        public int $deliveryDays,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'],
            size: $data['size'],
            sizeType: $data['sizeType'],
            quantity: $data['quantity'],
            unitPrice: $data['unitPrice'],
            subtotal: $data['subtotal'],
            deposit: $data['deposit50'],
            deliveryDays: $data['deliveryDays'],
        );
    }

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'size' => $this->size,
            'sizeType' => $this->sizeType,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'subtotal' => $this->subtotal,
            'deposit' => $this->deposit,
            'deliveryDays' => $this->deliveryDays,
        ];
    }
}

// Uso en service
public function calculate(string $model, string $size, int $quantity): PricingResult
{
    // ... logic ...
    return new PricingResult(
        model: $model,
        size: $size,
        sizeType: $sizeType,
        quantity: $quantity,
        unitPrice: $rule->price,
        subtotal: $subtotal,
        deposit: (int) ($subtotal * 0.5),
        deliveryDays: 5,
    );
}
```

## Manejo de Errores en Services

### Excepciones Personalizadas

```php
<?php

namespace App\Exceptions;

class PricingNotFoundException extends \Exception
{
    public function __construct(
        public string $model,
        public string $sizeType,
        public int $quantity
    ) {
        parent::__construct(
            "No pricing rule found for {$model}/{$sizeType} at quantity {$quantity}"
        );
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'pricing_not_found',
            'message' => $this->getMessage(),
            'details' => [
                'model' => $this->model,
                'sizeType' => $this->sizeType,
                'quantity' => $this->quantity,
            ],
        ], 404);
    }
}
```

```php
// En service
if (!$rule) {
    throw new PricingNotFoundException($model, $sizeType, $quantity);
}

// En controlador (se maneja automaticmente por el render())
try {
    $result = $this->calculator->calculate($model, $size, $quantity);
    return response()->json(['data' => $result]);
} catch (PricingNotFoundException $e) {
    // La exception se renderiza automaticmente
    throw $e;
}
```

### Result Objects

Para resultados que pueden fallar sin excepciones:

```php
<?php

namespace App\Support;

readonly class Result
{
    private function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $error = null,
    ) {}

    public static function success(mixed $data): self
    {
        return new self(success: true, data: $data);
    }

    public static function failure(string $error): self
    {
        return new self(success: false, error: $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }
}

// Uso
public function processPayment(Order $order, float $amount): Result
{
    if ($amount < $order->deposit) {
        return Result::failure("Monto insuficiente. Minimum: \${$order->deposit}");
    }

    // Procesar...

    return Result::success($order);
}

// En controlador
$result = $this->processor->processPayment($order, $amount);

if ($result->isFailure()) {
    return response()->json(['error' => $result->error], 400);
}

return response()->json(['data' => $result->data]);
```

## Testing de Services

```php
<?php

namespace Tests\Unit\Services;

use App\Models\PricingRule;
use App\Services\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PricingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PricingCalculator();

        // Crear datos de test
        PricingRule::create([
            'model' => 'basicas',
            'size_type' => 'standard',
            'min_qty' => 1,
            'max_qty' => 5,
            'price' => 200,
            'label' => '1-5 piezas',
            'is_active' => true,
        ]);

        PricingRule::create([
            'model' => 'basicas',
            'size_type' => 'standard',
            'min_qty' => 6,
            'max_qty' => null,
            'price' => 180,
            'label' => '6+ piezas',
            'is_active' => true,
        ]);
    }

    public function test_calculates_price_for_small_quantity(): void
    {
        $result = $this->calculator->calculate('basicas', 'MD', 3);

        $this->assertEquals(200, $result['unitPrice']);
        $this->assertEquals(600, $result['subtotal']);
        $this->assertEquals(300, $result['deposit50']);
    }

    public function test_calculates_price_for_bulk_quantity(): void
    {
        $result = $this->calculator->calculate('basicas', 'MD', 10);

        $this->assertEquals(180, $result['unitPrice']);
        $this->assertEquals(1800, $result['subtotal']);
    }

    public function test_throws_exception_for_invalid_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->calculate('invalid', 'MD', 1);
    }

    public function test_suggestion_returns_next_tier(): void
    {
        $suggestion = $this->calculator->getSuggestion('basicas', 'MD', 4);

        $this->assertNotNull($suggestion);
        $this->assertEquals(6, $suggestion['suggestedQuantity']);
        $this->assertEquals(20, $suggestion['savingsPerPiece']);
    }

    public function test_suggestion_returns_null_at_best_tier(): void
    {
        $suggestion = $this->calculator->getSuggestion('basicas', 'MD', 10);

        $this->assertNull($suggestion);
    }
}
```

## EnviaShippingService (Integration API Externa)

Service que cotiza envios usando la API de [Envia.com](https://envia.com). Example real de integration con API externa.

### Estructura

```php
<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviaShippingService
{
    private string $token;
    private string $baseUrl;

    private const CARRIERS = ['fedex', 'estafeta', 'dhl', 'ups', 'redpack', 'paquetexpress'];

    public function __construct()
    {
        // IMPORTANTE: usar config(), nunca env() directo
        $this->token = (string) config('services.envia.token');
        $this->baseUrl = (string) config('services.envia.url');
    }

    public function quote(Branch $origin, array $destination, array $packages = []): array
    {
        // ...
    }
}
```

### Patrones Clave

#### 1. HTTP Pool (Requests Concurrentes)

Envia.com requiere una request por carrier. En lugar de hacer 6 requests secuenciales, usamos `Http::pool()`:

```php
$responses = Http::pool(fn (Pool $pool) =>
    collect(self::CARRIERS)->map(
        fn (string $carrier) => $pool->as($carrier)
            ->withToken($this->token)
            ->timeout(10)
            ->post("{$this->baseUrl}/ship/rate/", [
                'origin' => $originPayload,
                'destination' => $destPayload,
                'packages' => $packagesPayload,
                'shipment' => ['carrier' => $carrier, 'type' => '1'],
                'settings' => ['currency' => 'USD'],
            ])
    )->all()
);
```

Ventajas:
- 6 requests en paralelo en lugar de secuenciales
- `$pool->as($carrier)` nombra cada response para identificarla
- `->timeout(10)` evita bloquear si un carrier no responde

#### 2. Manejo de Errores Robusto

En un pool, responses fallidas llegan como `\Throwable`:

```php
foreach ($responses as $carrier => $response) {
    // Un carrier puede fallar sin afectar a los demas
    if ($response instanceof \Throwable) {
        Log::warning("Carrier {$carrier} failed", ['error' => $response->getMessage()]);
        continue;
    }

    if (! $response->successful()) {
        continue;
    }

    // Procesar response exitosa...
}
```

#### 3. Configuracion via config/services.php

```php
// config/services.php
'envia' => [
    'token' => env('ENVIA_API_TOKEN'),
    'url' => env('ENVIA_API_URL', 'https://api-test.envia.com'),
],
```

Nunca usar `env()` fuera de files `config/*.php`. Con `config:cache` activo, `env()` retorna `null`.

#### 4. Constantes como Catalogos

```php
public const MEXICO_STATES = [
    'AG' => 'Aguascalientes',
    'BC' => 'Baja California',
    // ...
    'ZA' => 'Zacatecas',
];
```

La constante se reutiliza en `BranchResource` (Select de estados) y en el service para validar payloads.

### Leccion Aprendida: API URLs

El token de Envia.com es especifico por ambiente:
- Token de sandbox solo funciona en `api-test.envia.com`
- Token de operations solo funciona en `api.envia.com`

Si recibes 401 Authentication error, verifica que el token corresponda al URL configurado.

---

## Siguiente Step

Continue con [07-testing.md](./07-testing.md) para aprender sobre tests automatizadas.
