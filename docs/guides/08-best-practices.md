# 08 - Best Practices

This guide covers conventions, standards and best practices for Laravel development.

## Naming Conventions

### File and Class Names

| Element | Convention | Example |
|----------|------------|---------|
| **Models** | Singular, PascalCase | `Product.php`, `OrderLine.php` |
| **Controllers** | Singular + Controller | `ProductController.php` |
| **Migrations** | snake_case with timestamp | `2024_01_15_create_products_table.php` |
| **Seeders** | Singular + Seeder | `ProductSeeder.php` |
| **Factories** | Singular + Factory | `ProductFactory.php` |
| **Form Requests** | Action + Model + Request | `StoreProductRequest.php` |
| **Events** | Descriptive PascalCase | `OrderCreated.php` |
| **Listeners** | Descriptive action | `SendOrderConfirmation.php` |
| **Jobs** | Descriptive action | `ProcessPayment.php` |
| **Policies** | Model + Policy | `ProductPolicy.php` |

### Method and Variable Names

```php
// Correct
class ProductController
{
    public function index() { }              // List
    public function show(Product $product) { }  // Show one
    public function store(Request $request) { } // Create
    public function update(Request $request, Product $product) { } // Update
    public function destroy(Product $product) { } // Delete
}

// Variables: camelCase
$productCount = Product::count();
$activeProducts = Product::active()->get();
$isAvailable = true;

// Constants: UPPER_SNAKE_CASE
const STATUS_PENDING = 'pending';
const MAX_ITEMS_PER_PAGE = 50;
```

### Table and Column Names

```php
// Tables: plural, snake_case
'products'
'order_lines'
'pricing_rules'

// Columns: snake_case
'created_at'
'customer_name'
'is_active'
'model_id'  // Foreign key

// Pivot tables: singular_singular (alphabetical order)
'product_size'  // Relationship Product <-> Size
'order_product' // Relationship Order <-> Product

// Avoid redundant prefixes
// BAD: 'product_title', 'product_description'
// GOOD: 'title', 'description'
```

## Code Structure

### Clean Controllers

```php
// BAD: Controller with too much logic
class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Extensive validation here...
        // Business logic here...
        // Price calculations here...
        // Notifications here...
        // 200 lines of code...
    }
}

// GOOD: Clean controller using Form Request and Service
class OrderController extends Controller
{
    public function __construct(
        private OrderProcessor $orderProcessor
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderProcessor->createOrder(
            $request->validated()
        );

        return response()->json([
            'message' => 'Order created',
            'data' => $order,
        ], 201);
    }
}
```

### Organized Models

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    // 1. Traits
    use SoftDeletes;

    // 2. Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';

    // 3. Properties
    protected $fillable = [
        'title',
        'description',
        'model_id',
        'is_active',
    ];

    protected $casts = [
        'photos' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    // 4. Boot/events
    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            // ...
        });
    }

    // 5. Relationships
    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'model_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    // 6. Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModel($query, string $modelCode)
    {
        return $query->whereHas('productModel', fn($q) =>
            $q->where('code', $modelCode)
        );
    }

    // 7. Accessors
    public function getMainPhotoAttribute(): ?string
    {
        return $this->photos[0] ?? null;
    }

    // 8. Mutators
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = trim($value);
    }

    // 9. Business methods
    public function calculatePrice(string $size, int $quantity): int
    {
        // ...
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->stock > 0;
    }
}
```

### Services with Single Responsibility

```php
// BAD: Service that does too much
class OrderService
{
    public function createOrder() { }
    public function processPayment() { }
    public function sendNotifications() { }
    public function generatePdf() { }
    public function calculateShipping() { }
}

// GOOD: Separate services by responsibility
class OrderProcessor { }        // Create/update orders
class PaymentProcessor { }      // Process payments
class NotificationService { }   // Send notifications
class PdfGenerator { }          // Generate PDFs
class ShippingCalculator { }    // Calculate shipping
```

## Eloquent Queries

### Avoid N+1

```php
// BAD: N+1 problem: one query per item
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name;     // Additional query
    foreach ($order->items as $item) { // Another query
        echo $item->product->title;   // Another one per item
    }
}

