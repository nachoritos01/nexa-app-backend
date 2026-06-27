# 05 - Componentes Livewire

Esta guide cubre el desarrollo de componentes interactivos con Livewire 3.

## What es Livewire?

Livewire es un framework full-stack para Laravel que permite crear interfaces dynamics sin escribir JavaScript. Los componentes Livewire combinan PHP del servidor con reactividad del customer.

```
┌─────────────────────────────────────────────────────────────┐
│                      ARQUITECTURA LIVEWIRE                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   Browser                           Server                  │
│   ┌─────────────┐                  ┌─────────────┐         │
│   │   Alpine.js │ ◀── AJAX ──────▶ │  Component  │         │
│   │     DOM     │    (JSON)        │    (PHP)    │         │
│   └─────────────┘                  └─────────────┘         │
│         │                                │                  │
│         ▼                                ▼                  │
│   ┌─────────────┐                  ┌─────────────┐         │
│   │    HTML     │                  │   Eloquent  │         │
│   │   Output    │                  │   Models    │         │
│   └─────────────┘                  └─────────────┘         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Crear un Componente

### Command de Creation

```bash
# Componente basic
php artisan make:livewire QuoteCalculator

# Con directorio
php artisan make:livewire Layout/Header

# Resultado:
# - app/Livewire/QuoteCalculator.php (clase)
# - resources/views/livewire/quote-calculator.blade.php (vista)
```

### Estructura Basic

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\View;

class Counter extends Component
{
    // Propiedades publics = Estado reactivo
    public int $count = 0;

    // Methods publics = Acciones
    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        $this->count--;
    }

    // Render = Vista
    public function render(): View
    {
        return view('livewire.counter');
    }
}
```

```blade
{{-- resources/views/livewire/counter.blade.php --}}
<div>
    <h1>Contador: {{ $count }}</h1>

    <button wire:click="decrement">-</button>
    <button wire:click="increment">+</button>
</div>
```

### Usar el Componente

```blade
{{-- En cualquier vista Blade --}}
<livewire:counter />

{{-- O con la sintaxis de componente --}}
@livewire('counter')

{{-- Con propiedades iniciales --}}
<livewire:counter :count="10" />
```

## Propiedades Reactivas

### Tipos de Propiedades

```php
class QuoteCalculator extends Component
{
    // === PROPIEDADES PUBLIC (reactivas) ===

    // Strings
    public string $model = 'basicas';
    public string $size = 'MD';

    // Numbers
    public int $quantity = 1;
    public float $price = 0.0;

    // Booleanos
    public bool $isLoading = false;

    // Arrays
    public array $items = [];

    // Nullable
    public ?string $selectedColor = null;

    // === PROPIEDADES STATIC (no reactivas, compartidas) ===
    public static array $models = [
        'basicas' => 'Basic',
        'premium' => 'Premium',
    ];

    public static array $sizes = [
        'CH' => 'Chica',
        'MD' => 'Mediana',
        'GD' => 'Grande',
        'EG' => 'Extra Grande',
        'XX' => 'Doble Extra',
    ];
}
```

### Binding Bidireccional

```blade
{{-- wire:model actualiza la propiedad en tiempo real --}}
<input type="text" wire:model="customerName">

{{-- wire:model.live = actualiza inmediatamente (antes era .defer) --}}
<input type="text" wire:model.live="searchQuery">

{{-- wire:model.blur = actualiza al perder foco --}}
<input type="text" wire:model.blur="email">

{{-- wire:model.change = actualiza al cambiar --}}
<select wire:model.change="selectedOption">

{{-- Selects --}}
<select wire:model="size">
    @foreach(self::$sizes as $code => $name)
        <option value="{{ $code }}">{{ $name }}</option>
    @endforeach
</select>

{{-- Checkboxes --}}
<input type="checkbox" wire:model="isActive">

{{-- Radio buttons --}}
<input type="radio" wire:model="paymentMethod" value="card">
<input type="radio" wire:model="paymentMethod" value="cash">
```

## Propiedades Computadas

Las propiedades computadas se calculan automaticmente cuando cambian sus dependencias:

