# 04 - Desarrollo de API REST

Esta guide cubre el desarrollo de APIs RESTful en Laravel: controladores, rutas, validation y responses.

## Conceptos de API REST

### Principios REST

| Principio | Description |
|-----------|-------------|
| **Stateless** | Cada request es independiente, no guarda estado |
| **Recursos** | URLs representan recursos (sustantivos, no verbos) |
| **Methods HTTP** | GET, POST, PUT/PATCH, DELETE para operaciones CRUD |
| **Responses JSON** | Formato aredar para intercambio de datos |

### Methods HTTP y Operaciones CRUD

| Method | Operation | Ruta | Description |
|--------|-----------|------|-------------|
| GET | Read | `/api/products` | Listar todos |
| GET | Read | `/api/products/{id}` | Obtener uno |
| POST | Create | `/api/products` | Crear nuevo |
| PUT/PATCH | Update | `/api/products/{id}` | Actualizar |
| DELETE | Delete | `/api/products/{id}` | Eliminar |

## Estructura de Rutas API

### File de Rutas

```php
// routes/api.php

<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\QuoteController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/', fn() => response()->json([
    'name' => 'SaaS Template API',
    'version' => '1.0.0',
    'status' => 'ok',
    'timestamp' => now()->toIso8601String(),
]));

// Items (CRUD completo)
Route::apiResource('products', ProductController::class);
Route::get('products/{product}/sizes', [ProductController::class, 'sizes']);

// Orders
Route::apiResource('orders', OrderController::class);
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);

// Precios
Route::prefix('pricing')->group(function () {
    Route::get('rules', [PricingController::class, 'rules']);
    Route::get('limits', [PricingController::class, 'limits']);
    Route::get('tiers', [PricingController::class, 'tiers']);
    Route::get('calculate', [PricingController::class, 'calculate']);
    Route::get('suggestions', [PricingController::class, 'suggestions']);
    Route::post('validate-dimensions', [PricingController::class, 'validateDimensions']);
});

// Quotes
Route::apiResource('quotes', QuoteController::class)->except(['update']);
Route::get('quotes/{quote}/pdf', [QuoteController::class, 'downloadPdf']);
Route::post('quotes/{quote}/generate-pdf', [QuoteController::class, 'generatePdf']);

// Contenido
Route::get('content', [ContentController::class, 'index']);
Route::get('content/{type}', [ContentController::class, 'show']);
```

### Convenciones de Rutas

```php
// apiResource genera estas rutas automaticmente:
Route::apiResource('products', ProductController::class);

// Equivale a:
// GET    /api/products          → index()
// POST   /api/products          → store()
// GET    /api/products/{id}     → show()
// PUT    /api/products/{id}     → update()
// DELETE /api/products/{id}     → destroy()

// Rutas adicionales personalizadas
Route::get('products/{product}/sizes', [ProductController::class, 'sizes']);
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);

// Agrupar con prefijo
Route::prefix('pricing')->group(function () {
    Route::get('calculate', [PricingController::class, 'calculate']);
    // Resulta en: /api/pricing/calculate
});
```

### Ver Rutas Available

```bash
# Todas las rutas
php artisan route:list

# Solo rutas API
php artisan route:list --path=api

# Formato compacto
php artisan route:list --path=api --compact
```

## Controladores API

### Crear Controlador API

```bash
# Controlador API con methods resource
php artisan make:controller Api/ProductController --api

# Solo el controlador empty
php artisan make:controller Api/CustomController
```