// GOOD: Eager loading: everything in 3 queries
$orders = Order::with(['customer', 'items.product'])->get();
foreach ($orders as $order) {
    echo $order->customer->name;      // Already loaded
    foreach ($order->items as $item) {
        echo $item->product->title;   // Already loaded
    }
}
```

### Use Scopes Instead of Repeated Where Clauses

```php
// BAD: Repeating conditions
$pendingOrders = Order::where('status', 'pending')
    ->where('deleted_at', null)
    ->get();

$activeProducts = Product::where('is_active', true)
    ->where('deleted_at', null)
    ->get();

// GOOD: Use reusable scopes
class Order extends Model
{
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

$pendingOrders = Order::pending()->get();
$activeProducts = Product::active()->get();
```

### Select Only Necessary Columns

```php
// BAD: Fetching everything when you only need a few fields
$products = Product::all();
// Fetches: id, title, description, photos (large JSON), technique, material, etc.

// GOOD: Select only what's needed
$products = Product::select(['id', 'title', 'model_id'])->get();

// For counts, use count() instead of fetching all
// BAD
$count = Product::all()->count();
// GOOD
$count = Product::count();
```

### Chunks for Large Datasets

```php
// BAD: Loading millions of records into memory
$orders = Order::all();
foreach ($orders as $order) {
    // Process...
}

// GOOD: Process in chunks
Order::chunk(1000, function ($orders) {
    foreach ($orders as $order) {
        // Process...
    }
});

// GOOD: Or use lazy() for minimal memory
Order::lazy()->each(function ($order) {
    // Process...
});

// GOOD: Or cursor() for even less memory
foreach (Order::cursor() as $order) {
    // Process one at a time
}
```

## Validation

### Use Form Requests for Complex Validation

```php
// BAD: Validation in the controller
public function store(Request $request)
{
    $validated = $request->validate([
        'customer_name' => 'required|string|max:100',
        'customer_email' => 'required|email|unique:customers',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        // 20 more rules...
    ]);
}

// GOOD: Separate Form Request
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:100',
            // ...
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'The name is required',
        ];
    }
}

public function store(StoreOrderRequest $request)
{
    // Validation already passed
    $order = Order::create($request->validated());
}
```

### Custom Validation Rules

```php
// For complex rules, create a Rule class
php artisan make:rule ValidPhoneNumber

class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            $fail('The :attribute must be a 10-digit number.');
        }
    }
}

// Usage
'phone' => ['required', new ValidPhoneNumber],
```

## Error Handling

### Custom Exceptions

```php
// Create domain-specific exceptions
class InsufficientStockException extends \Exception
{
    public function __construct(
        public Product $product,
        public int $requested,
        public int $available
    ) {
        parent::__construct(
            "Insufficient stock for {$product->title}. " .
            "Requested: {$requested}, Available: {$available}"
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'insufficient_stock',
            'message' => $this->getMessage(),
            'product_id' => $this->product->id,
        ], 422);
    }
}

// Usage
if ($product->stock < $quantity) {
    throw new InsufficientStockException($product, $quantity, $product->stock);
}
```

### Don't Silence Errors

```php
// BAD: Silencing errors (makes debugging hard)
try {
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    // Total silence
}

// BAD: Log but no context
try {
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    \Log::error($e->getMessage());
}

// GOOD: Log with context and re-throw if needed
try {
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    \Log::error('Error in operation', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'context' => ['user_id' => auth()->id()],
    ]);

    throw $e; // Or a more specific exception
}
```

## Security

### Protect Against Mass Assignment

```php
// BAD: Allows assigning any field
$product->update($request->all());

// GOOD: Only validated fields
$product->update($request->validated());

// GOOD: Or specific fields
$product->update($request->only(['title', 'description']));

// GOOD: Configure $fillable in the model
protected $fillable = ['title', 'description', 'price'];

// GOOD: Or $guarded for sensitive fields
protected $guarded = ['id', 'is_admin'];
```

### Validate User IDs

```php
// BAD: Trusting the request ID
public function update(Request $request, Order $order)
{
    $order->update($request->validated());
}

// GOOD: Check ownership
public function update(Request $request, Order $order)
{
    // With Policy
    $this->authorize('update', $order);

    // Or manually
    if ($order->user_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }

    $order->update($request->validated());
}
```

### Escape Output

```php
{{-- BAD: Vulnerable to XSS --}}
{!! $userInput !!}

{{-- GOOD: Automatic escaping --}}
{{ $userInput }}

