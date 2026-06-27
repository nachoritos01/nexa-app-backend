# Laravel Rules

Reglas específicas para desarrollo Laravel.

## Models

### Do
```php
// Usar fillable explícito
protected $fillable = ['name', 'email'];

// Definir casts
protected $casts = [
    'is_active' => 'boolean',
    'metadata' => 'array',
];

// Usar scopes para queries comunes
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}

// Métodos estáticos para lógica de negocio
public static function calculatePrice(string $model, int $qty): int
```

### Don't
```php
// NO usar guarded vacío
protected $guarded = []; // NUNCA

// NO queries raw sin parametrizar
DB::select("SELECT * FROM users WHERE id = $id"); // SQL Injection

// NO lógica de negocio en controladores
public function store(Request $request) {
    // NO: Cálculos complejos aquí
}
```

## Controllers

### Do
```php
// Inyección de dependencias
public function __construct(
    private PricingCalculator $calculator
) {}

// Validación con Form Request
public function store(CreateOrderRequest $request): JsonResponse

// Retornar respuestas JSON consistentes
return response()->json([
    'data' => $result,
    'message' => 'Success'
]);
```

### Don't
```php
// NO validación inline compleja
$request->validate([/* muchas reglas */]); // Usar Form Request

// NO retornar arrays directamente
return $product; // Usar response()->json()
```

## Livewire

### Do
```php
// Propiedades tipadas
public string $name = '';
public int $quantity = 1;

// Computed properties para cálculos
#[Computed]
public function total(): int
{
    return $this->price * $this->quantity;
}

// wire:model.live para reactivity inmediata
// wire:model.blur para validación al salir
```

### Don't
```php
// NO lógica pesada en render()
public function render() {
    // NO: Queries complejas aquí
}

// NO propiedades públicas para datos sensibles
public string $password; // NUNCA
```

## Database

### Do
```php
// Usar migraciones para todos los cambios
php artisan make:migration add_status_to_orders

// Índices en columnas de búsqueda frecuente
$table->index('status');

// Foreign keys con constraints
$table->foreignId('user_id')->constrained()->onDelete('cascade');
```

### Don't
```php
// NO modificar BD manualmente
// NO migraciones destructivas sin backup
Schema::dropIfExists('orders'); // Cuidado en producción
```

## Security

### Do
```php
// Escapar output en Blade
{{ $userInput }}

// Validar toda entrada
$validated = $request->validated();

// Usar policies para autorización
$this->authorize('update', $order);
```

### Don't
```php
// NO output sin escapar
{!! $userInput !!} // Solo para HTML confiable

// NO confiar en input del cliente
$order->update($request->all()); // Mass assignment vulnerable
```

---
*Verificado por PHPStan Level 5*