### Estructura de un Controlador

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Listar todos los items
     * GET /api/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['productModel', 'color'])->active();

        // Filtros optionales
        if ($request->has('technique')) {
            $query->byTechnique($request->technique);
        }

        if ($request->has('model')) {
            $query->byModel($request->model);
        }

        $products = $query->get();

        return response()->json([
            'data' => $products,
            'meta' => [
                'total' => $products->count(),
            ],
        ]);
    }

    /**
     * Mostrar un item
     * GET /api/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['productModel', 'color', 'sizes']);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Crear item
     * POST /api/products
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'model_id' => 'required|exists:item_categories,id',
            'color_id' => 'nullable|exists:colors,id',
            'technique' => 'required|string|max:50',
            'material' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Item creado exitosamente',
            'data' => $product,
        ], 201); // 201 Created
    }

    /**
     * Actualizar item
     * PUT /api/products/{id}
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'model_id' => 'sometimes|exists:item_categories,id',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Item actualizado',
            'data' => $product->fresh(),
        ]);
    }

    /**
     * Eliminar item
     * DELETE /api/products/{id}
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete(); // Soft delete

        return response()->json([
            'message' => 'Item eliminado',
        ]);
    }

    /**
     * Available sizes para un item
     * GET /api/products/{id}/sizes
     */
    public function sizes(Product $product): JsonResponse
    {
        return response()->json([
            'data' => $product->sizes,
        ]);
    }
}
```

### Controlador de Precios (Example Real)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\removed;
use App\Models\PricingRule;
use App\Services\PricingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function __construct(
        private PricingCalculator $calculator
    ) {}

    /**
     * Obtener todas las reglas de precio
     * GET /api/pricing/rules
     */
    public function rules(): JsonResponse
    {
        $rules = PricingRule::with('productModel')
            ->active()
            ->orderBy('model_id')
            ->orderBy('size_type')
            ->orderBy('min_qty')
            ->get();

        return response()->json(['data' => $rules]);
    }

    /**
     * Obtener limits de dimension
     * GET /api/pricing/limits
     */
    public function limits(): JsonResponse
    {
        $limits = removed::active()->get();

        return response()->json(['data' => $limits]);
    }

    /**
     * Obtener tiers de precio
     * GET /api/pricing/tiers?model=basicas&size=MD
     */
    public function tiers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => 'required|string|in:basicas,premium',
            'size' => 'required|string|in:CH,MD,GD,EG,XX',
        ]);

        $result = $this->calculator->getTiers(
            $validated['model'],
            $validated['size']
        );

        return response()->json(['data' => $result]);
    }

    /**
     * Calcular precio
     * GET /api/pricing/calculate?model=basicas&size=MD&quantity=10
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => 'required|string|in:basicas,premium',
            'size' => 'required|string|in:CH,MD,GD,EG,XX',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->calculator->calculate(
                $validated['model'],
                $validated['size'],
                $validated['quantity']
            );

            return response()->json(['data' => $result]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Sugerencia de cantidad
     * GET /api/pricing/suggestions?model=basicas&size=MD&quantity=4
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => 'required|string',
            'size' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $suggestion = $this->calculator->getSuggestion(
            $validated['model'],
            $validated['size'],
            $validated['quantity']
        );

        return response()->json([
            'hasSuggestion' => $suggestion !== null,
            'data' => $suggestion,
        ]);
    }

    /**
     * Validar dimensiones de design
     * POST /api/pricing/validate-dimensions
     */
    public function validateDimensions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'size' => 'required|string|in:CH,MD,GD,EG,XX',
            'side' => 'required|string|in:front,back',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
        ]);

        $result = removed::validate(
            $validated['size'],
            $validated['side'],
            (float) $validated['width'],
            (float) $validated['height']
        );

        return response()->json(['data' => $result]);
    }
}
```

## Validation de Datos

### Validation en el Controlador

```php
public function store(Request $request): JsonResponse
{
    // Validation basic
    $validated = $request->validate([
        'customer_name' => 'required|string|max:100',
        'customer_phone' => 'required|string|max:20',
        'customer_email' => 'nullable|email',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.size' => 'required|string|in:CH,MD,GD,EG,XX',
        'items.*.quantity' => 'required|integer|min:1',
    ]);

    // Si la validation falla, Laravel retorna automaticmente
    // un JSON con errores y code 422

    // Si pasa, $validated contiene solo los campos valids
    $order = Order::create($validated);

    return response()->json(['data' => $order], 201);
}
```

### Reglas de Validation Comunes

