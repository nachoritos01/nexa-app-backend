# 07 - Testing en Laravel

Esta guide cubre las tests automatizadas: unitarias, de integration y de feature.

> **Reporte de implementation:** Ver [FASE Testing Report](../reports/fase-testing-report.md) para el detalle completo de los 67 tests implementados.

## Fundamentos de Testing

### ¿Por what hacer testing?

```
┌─────────────────────────────────────────────────────────────┐
│                    BENEFICIOS DEL TESTING                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ Detectar bugs temprano (antes de operations)            │
│  ✅ Documentar comportamiento esperado                      │
│  ✅ Refactorizar con confianza                              │
│  ✅ Prevenir regresiones                                    │
│  ✅ Design mejor code (TDD)                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Tipos de Tests

| Tipo | Description | Velocidad | Cobertura |
|------|-------------|-----------|-----------|
| **Unit** | Test una clase/method aislado | Muy fast | Specific |
| **Feature** | Test funcionalidad completa (HTTP) | Medio | Amplia |
| **Integration** | Test interaction entre componentes | Medio | Media |
| **Browser** | Test UI con navegador real | Lento | Completa |

### Estructura de Directorios

```
tests/
├── TestCase.php                         # Clase base para todos los tests
├── Unit/                                # Tests unitarias (34 tests)
│   ├── ExampleTest.php
│   ├── Services/
│   │   └── PricingCalculatorTest.php    # 8 tests - calculations de pricing
│   └── Models/
│       ├── PricingRuleTest.php          # 6 tests - reglas y scopes
│       ├── OrderTest.php                # 10 tests - transiciones de estado
│       ├── removedTest.php       # 5 tests - validation de dimensiones
│       └── QuoteTest.php               # 5 tests - expiration y accessors
└── Feature/                             # Tests de feature (33 tests)
    ├── ExampleTest.php
    ├── Api/
    │   ├── HealthCheckTest.php          # 1 test - GET /api
    │   ├── PricingApiTest.php           # 9 tests - pricing endpoints
    │   ├── OrderApiTest.php             # 9 tests - CRUD + status
    │   └── QuoteApiTest.php             # 5 tests - CRUD quotes
    └── Livewire/
        └── QuoteCalculatorTest.php      # 8 tests - componente calculadora
```

## Configuration

### phpunit.xml

El proyecto usa **SQLite en memoria** para testing (velocidad maxima, ~0.87s para 67 tests):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

### Base de Datos para Testing

SQLite en memoria ya esta configurado. No se necesita crear base de datos adicional.

```bash
# Ejecutar todos los tests (usa SQLite :memory: automaticamente)
/opt/homebrew/opt/php/bin/php artisan test
```

## Tests Unitarias

### Estructura Basic

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

### Test de Model

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos necesarios
        ItemCategory::create([
            'code' => 'basicas',
            'name' => 'Basic',
            'is_active' => true,
        ]);

        PricingRule::create([
            'model' => 'basicas',
            'size_type' => 'standard',
            'min_qty' => 1,
            'max_qty' => null,
            'price' => 200,
            'is_active' => true,
        ]);
    }

    public function test_product_has_main_photo_accessor(): void
    {
        $product = Product::create([
            'title' => 'Test Product',
            'photos' => ['photo1.jpg', 'photo2.jpg'],
            'technique' => 'DTF',
            'material' => 'Cotton',
            'model_id' => 1,
            'is_active' => true,
        ]);

        $this->assertEquals('photo1.jpg', $product->main_photo);
    }

    public function test_product_main_photo_is_null_when_no_photos(): void
    {
        $product = Product::create([
            'title' => 'Test Product',
            'photos' => [],
            'technique' => 'DTF',
            'material' => 'Cotton',
            'is_active' => true,
        ]);

        $this->assertNull($product->main_photo);
    }

    public function test_product_belongs_to_product_model(): void
    {
        $product = Product::create([
            'title' => 'Test Product',
            'model_id' => 1,
            'technique' => 'DTF',
            'material' => 'Cotton',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(ItemCategory::class, $product->productModel);
        $this->assertEquals('basicas', $product->productModel->code);
    }

    public function test_active_scope_filters_inactive_products(): void
    {
        Product::create([
            'title' => 'Active Product',
            'technique' => 'DTF',
            'material' => 'Cotton',
            'is_active' => true,
        ]);

        Product::create([
            'title' => 'Inactive Product',
            'technique' => 'DTF',
            'material' => 'Cotton',
            'is_active' => false,
        ]);

        $activeProducts = Product::active()->get();

        $this->assertCount(1, $activeProducts);
        $this->assertEquals('Active Product', $activeProducts->first()->title);
    }

    public function test_calculate_price_returns_correct_amount(): void
    {
        $product = Product::create([
            'title' => 'Test Product',
            'model_id' => 1,
            'technique' => 'DTF',
            'material' => 'Cotton',
            'is_active' => true,
        ]);

        $price = $product->calculatePrice('MD', 5);

        $this->assertEquals(200, $price);
    }
}
```

