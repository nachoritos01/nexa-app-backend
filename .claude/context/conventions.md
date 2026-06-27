# Coding Conventions

Estándares y convenciones para este proyecto.

## General Principles

1. **Readability**: Código auto-documentado
2. **Consistency**: Seguir patrones establecidos
3. **Simplicity**: Evitar sobre-ingeniería
4. **PSR-12**: Estándar PHP

## Naming Conventions

### Files
| Type | Convention | Example |
|------|------------|---------|
| Controllers | PascalCase | `ItemController.php` |
| Models | PascalCase Singular | `Item.php` |
| Migrations | snake_case | `create_items_table.php` |
| Seeders | PascalCase + Seeder | `ItemSeeder.php` |
| Livewire | PascalCase | `TenantSwitcher.php` |
| Views | kebab-case | `tenant-switcher.blade.php` |

### Code
| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `PricingCalculator` |
| Methods | camelCase | `calculatePrice` |
| Variables | camelCase | `unitPrice` |
| Constants | SCREAMING_SNAKE | `STATUS_PENDING` |
| Properties | camelCase | `$isActive` |
| Database columns | snake_case | `customer_name` |

## Laravel Specific

### Models
```php
class Product extends Model
{
    // 1. Traits
    use HasFactory;

    // 2. Constants
    public const STATUS_ACTIVE = 'active';

    // 3. Properties
    protected $fillable = ['title', 'description'];
    protected $casts = ['sizes' => 'array'];

    // 4. Relationships
    public function orders(): HasMany

    // 5. Scopes
    public function scopeActive(Builder $query): Builder

    // 6. Accessors
    public function getMainPhotoAttribute(): ?string

    // 7. Static methods
    public static function calculatePrice(): int
}
```

### Controllers
```php
class ProductController extends Controller
{
    // Constructor injection
    public function __construct(
        private ProductService $service
    ) {}

    // Resource methods: index, show, store, update, destroy
    public function index(): JsonResponse
    {
        return response()->json(Product::active()->get());
    }
}
```

### Livewire Components
```php
class QuoteCalculator extends Component
{
    // 1. Public properties (reactive)
    public string $model = 'basicas';
    public int $quantity = 1;

    // 2. Computed properties
    #[Computed]
    public function unitPrice(): int

    // 3. Action methods
    public function increment(): void

    // 4. Render
    public function render()
}
```

## Blade Templates

```blade
{{-- 1. Component syntax --}}
<x-layouts.app title="Page">
    <livewire:quote-calculator />
</x-layouts.app>

{{-- 2. Control flow --}}
@if($condition)
@foreach($items as $item)
@endif
@endforeach

{{-- 3. Class binding --}}
@class([
    'base-class',
    'active' => $isActive,
])
```

## Git Conventions

### Commits (Conventional Commits)
```
type(scope): description

feat(pricing): add volume discount calculation
fix(order): resolve status update bug
refactor(models): extract pricing logic to service
docs(readme): update installation steps
test(pricing): add unit tests for calculator
chore(deps): update laravel to 12.x
```

### Branches
```
feature/quote-calculator
bugfix/price-rounding
hotfix/critical-payment-bug
release/v1.0.0
```

## Code Quality

### Before Commit
```bash
composer format      # Fix code style
composer analyse     # Check for errors
composer test        # Run tests
```

### PHPStan Level
- Current: Level 5
- Target: Level 6

## Import Order

```php
<?php

namespace App\Models;

// 1. PHP core
use Exception;

// 2. Laravel framework
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

// 3. Third-party packages
use Barryvdh\DomPDF\Facade\Pdf;

// 4. App classes
use App\Services\PricingCalculator;
```

---
*Enforced by PHP CS Fixer and PHPStan.*