```php
use Livewire\Attributes\Computed;

class QuoteCalculator extends Component
{
    public string $model = 'basicas';
    public string $size = 'MD';
    public int $quantity = 1;

    #[Computed]
    public function sizeType(): string
    {
        return $this->size === 'XX' ? 'twoXG' : 'standard';
    }

    #[Computed]
    public function unitPrice(): int
    {
        return PricingRule::calculatePrice(
            $this->model,
            $this->sizeType,
            $this->quantity
        ) ?? 0;
    }

    #[Computed]
    public function subtotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    #[Computed]
    public function deposit(): int
    {
        return (int) ($this->subtotal * 0.5);
    }

    #[Computed]
    public function pricingTiers(): array
    {
        return PricingRule::getTiers($this->model, $this->sizeType);
    }

    #[Computed]
    public function messagingUrl(): string
    {
        $phone = config('app.messaging_number', '5212345678901');
        $message = urlencode(
            "Hola, me interesa cotizar:\n" .
            "- Model: {$this->model}\n" .
            "- Talla: {$this->size}\n" .
            "- Cantidad: {$this->quantity}\n" .
            "- Total: \${$this->subtotal}"
        );

        return "https://wa.me/{$phone}?text={$message}";
    }
}
```

```blade
{{-- Acceso en la vista --}}
<div>
    <p>Precio unitario: ${{ $this->unitPrice }}</p>
    <p>Subtotal: ${{ $this->subtotal }}</p>
    <p>Anticipo (50%): ${{ $this->deposit }}</p>

    <a href="{{ $this->messagingUrl }}" target="_blank">
        Cotizar por Messaging
    </a>
</div>
```

## Acciones y Eventos

### Acciones Basic

```php
class QuoteCalculator extends Component
{
    public int $quantity = 1;
    public array $cart = [];

    // Action simple
    public function increment(): void
    {
        $this->quantity++;
    }

    public function decrement(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    // Action con parameters
    public function setQuantity(int $qty): void
    {
        $this->quantity = max(1, min($qty, 100));
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
        $this->quantity = 1; // Reset al cambiar model
    }

    // Action que dispara eventos
    public function addToCart(): void
    {
        $this->cart[] = [
            'model' => $this->model,
            'size' => $this->size,
            'quantity' => $this->quantity,
            'price' => $this->unitPrice,
        ];

        // Disparar evento para otros componentes
        $this->dispatch('cart-updated');

        // Mostrar notification
        $this->dispatch('toast', message: '¡Agregado al carrito!');
    }
}
```

```blade
{{-- Llamar actions --}}
<button wire:click="increment">+</button>
<button wire:click="decrement">-</button>

{{-- Con parameters --}}
<button wire:click="setQuantity(5)">5 piezas</button>
<button wire:click="setQuantity(10)">10 piezas</button>

{{-- Con confirmation --}}
<button wire:click="clearCart"
        wire:confirm="¿You are seguro de vaciar el carrito?">
    Vaciar carrito
</button>

{{-- Con loading state --}}
<button wire:click="addToCart" wire:loading.attr="disabled">
    <span wire:loading.remove>Agregar</span>
    <span wire:loading>Agregando...</span>
</button>
```

### Eventos entre Componentes

```php
// Componente que dispara eventos
class ShoppingCart extends Component
{
    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        // Disparar evento global
        $this->dispatch('cart-updated');
    }
}

// Componente que escucha eventos
class Header extends Component
{
    public int $cartCount = 0;

    // Method escuchador
    #[On('cart-updated')]
    public function updateCartCount(): void
    {
        $this->cartCount = count(session('cart', []));
    }

    public function mount(): void
    {
        $this->updateCartCount();
    }
}
```

```php
use Livewire\Attributes\On;

class Header extends Component
{
    public int $cartCount = 0;
    public bool $mobileMenuOpen = false;

    // Escuchar evento con atributo
    #[On('cart-updated')]
    public function updateCartCount(): void
    {
        $cart = session('cart', []);
        $this->cartCount = count($cart);
    }

    public function toggleMobileMenu(): void
    {
        $this->mobileMenuOpen = !$this->mobileMenuOpen;
    }

    public function mount(): void
    {
        $this->updateCartCount();
    }
}
```

## Ciclo de Vida

### Hooks del Componente

```php
class ProductCatalog extends Component
{
    public string $sortBy = 'newest';
    public ?string $modelFilter = null;

    // Se ejecuta al crear el componente (solo una vez)
    public function mount(?string $model = null): void
    {
        $this->modelFilter = $model;
    }

    // Se ejecuta antes de cada update
    public function updating(string $property): void
    {
        // $property es el name de la propiedad que will change
    }

    // Se ejecuta after de cada update
    public function updated(string $property): void
    {
        if ($property === 'sortBy') {
            // Hacer algo cuando cambia sortBy
        }
    }

    // Hook specific para una propiedad
    public function updatedModelFilter(?string $value): void
    {
        // Se ejecuta cuando cambia modelFilter
    }

    // Se ejecuta antes de renderizar
    public function rendering(): void
    {
        // Preparar datos para la vista
    }

    // Se ejecuta after de renderizar
    public function rendered(): void
    {
        // Logic post-render
    }

    // Se ejecuta al destruir el componente
    public function dehydrate(): void
    {
        // Limpieza
    }
}
```