{{-- If you need HTML, sanitize first --}}
{!! clean($trustedHtml) !!}
```

### Use Prepared Statements

```php
// BAD: Vulnerable to SQL Injection
DB::select("SELECT * FROM users WHERE email = '$email'");

// GOOD: Prepared statement
DB::select("SELECT * FROM users WHERE email = ?", [$email]);

// GOOD: Eloquent (already uses prepared statements)
User::where('email', $email)->first();
```

## Performance

### Cache Expensive Data

```php
// BAD: Heavy query on every request
public function getPricingTiers(string $model): array
{
    return PricingRule::where('model', $model)
        ->orderBy('min_qty')
        ->get()
        ->toArray();
}

// GOOD: Cache the result
public function getPricingTiers(string $model): array
{
    return Cache::remember(
        "pricing_tiers_{$model}",
        now()->addHour(),
        fn() => PricingRule::where('model', $model)
            ->orderBy('min_qty')
            ->get()
            ->toArray()
    );
}

// Invalidate cache when data changes
public function updatePricingRule(PricingRule $rule, array $data): void
{
    $rule->update($data);
    Cache::forget("pricing_tiers_{$rule->model}");
}
```

### Lazy Loading Relationships

```php
// BAD: Loading all relationships always
class Product extends Model
{
    protected $with = ['productModel', 'color', 'sizes', 'reviews', 'variants'];
}

// GOOD: Load on demand
class Product extends Model
{
    // No $with, load when needed
}

// In the controller
Product::with(['productModel', 'color'])->get(); // List
Product::with(['productModel', 'color', 'sizes', 'reviews'])->find($id); // Detail
```

### Database Indexes

```php
// In migrations, add indexes for frequently searched columns
Schema::table('orders', function (Blueprint $table) {
    $table->index('status');                    // Common filters
    $table->index('customer_phone');            // Searches
    $table->index(['status', 'created_at']);    // Combined queries
});

// Unique index to prevent duplicates
$table->unique(['product_id', 'size_id'], 'product_size_unique');
```

## Clean Code

### Descriptive Names

```php
// BAD: Cryptic names
$d = Order::where('s', 'p')->get();
$x = $o->i->sum('q');

// GOOD: Descriptive names
$pendingOrders = Order::where('status', 'pending')->get();
$totalQuantity = $order->items->sum('quantity');
```

### Avoid Magic Numbers

```php
// BAD: Magic numbers
if ($order->total > 5000) {
    $discount = $order->total * 0.1;
}
$order->update(['status' => 3]);

// GOOD: Constants with meaning
const BULK_ORDER_THRESHOLD = 5000;
const BULK_DISCOUNT_RATE = 0.1;
const STATUS_CONFIRMED = 'confirmed';

if ($order->total > self::BULK_ORDER_THRESHOLD) {
    $discount = $order->total * self::BULK_DISCOUNT_RATE;
}
$order->update(['status' => self::STATUS_CONFIRMED]);
```

### Early Returns

```php
// BAD: Deep nesting
public function processOrder(Order $order)
{
    if ($order->status === 'pending') {
        if ($order->items->count() > 0) {
            if ($order->total > 0) {
                // Main logic here
                // 50 lines of code
            }
        }
    }
}

// GOOD: Early returns
public function processOrder(Order $order)
{
    if ($order->status !== 'pending') {
        return;
    }

    if ($order->items->isEmpty()) {
        throw new EmptyOrderException();
    }

    if ($order->total <= 0) {
        throw new InvalidOrderTotalException();
    }

    // Main logic here (no nesting)
}
```

### Small Functions

```php
// BAD: Function that does too much
public function processCheckout(Request $request)
{
    // Validate data (20 lines)
    // Check stock (15 lines)
    // Calculate prices (25 lines)
    // Create order (10 lines)
    // Process payment (30 lines)
    // Send notifications (20 lines)
    // Total: 120+ lines
}