### Test de Service

```php
<?php

namespace Tests\Unit\Services;

use App\Models\PricingRule;
use App\Models\ItemCategory;
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

        // Crear model
        ItemCategory::create([
            'code' => 'basicas',
            'name' => 'Basic',
            'is_active' => true,
        ]);

        // Crear reglas de precio
        PricingRule::insert([
            [
                'model' => 'basicas',
                'size_type' => 'standard',
                'min_qty' => 1,
                'max_qty' => 5,
                'price' => 200,
                'label' => '1-5 piezas',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'model' => 'basicas',
                'size_type' => 'standard',
                'min_qty' => 6,
                'max_qty' => 12,
                'price' => 185,
                'label' => '6-12 piezas',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'model' => 'basicas',
                'size_type' => 'standard',
                'min_qty' => 13,
                'max_qty' => null,
                'price' => 175,
                'label' => '13+ piezas',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_calculate_returns_correct_price_for_tier_1(): void
    {
        $result = $this->calculator->calculate('basicas', 'MD', 3);

        $this->assertEquals(200, $result['unitPrice']);
        $this->assertEquals(600, $result['subtotal']);
        $this->assertEquals(300, $result['deposit50']);
    }

    public function test_calculate_returns_correct_price_for_tier_2(): void
    {
        $result = $this->calculator->calculate('basicas', 'MD', 8);

        $this->assertEquals(185, $result['unitPrice']);
        $this->assertEquals(1480, $result['subtotal']);
    }

    public function test_calculate_returns_correct_price_for_tier_3(): void
    {
        $result = $this->calculator->calculate('basicas', 'MD', 20);

        $this->assertEquals(175, $result['unitPrice']);
        $this->assertEquals(3500, $result['subtotal']);
    }

    public function test_calculate_throws_exception_for_invalid_model(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se found regla de precio');

        $this->calculator->calculate('inexistente', 'MD', 5);
    }

    public function test_get_suggestion_returns_next_tier(): void
    {
        $suggestion = $this->calculator->getSuggestion('basicas', 'MD', 4);

        $this->assertNotNull($suggestion);
        $this->assertEquals(6, $suggestion['suggestedQuantity']);
        $this->assertEquals(15, $suggestion['savingsPerPiece']); // 200 - 185
        $this->assertEquals(2, $suggestion['additionalPieces']); // 6 - 4
    }

    public function test_get_suggestion_returns_null_at_best_tier(): void
    {
        $suggestion = $this->calculator->getSuggestion('basicas', 'MD', 15);

        $this->assertNull($suggestion);
    }

    public function test_get_tiers_returns_all_tiers_ordered(): void
    {
        $result = $this->calculator->getTiers('basicas', 'MD');

        $this->assertCount(3, $result['tiers']);
        $this->assertEquals(1, $result['tiers'][0]['min_qty']);
        $this->assertEquals(6, $result['tiers'][1]['min_qty']);
        $this->assertEquals(13, $result['tiers'][2]['min_qty']);
    }

    public function test_size_type_is_correctly_determined(): void
    {
        $result1 = $this->calculator->calculate('basicas', 'MD', 1);
        $result2 = $this->calculator->calculate('basicas', 'GD', 1);

        $this->assertEquals('standard', $result1['sizeType']);
        $this->assertEquals('standard', $result2['sizeType']);
    }
}
```

## Tests de Feature (HTTP)