### Propiedades URL

```php
use Livewire\Attributes\Url;

class ProductCatalog extends Component
{
    // Sincronizar con URL: ?sort=newest
    #[Url]
    public string $sortBy = 'newest';

    // Con name personalizado: ?model=basicas
    #[Url(as: 'model')]
    public ?string $modelFilter = null;

    // Solo mantener en URL si es diferente al default
    #[Url(except: '')]
    public string $search = '';
}
```

URL resultante: `/items?sort=oldest&model=premium&search=product`

## Example Completo: QuoteCalculator

```php
<?php

namespace App\Livewire;

use App\Models\removed;
use App\Models\PricingRule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\View\View;

class QuoteCalculator extends Component
{
    // Estado principal
    public string $model = 'basicas';
    public string $size = 'MD';
    public string $color = 'Blanco';
    public int $quantity = 1;

    // Design
    public float $designWidth = 20;
    public float $designHeight = 25;
    public string $side = 'front';

    // Opciones isticas
    public static array $models = [
        'basicas' => 'Basic',
        'premium' => 'Premium',
    ];

    public static array $sizes = [
        'CH' => 'Chica',
        'MD' => 'Mediana',
        'GD' => 'Grande',
        'EG' => 'Extra Grande',
        'XX' => 'Doble Extra',
    ];

    public static array $colors = [
        'Blanco', 'Negro', 'Gris', 'Azul Marino',
        'Rojo', 'Verde', 'Amarillo', 'Rosa',
    ];

    public static array $quantityPresets = [1, 3, 6, 10, 15, 20, 50];

    // === PROPIEDADES COMPUTADAS ===

    #[Computed]
    public function sizeType(): string
    {
        return $this->size === 'XX' ? 'twoXG' : 'standard';
    }

    #[Computed]
    public function unitPrice(): int
    {
        return PricingRule::calculatePrice(
            $this->model,
            $this->sizeType,
            $this->quantity
        ) ?? 0;
    }

    #[Computed]
    public function subtotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    #[Computed]
    public function deposit(): int
    {
        return (int) ($this->subtotal * 0.5);
    }

    #[Computed]
    public function pricingTiers(): array
    {
        return PricingRule::getTiers($this->model, $this->sizeType);
    }

    #[Computed]
    public function suggestion(): ?array
    {
        $tiers = $this->pricingTiers;
        $currentTier = null;

        foreach ($tiers as $tier) {
            if ($this->quantity >= $tier['min_qty'] &&
                ($tier['max_qty'] === null || $this->quantity <= $tier['max_qty'])) {
                $currentTier = $tier;
            }
        }

        // Buscar siguiente tier
        foreach ($tiers as $tier) {
            if ($tier['min_qty'] > $this->quantity) {
                $savings = $currentTier['price'] - $tier['price'];
                $additional = $tier['min_qty'] - $this->quantity;

                return [
                    'suggestedQty' => $tier['min_qty'],
                    'currentPrice' => $currentTier['price'],
                    'newPrice' => $tier['price'],
                    'savingsPerPiece' => $savings,
                    'additionalPieces' => $additional,
                    'message' => "Agrega {$additional} more y ahorra \${$savings} por pieza",
                ];
            }
        }

        return null;
    }

    #[Computed]
    public function dimensionLimits(): ?removed
    {
        return removed::getLimit($this->size, $this->side);
    }

    #[Computed]
    public function dimensionValidation(): array
    {
        $limit = $this->dimensionLimits;

        if (!$limit) {
            return ['valid' => true, 'message' => ''];
        }

        $isValid = $this->designWidth <= $limit->max_width &&
                   $this->designHeight <= $limit->max_height;

        return [
            'valid' => $isValid,
            'message' => $isValid
                ? ''
                : "El design excede los limits ({$limit->max_width}x{$limit->max_height} cm)",
            'maxWidth' => $limit->max_width,
            'maxHeight' => $limit->max_height,
        ];
    }

    #[Computed]
    public function messagingUrl(): string
    {
        $phone = config('app.messaging_number', '5212345678901');

        $message = "Hola, me interesa cotizar:\n\n" .
            "📦 *Item*\n" .
            "- Model: " . self::$models[$this->model] . "\n" .
            "- Talla: " . self::$sizes[$this->size] . "\n" .
            "- Color: {$this->color}\n" .
            "- Cantidad: {$this->quantity} piezas\n\n" .
            "🎨 *Design*\n" .
            "- Lado: " . ($this->side === 'front' ? 'Frente' : 'Espalda') . "\n" .
            "- Size: {$this->designWidth}x{$this->designHeight} cm\n\n" .
            "💰 *Precio*\n" .
            "- Unitario: \${$this->unitPrice}\n" .
            "- Subtotal: \${$this->subtotal}\n" .
            "- Anticipo (50%): \${$this->deposit}";

        return "https://wa.me/{$phone}?text=" . urlencode($message);
    }

    // === ACCIONES ===

    public function increment(): void
    {
        $this->quantity++;
    }

    public function decrement(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function setQuantity(int $qty): void
    {
        $this->quantity = max(1, $qty);
    }

    public function setModel(string $model): void
    {
        if (array_key_exists($model, self::$models)) {
            $this->model = $model;
        }
    }

    public function setSize(string $size): void
    {
        if (array_key_exists($size, self::$sizes)) {
            $this->size = $size;
        }
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function setSide(string $side): void
    {
        if (in_array($side, ['front', 'back'])) {
            $this->side = $side;
        }
    }

    public function addToCart(): void
    {
        $cart = session('cart', []);

        $cart[] = [
            'model' => $this->model,
            'modelName' => self::$models[$this->model],
            'size' => $this->size,
            'sizeName' => self::$sizes[$this->size],
            'color' => $this->color,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'subtotal' => $this->subtotal,
            'design' => [
                'side' => $this->side,
                'width' => $this->designWidth,
                'height' => $this->designHeight,
            ],
        ];

        session(['cart' => $cart]);

        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: '¡Item agregado al carrito!');

        // Reset
        $this->quantity = 1;
    }

    public function render(): View
    {
        return view('livewire.quote-calculator');
    }
}
```