// GOOD: Small, focused functions
public function processCheckout(Request $request)
{
    $data = $this->validateCheckoutData($request);
    $this->verifyStockAvailability($data['items']);
    $pricing = $this->calculatePricing($data['items']);
    $order = $this->createOrder($data, $pricing);
    $this->processPayment($order, $data['payment']);
    $this->sendNotifications($order);

    return $order;
}
```

## Documentation

### PHPDoc for Public Methods

```php
/**
 * Calculate the price for a specific quantity
 *
 * @param string $model Model code (basic, premium)
 * @param string $size Size code (S, M, L, XL, XXL)
 * @param int $quantity Number of pieces
 * @return array{unitPrice: int, subtotal: int, deposit: int}
 * @throws \InvalidArgumentException If no pricing rule exists
 */
public function calculate(string $model, string $size, int $quantity): array
{
    // ...
}
```

### Useful Comments

```php
// BAD: Obvious comments
// Increment counter
$counter++;

// Get user
$user = User::find($id);

// GOOD: Comments that explain the "why"
// We use soft delete to maintain an audit trail
$order->delete();

// Price is rounded up to avoid losses from decimals
$price = (int) ceil($calculatedPrice);

// 30s timeout because the external service can be slow
$response = Http::timeout(30)->get($url);
```

## Lessons Learned from the Project

Real errors found during development and their solutions.

### env() vs config()

```php
// BAD: returns null when config:cache is active
$token = env('ENVIA_API_TOKEN'); // null in production

// GOOD: always use config()
$token = config('services.envia.token');

// Define in config/services.php
'envia' => [
    'token' => env('ENVIA_API_TOKEN'),
    'url' => env('ENVIA_API_URL', 'https://api-test.envia.com'),
],
```

**Rule:** `env()` ONLY inside `config/*.php` files. In the rest of the code, always `config()`.

### Filament: $set() from Modal Does NOT Reach the Parent Form

When an `Action` has its own `form()`, the scope of `$set` is the modal:

```php
// Does NOT work: $set scoped to modal
->action(function (array $data, Forms\Set $set): void {
    $set('shipping_cost', 185); // Does not reach parent form
})

// Does NOT work: path navigation doesn't cross form boundaries
$set('../../shipping_cost', 185);

// Does NOT work: direct mutation without Livewire reactivity
$livewire->data['shipping_cost'] = 185;

// SOLUTION: $wire.$set() via JavaScript
->action(function (array $data, \Livewire\Component $livewire): void {
    $livewire->js("\$wire.\$set('data.shipping_cost', 185)");
})
```

### Livewire: JSON as Radio Keys Gets Corrupted

Livewire serializes/deserializes Radio values. JSON strings as keys get corrupted:

```php
// BAD: JSON key gets truncated/corrupted
$options[json_encode($quote)] = 'Description'; // "{\\"carrier... (corrupt)

// GOOD: numeric indexes + Hidden field with the data
$options[0] = 'FedEx — $185.00';
$options[1] = 'DHL — $245.00';
// Hidden::make('quotes_data')->default(json_encode($quotes))
// In action: $quotes[(int) $data['selected_quote']]
```

### Filament: FileUpload Between Wizard Steps

`$get('file_field')` between steps returns `[]` because temporary files are not exposed:

```php
// Solution: save temporary URL in Hidden field
Forms\Components\FileUpload::make('design_image')
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        if ($state instanceof TemporaryUploadedFile) {
            $set('design_image_preview', $state->temporaryUrl());
        }
    }),
Forms\Components\Hidden::make('design_image_preview'),
```

### Password Hashing: Avoid Double Hash

The `'password' => 'hashed'` cast on User automatically hashes on assignment. Do NOT use `bcrypt()`:

```php
// BAD: double hash, password won't work
User::create(['password' => bcrypt('admin123')]); // Hash(Hash(password))

// GOOD: the 'hashed' cast does the work
User::create(['password' => 'admin123']); // Cast applies automatic hash

// Or bypass with DB::table if you need full control
DB::table('users')->insert([
    'password' => Hash::make('admin123'),
]);
```

### Trusted Proxies on Railway

Without trusted proxies, Vite generates `http://` URLs causing mixed content:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

### Idempotent Seeders

Seeders run on every deploy. Use `firstOrCreate` or `updateOrCreate`:

```php
// BAD: duplicates data on every deploy
Branch::create(['name' => 'Downtown Location']);

// GOOD: idempotent
Branch::updateOrCreate(
    ['name' => 'Downtown Location'],
    ['city' => 'New York', 'state' => 'NY']
);
```

---

## Next Step

Continue with [09-commands-reference.md](./09-commands-reference.md) for the command reference.