### Test de API

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ItemCategory::create([
            'code' => 'basicas',
            'name' => 'Basic',
            'is_active' => true,
        ]);

        Color::create([
            'name' => 'Blanco',
            'hex_code' => '#FFFFFF',
            'is_active' => true,
        ]);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'is_active'],
                     ],
                 ])
                 ->assertJsonCount(5, 'data');
    }

    public function test_list_only_returns_active_products(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_can_show_single_product(): void
    {
        $product = Product::factory()->create([
            'title' => 'Test Product',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id' => $product->id,
                         'title' => 'Test Product',
                     ],
                 ]);
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function test_can_create_product(): void
    {
        $data = [
            'title' => 'Nueva Product',
            'description' => 'Description de test',
            'technique' => 'DTF',
            'material' => 'Cotton',
            'model_id' => 1,
            'color_id' => 1,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Item creado exitosamente',
                     'data' => [
                         'title' => 'Nueva Product',
                     ],
                 ]);

        $this->assertDatabaseHas('products', [
            'title' => 'Nueva Product',
            'technique' => 'DTF',
        ]);
    }

    public function test_create_validates_required_fields(): void
    {
        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title', 'technique', 'material']);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['title' => 'Original']);

        $response = $this->putJson("/api/products/{$product->id}", [
            'title' => 'Actualizado',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'title' => 'Actualizado',
                     ],
                 ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'title' => 'Actualizado',
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);

        // Soft delete: el registration existe pero con deleted_at
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_filter_by_model(): void
    {
        $premium = ItemCategory::create([
            'code' => 'premium',
            'name' => 'Premium',
            'is_active' => true,
        ]);

        Product::factory()->create(['model_id' => 1, 'is_active' => true]);
        Product::factory()->create(['model_id' => $premium->id, 'is_active' => true]);

        $response = $this->getJson('/api/products?model=basicas');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }
}
```

### Test de Pricing API

```php
<?php

namespace Tests\Feature\Api;

use App\Models\PricingRule;
use App\Models\ItemCategory;
use App\Models\removed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ItemCategory::create([
            'code' => 'basicas',
            'name' => 'Basic',
            'is_active' => true,
        ]);

        PricingRule::create([
            'model' => 'basicas',
            'size_type' => 'standard',
            'min_qty' => 1,
            'max_qty' => null,
            'price' => 200,
            'label' => '1+ piezas',
            'is_active' => true,
        ]);

        removed::create([
            'size' => 'MD',
            'side' => 'front',
            'max_width' => 30,
            'max_height' => 35,
            'is_active' => true,
        ]);
    }

    public function test_calculate_endpoint_returns_pricing(): void
    {
        $response = $this->getJson('/api/pricing/calculate?model=basicas&size=MD&quantity=5');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'model',
                         'size',
                         'sizeType',
                         'quantity',
                         'unitPrice',
                         'subtotal',
                         'deposit50',
                     ],
                 ])
                 ->assertJson([
                     'data' => [
                         'unitPrice' => 200,
                         'subtotal' => 1000,
                         'deposit50' => 500,
                     ],
                 ]);
    }

    public function test_calculate_validates_required_params(): void
    {
        $response = $this->getJson('/api/pricing/calculate');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['model', 'size', 'quantity']);
    }

    public function test_calculate_validates_model_value(): void
    {
        $response = $this->getJson('/api/pricing/calculate?model=invalid&size=MD&quantity=5');

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['model']);
    }

    public function test_tiers_endpoint_returns_all_tiers(): void
    {
        $response = $this->getJson('/api/pricing/tiers?model=basicas&size=MD');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'model',
                         'size',
                         'sizeType',
                         'tiers',
                     ],
                 ]);
    }

    public function test_validate_dimensions_with_valid_size(): void
    {
        $response = $this->postJson('/api/pricing/validate-dimensions', [
            'size' => 'MD',
            'side' => 'front',
            'width' => 25,
            'height' => 30,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'valid' => true,
                     ],
                 ]);
    }

    public function test_validate_dimensions_with_oversized_design(): void
    {
        $response = $this->postJson('/api/pricing/validate-dimensions', [
            'size' => 'MD',
            'side' => 'front',
            'width' => 50,  // Excede 30
            'height' => 50, // Excede 35
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'valid' => false,
                     ],
                 ]);
    }
}
```

### Test de Orders API

```php
<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        ItemCategory::create([
            'code' => 'basicas',
            'name' => 'Basic',
            'is_active' => true,
        ]);

        PricingRule::create([
            'model' => 'basicas',
            'size_type' => 'standard',
            'min_qty' => 1,
            'max_qty' => null,
            'price' => 200,
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'model_id' => 1,
            'is_active' => true,
        ]);
    }

    public function test_can_create_order_with_items(): void
    {
        $data = [
            'customer_name' => 'Juan Perez',
            'customer_phone' => '5551234567',
            'customer_email' => 'juan@test.com',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'size' => 'MD',
                    'color' => 'Blanco',
                    'quantity' => 5,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $data);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'customer_name',
                         'status',
                         'subtotal',
                         'deposit',
                         'total',
                     ],
                 ]);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Juan Perez',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_lines', [
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);
    }

    public function test_order_status_can_be_updated(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_cannot_update_to_invalid_status(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/orders/{$order->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    public function test_list_orders_can_filter_by_status(): void
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'confirmed']);

        $response = $this->getJson('/api/orders?status=pending');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }
}
```

## Factories

### Crear Factory

```bash
php artisan make:factory ProductFactory --model=Product
```

### Definir Factory

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ItemCategory;
use App\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'technique' => $this->faker->randomElement(['DTF', 'Screen printing', 'Sublimation']),
            'material' => $this->faker->randomElement(['Cotton', 'Polyester', 'Mixto']),
            'model_id' => ItemCategory::factory(),
            'color_id' => Color::factory(),
            'photos' => [
                $this->faker->imageUrl(400, 400, 'fashion'),
            ],
            'is_active' => true,
        ];
    }

    // Estados personalizados
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_id' => ItemCategory::where('code', 'premium')->first()?->id ?? 2,
        ]);
    }

    public function withoutPhotos(): static
    {
        return $this->state(fn (array $attributes) => [
            'photos' => [],
        ]);
    }
}
```