### Vista del Componente

```blade
{{-- resources/views/livewire/quote-calculator.blade.php --}}
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Cotizador</h2>

    {{-- Selector de Model --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Tipo de Product
        </label>
        <div class="flex gap-2">
            @foreach(self::$models as $code => $name)
                <button
                    wire:click="setModel('{{ $code }}')"
                    class="px-4 py-2 rounded-lg transition-colors
                        {{ $model === $code
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $name }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Selector de Talla --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Talla
        </label>
        <div class="flex flex-wrap gap-2">
            @foreach(self::$sizes as $code => $name)
                <button
                    wire:click="setSize('{{ $code }}')"
                    class="px-3 py-1.5 rounded-lg text-sm transition-colors
                        {{ $size === $code
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ $code }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Selector de Color --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Color
        </label>
        <select wire:model.live="color"
                class="w-full border-gray-300 rounded-lg">
            @foreach(self::$colors as $colorOption)
                <option value="{{ $colorOption }}">{{ $colorOption }}</option>
            @endforeach
        </select>
    </div>

    {{-- Selector de Cantidad --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Cantidad
        </label>
        <div class="flex items-center gap-3">
            <button wire:click="decrement"
                    class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200
                           flex items-center justify-center">
                -
            </button>
            <input type="number"
                   wire:model.live="quantity"
                   min="1"
                   class="w-20 text-center border-gray-300 rounded-lg">
            <button wire:click="increment"
                    class="w-10 h-10 rounded-full bg-blue-600 text-white
                           hover:bg-blue-700 flex items-center justify-center">
                +
            </button>
        </div>

        {{-- Presets de cantidad --}}
        <div class="flex flex-wrap gap-2 mt-2">
            @foreach(self::$quantityPresets as $preset)
                <button wire:click="setQuantity({{ $preset }})"
                        class="px-3 py-1 text-sm rounded-full
                            {{ $quantity === $preset
                                ? 'bg-blue-100 text-blue-700 border border-blue-300'
                                : 'bg-gray-50 text-gray-600 hover:bg-gray-100' }}">
                    {{ $preset }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Sugerencia de ahorro --}}
    @if($this->suggestion)
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800">
                💡 {{ $this->suggestion['message'] }}
            </p>
            <button wire:click="setQuantity({{ $this->suggestion['suggestedQty'] }})"
                    class="mt-2 text-sm text-green-700 underline">
                Cambiar a {{ $this->suggestion['suggestedQty'] }} piezas
            </button>
        </div>
    @endif

    {{-- Resumen de Precio --}}
    <div class="bg-gray-50 rounded-lg p-4 mb-4">
        <div class="flex justify-between mb-2">
            <span class="text-gray-600">Precio unitario:</span>
            <span class="font-semibold">${{ number_format($this->unitPrice) }}</span>
        </div>
        <div class="flex justify-between mb-2">
            <span class="text-gray-600">Subtotal:</span>
            <span class="font-bold text-lg">${{ number_format($this->subtotal) }}</span>
        </div>
        <div class="flex justify-between text-blue-600">
            <span>Anticipo (50%):</span>
            <span class="font-semibold">${{ number_format($this->deposit) }}</span>
        </div>
    </div>

    {{-- Tiers de precio --}}
    <div class="mb-4">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Precios por volumen:</h3>
        <div class="space-y-1">
            @foreach($this->pricingTiers as $tier)
                <div class="flex justify-between text-sm
                    {{ $quantity >= $tier['min_qty'] &&
                       ($tier['max_qty'] === null || $quantity <= $tier['max_qty'])
                        ? 'text-blue-700 font-medium'
                        : 'text-gray-500' }}">
                    <span>{{ $tier['label'] }}</span>
                    <span>${{ $tier['price'] }}/pieza</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Botones de action --}}
    <div class="flex gap-3">
        <button wire:click="addToCart"
                wire:loading.attr="disabled"
                class="flex-1 bg-blue-600 text-white py-3 rounded-lg
                       hover:bg-blue-700 disabled:opacity-50 transition-colors">
            <span wire:loading.remove>Agregar al carrito</span>
            <span wire:loading>Agregando...</span>
        </button>

        <a href="{{ $this->messagingUrl }}"
           target="_blank"
           class="flex-1 bg-green-500 text-white py-3 rounded-lg text-center
                  hover:bg-green-600 transition-colors">
            Messaging
        </a>
    </div>
</div>
```