```php
$request->validate([
    // Strings
    'name' => 'required|string|max:100',
    'slug' => 'required|string|alpha_dash',
    'email' => 'required|email',

    // Numbers
    'quantity' => 'required|integer|min:1|max:1000',
    'price' => 'required|numeric|min:0',
    'percentage' => 'required|between:0,100',

    // Booleanos
    'is_active' => 'boolean',
    'accepted' => 'accepted', // true, "yes", "on", "1"

    // Fechas
    'start_date' => 'required|date',
    'end_date' => 'required|date|after:start_date',
    'birthday' => 'date_format:Y-m-d',

    // Files
    'photo' => 'image|mimes:jpg,png|max:2048',
    'document' => 'file|mimetypes:application/pdf',

    // Base de datos
    'user_id' => 'required|exists:users,id',
    'email' => 'required|unique:users,email',
    'code' => 'required|unique:products,code,' . $id, // Ignorar actual

    // Arrays
    'items' => 'required|array|min:1|max:50',
    'items.*' => 'required|string',
    'items.*.id' => 'required|integer',
    'tags' => 'array',
    'tags.*' => 'string|max:50',

    // Condicionales
    'password' => 'sometimes|required|min:8', // Solo si is presente
    'state' => 'required_if:country,US',
    'reason' => 'required_unless:status,approved',

    // Valores specifics
    'status' => 'required|in:pending,confirmed,cancelled',
    'size' => 'required|in:CH,MD,GD,EG,XX',
    'type' => 'required|not_in:invalid,test',

    // Nullable
    'description' => 'nullable|string',
    'phone' => 'nullable|regex:/^[0-9]{10}$/',
]);
```

### Form Request (Validation Separada)

```bash
# Crear Form Request
php artisan make:request StoreOrderRequest
```

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // O logic de authorization
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|string|in:CH,MD,GD,EG,XX',
            'items.*.color' => 'required|string|max:50',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'El name es required',
            'items.required' => 'Debe agregar al menos un item',
            'items.*.quantity.min' => 'La cantidad minimum es 1',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'name del customer',
            'customer_phone' => 'phone',
        ];
    }
}
```

```php
// Uso en el controlador
public function store(StoreOrderRequest $request): JsonResponse
{
    // La validation ya happened, $request->validated() tiene los datos
    $order = Order::create($request->validated());

    return response()->json(['data' => $order], 201);
}
```

## Responses JSON

### Estructura de Responses

```php
// Response exitosa simple
return response()->json([
    'data' => $product,
]);

// Response con metadata
return response()->json([
    'data' => $products,
    'meta' => [
        'total' => $products->count(),
        'page' => 1,
        'per_page' => 15,
    ],
]);

// Response de creation (201)
return response()->json([
    'message' => 'Item creado exitosamente',
    'data' => $product,
], 201);

// Response sin contenido (204)
return response()->json(null, 204);

// Response de error (4xx)
return response()->json([
    'error' => 'Recurso no encontrado',
    'message' => 'El item con ID 999 no existe',
], 404);

// Response de error de validation (422)
return response()->json([
    'message' => 'Los datos proporcionados no son valids',
    'errors' => [
        'email' => ['El email ya is registrado'],
        'password' => ['La password debe tener al menos 8 caracteres'],
    ],
], 422);
```

### Codes de Estado HTTP

| Code | Name | Uso |
|--------|--------|-----|
| 200 | OK | Request exitosa (GET, PUT, PATCH) |
| 201 | Created | Recurso creado (POST) |
| 204 | No Content | Eliminado sin contenido (DELETE) |
| 400 | Bad Request | Error en la request del customer |
| 401 | Unauthorized | No autenticado |
| 403 | Forbidden | No autorizado (sin permisos) |
| 404 | Not Found | Recurso no encontrado |
| 422 | Unprocessable Entity | Error de validation |
| 500 | Server Error | Error interno del servidor |

### Helpers de Response

```php
// Response con code specific
return response()->json($data, 201);

// Response con headers
return response()
    ->json($data)
    ->header('X-Custom-Header', 'value');

// Response de descarga
return response()->download($pathToFile);

// Response de file
return response()->file($pathToFile);
```

## Route Model Binding

Laravel puede inyectar models automaticmente en los controladores:

```php
// routes/api.php
Route::get('products/{product}', [ProductController::class, 'show']);

// ProductController.php
public function show(Product $product): JsonResponse
{
    // $product ya is cargado automaticmente
    // Si no existe, Laravel retorna 404
    return response()->json(['data' => $product]);
}

// Con eager loading
public function show(Product $product): JsonResponse
{
    $product->load(['productModel', 'color', 'sizes']);
    return response()->json(['data' => $product]);
}

// Personalizar la column de search
// En el model:
public function getRouteKeyName(): string
{
    return 'slug'; // Busca por slug en lugar de id
}
// Ruta: /api/products/product-basica-blanca
```

## Injection de Dependencias

```php
class PricingController extends Controller
{
    // Constructor injection
    public function __construct(
        private PricingCalculator $calculator,
        private PdfGenerator $pdfGenerator
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        // Usar el service inyectado
        $result = $this->calculator->calculate(
            $request->model,
            $request->size,
            $request->quantity
        );

        return response()->json(['data' => $result]);
    }