### Usar Factories

```php
// Crear uno
$product = Product::factory()->create();

// Crear varios
$products = Product::factory()->count(10)->create();

// Con estado
$inactive = Product::factory()->inactive()->create();
$premium = Product::factory()->premium()->count(5)->create();

// Con atributos specifics
$product = Product::factory()->create([
    'title' => 'Item Specific',
    'is_active' => true,
]);

// Sin persistir (solo instancia)
$product = Product::factory()->make();

// Con relationships
$product = Product::factory()
    ->has(Size::factory()->count(3))
    ->create();
```

## Commands de Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests specifics
php artisan test --filter=ProductTest
php artisan test --filter=test_can_create_product

# Por directorio
php artisan test tests/Unit
php artisan test tests/Feature/Api

# Con coverage
php artisan test --coverage

# Coverage minimum requerido
php artisan test --coverage --min=80

# Parallel (more fast)
php artisan test --parallel

# Stop on failure
php artisan test --stop-on-failure

# Verbose
php artisan test -v

# Con PHPUnit directamente
./vendor/bin/phpunit
./vendor/bin/phpunit --filter=ProductTest
```

## Assertions Comunes

### PHPUnit Assertions

```php
// Igualdad
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Tipo estricto
$this->assertNotEquals($expected, $actual);

// Booleanos
$this->assertTrue($value);
$this->assertFalse($value);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// Arrays
$this->assertCount(5, $array);
$this->assertContains($needle, $array);
$this->assertArrayHasKey('key', $array);
$this->assertEmpty($array);
$this->assertNotEmpty($array);

// Strings
$this->assertStringContainsString('needle', $haystack);
$this->assertStringStartsWith('prefix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Tipos
$this->assertInstanceOf(Product::class, $object);
$this->assertIsArray($value);
$this->assertIsInt($value);
$this->assertIsString($value);

// Excepciones
$this->expectException(InvalidArgumentException::class);
$this->expectExceptionMessage('mensaje esperado');
```

### Laravel Assertions

```php
// Base de datos
$this->assertDatabaseHas('products', ['title' => 'Test']);
$this->assertDatabaseMissing('products', ['title' => 'Test']);
$this->assertDatabaseCount('products', 5);
$this->assertSoftDeleted('products', ['id' => 1]);

// HTTP Response
$response->assertStatus(200);
$response->assertOk();           // 200
$response->assertCreated();      // 201
$response->assertNoContent();    // 204
$response->assertNotFound();     // 404
$response->assertUnauthorized(); // 401
$response->assertForbidden();    // 403

// JSON
$response->assertJson(['key' => 'value']);
$response->assertJsonFragment(['name' => 'Test']);
$response->assertJsonStructure(['data' => ['id', 'title']]);
$response->assertJsonCount(5, 'data');
$response->assertJsonPath('data.0.title', 'Expected');

// Validation
$response->assertValid();
$response->assertInvalid(['email']);
$response->assertJsonValidationErrors(['email', 'password']);

// Redirects
$response->assertRedirect('/login');
$response->assertRedirectToRoute('login');

// Session
$response->assertSessionHas('key');
$response->assertSessionHasErrors(['email']);
$response->assertSessionMissing('key');

// Cookies
$response->assertCookie('name');
$response->assertCookieMissing('name');
```

## Siguiente Step

Continue con [08-best-practices.md](./08-best-practices.md) para aprender las mejores practices.