## Directivas Wire

### Eventos de Click

```blade
{{-- Click simple --}}
<button wire:click="save">Guardar</button>

{{-- Con parameters --}}
<button wire:click="delete({{ $id }})">Eliminar</button>
<button wire:click="setStatus('active')">Activar</button>

{{-- Con confirmation --}}
<button wire:confirm="¿Seguro?" wire:click="delete">Eliminar</button>

{{-- Prevenir default --}}
<a href="#" wire:click.prevent="doSomething">Link</a>

{{-- Modificadores de evento --}}
<button wire:click.stop="action">Stop propagation</button>
<form wire:submit.prevent="save">...</form>
<input wire:keydown.enter="search">
```

### Estados de Carga

```blade
{{-- Mostrar mientras carga --}}
<div wire:loading>Cargando...</div>

{{-- Ocultar mientras carga --}}
<div wire:loading.remove>Contenido normal</div>

{{-- Atributo disabled --}}
<button wire:loading.attr="disabled">Submit</button>

{{-- Clase CSS --}}
<button wire:loading.class="opacity-50">Submit</button>

{{-- Para actions specifics --}}
<span wire:loading wire:target="save">Guardando...</span>
<span wire:loading wire:target="delete">Eliminando...</span>

{{-- Delay para evitar parpadeo --}}
<div wire:loading.delay>Cargando...</div>
<div wire:loading.delay.long>Procesando...</div>
```

### Polling (Update Automatic)

```blade
{{-- Actualizar cada 2 segundos --}}
<div wire:poll.2s>
    Last update: {{ now() }}
</div>

{{-- Solo cuando visible --}}
<div wire:poll.visible>...</div>

{{-- Llamar method specific --}}
<div wire:poll.5s="refreshData">...</div>
```

## Validation en Livewire

```php
use Livewire\Attributes\Validate;

class ContactForm extends Component
{
    #[Validate('required|min:3')]
    public string $name = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:10')]
    public string $message = '';

    public function submit(): void
    {
        $this->validate(); // Valida todas las propiedades

        // O validation manual
        $this->validate([
            'name' => 'required|min:3',
            'email' => 'required|email',
        ]);

        // Procesar...
    }

    // Validation en tiempo real
    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }
}
```

```blade
<form wire:submit="submit">
    <input wire:model="name" type="text">
    @error('name')
        <span class="text-red-500">{{ $message }}</span>
    @enderror

    <input wire:model="email" type="email">
    @error('email')
        <span class="text-red-500">{{ $message }}</span>
    @enderror

    <textarea wire:model="message"></textarea>
    @error('message')
        <span class="text-red-500">{{ $message }}</span>
    @enderror

    <button type="submit">Enviar</button>
</form>
```

## Siguiente Step

Continue con [06-services-logic.md](./06-services-logic.md) para aprender sobre la capa de services.