    // Method injection (also funciona)
    public function generatePdf(Quote $quote, PdfGenerator $pdf): JsonResponse
    {
        $filename = $pdf->generateQuote($quote);
        return response()->json(['file' => $filename]);
    }
}
```

## Pagination

```php
public function index(Request $request): JsonResponse
{
    // Pagination simple
    $products = Product::paginate(15);

    // Pagination con parameters
    $perPage = $request->get('per_page', 15);
    $products = Product::paginate($perPage);

    return response()->json($products);
    // Retorna automaticmente:
    // {
    //   "data": [...],
    //   "links": { "first", "last", "prev", "next" },
    //   "meta": { "current_page", "per_page", "total", ... }
    // }
}

// Pagination simple (solo prev/next, more eficiente)
$products = Product::simplePaginate(15);

// Cursor pagination (para grandes datasets)
$products = Product::cursorPaginate(15);
```

## Manejo de Errores

### Try-Catch en Controladores

```php
public function calculate(Request $request): JsonResponse
{
    $validated = $request->validate([
        'model' => 'required|string',
        'size' => 'required|string',
        'quantity' => 'required|integer|min:1',
    ]);

    try {
        $result = $this->calculator->calculate(
            $validated['model'],
            $validated['size'],
            $validated['quantity']
        );

        return response()->json(['data' => $result]);

    } catch (\InvalidArgumentException $e) {
        return response()->json([
            'error' => 'Configuration no valid',
            'message' => $e->getMessage(),
        ], 400);

    } catch (\Exception $e) {
        report($e); // Registrar en logs

        return response()->json([
            'error' => 'Error interno',
            'message' => 'Occurred un error inesperado',
        ], 500);
    }
}
```

### Excepciones Personalizadas

```php
// app/Exceptions/PricingNotFoundException.php
class PricingNotFoundException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'pricing_not_found',
            'message' => $this->getMessage(),
        ], 404);
    }
}

// Uso en service
if (!$rule) {
    throw new PricingNotFoundException(
        "No se found precio para {$model}/{$sizeType}"
    );
}
```

## Testing de APIs

### Test Basic

```php
// tests/Feature/Api/ProductTest.php

namespace Tests\Feature\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'is_active'],
                     ],
                 ]);
    }

    public function test_can_create_product(): void
    {
        $data = [
            'title' => 'Nueva Product',
            'technique' => 'DTF',
            'material' => 'Cotton',
            'model_id' => 1,
        ];

        $response = $this->postJson('/api/products', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'data' => [
                         'title' => 'Nueva Product',
                     ],
                 ]);

        $this->assertDatabaseHas('products', [
            'title' => 'Nueva Product',
        ]);
    }

    public function test_validation_fails_for_invalid_data(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title', 'technique']);
    }
}
```

### Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Tests de feature specifics
php artisan test --filter=ProductTest

# Con coverage
php artisan test --coverage
```

## Examples de Peticiones (curl)

```bash
# Health check
curl http://localhost:8000/api

# Listar items
curl http://localhost:8000/api/products

# Obtener item specific
curl http://localhost:8000/api/products/1

# Crear item
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Product Nueva",
    "technique": "DTF",
    "material": "Cotton",
    "model_id": 1
  }'

# Actualizar item
curl -X PUT http://localhost:8000/api/products/1 \
  -H "Content-Type: application/json" \
  -d '{"title": "Name Actualizado"}'

# Eliminar item
curl -X DELETE http://localhost:8000/api/products/1

# Calcular precio
curl "http://localhost:8000/api/pricing/calculate?model=basicas&size=MD&quantity=10"

# Crear orden con items
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Juan Perez",
    "customer_phone": "5551234567",
    "items": [
      {"product_id": 1, "size": "MD", "color": "Blanco", "quantity": 5}
    ]
  }'

# Actualizar estado de orden
curl -X PATCH http://localhost:8000/api/orders/1/status \
  -H "Content-Type: application/json" \
  -d '{"status": "confirmed"}'
```

## Siguiente Step

Continue con [05-livewire-components.md](./05-livewire-components.md) para aprender sobre componentes interactivos.
