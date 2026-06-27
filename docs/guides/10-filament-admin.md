# 10 - FilamentPHP: Admin Panel

Complete guide to FilamentPHP for the SaaS Template backoffice. Includes installation, configuration, current implementations and customization.

## Table of Contents

1. [Introduction](#introduction)
2. [Installation and Configuration](#installation-and-configuration)
3. [Project Structure](#project-structure)
4. [Current Implementations](#current-implementations)
5. [Creating Resources (CRUD)](#creating-resources-crud)
6. [Form Components](#form-components)
7. [Tables and Columns](#tables-and-columns)
8. [Filters and Actions](#filters-and-actions)
9. [Navigation and Groups](#navigation-and-groups)
10. [Theme Customization](#theme-customization)
11. [Widgets and Dashboard](#widgets-and-dashboard)
12. [Best Practices](#best-practices)

---

## Introduction

### What is FilamentPHP?

FilamentPHP is an administration framework for Laravel that enables creating elegant and functional admin panels with very little code. It is built on:

- **Livewire 3**: For reactive components without JavaScript
- **Alpine.js**: For lightweight interactivity
- **Tailwind CSS**: For modern styling

### Project Version

```
FilamentPHP: 3.3.x
Livewire: 3.5.x
Laravel: 12.x
```

### Access URL

```
Development: http://localhost:8000/admin
Production: https://your-domain.com/admin
```

---

## Installation and Configuration

### 1. Installation via Composer

```bash
composer require filament/filament:"^3.3"
```

### 2. Install the Admin Panel

```bash
php artisan filament:install --panels
```

This creates:
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/` directory

### 3. Create Admin User

```bash
php artisan make:filament-user
```

Answer the prompts:
```
Name: Admin
Email: admin@example.com
Password: ********
```

### 4. Panel Configuration

The panel is configured in `app/Providers/Filament/AdminPanelProvider.php`:

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')                    // URL: /admin
            ->login()                          // Enable login
            ->colors([
                'primary' => Color::Amber,     // Primary color
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
```

### 5. Publish Assets (Optional)

```bash
# Publish configuration
php artisan vendor:publish --tag=filament-config

# Publish views for customization
php artisan vendor:publish --tag=filament-views

# Publish translations
php artisan vendor:publish --tag=filament-translations
```

---

## Project Structure

```
app/
├── Filament/
│   ├── Resources/                    # CRUD Resources
│   │   ├── OrderResource.php
│   │   ├── OrderResource/
│   │   │   └── Pages/
│   │   │       ├── CreateOrder.php
│   │   │       ├── EditOrder.php
│   │   │       └── ListOrders.php
│   │   ├── ProductResource.php
│   │   ├── ProductResource/
│   │   │   └── Pages/
│   │   │       └── ...
│   │   └── ...
│   ├── Pages/                        # Custom pages
│   │   └── Dashboard.php
│   └── Widgets/                      # Dashboard widgets
│       ├── StatsOverview.php
│       ├── OrdersChart.php
│       ├── RevenueChart.php
│       ├── LatestOrders.php
│       └── PendingActions.php
├── Providers/
│   └── Filament/
│       └── AdminPanelProvider.php    # Panel configuration
```

---

## Current Implementations

### Implemented Resources (7 total)

| Resource | Model | Group | Description |
|----------|--------|-------|-------------|
| `OrderResource` | Order | - | Order management with statuses |
| `ProductResource` | Product | - | Item catalog |
| `ItemCategoryResource` | ItemCategory | Catalogs | Product types |
| `ColorResource` | Color | Catalogs | Available colors |
| `SizeResource` | Size | Catalogs | Available sizes |
| `PricingRuleResource` | PricingRule | Configuration | Pricing rules |
| `removedResource` | removed | Configuration | Service limits |

### Implemented Widgets (5 total)

| Widget | Type | Description |
|--------|------|-------------|
| `StatsOverview` | Stats | 4 key metrics (orders today, pending, production, revenue) |
| `OrdersChart` | Line Chart | Orders chart for last 30 days |
| `RevenueChart` | Bar Chart | Revenue chart for last 30 days |
| `LatestOrders` | Table | Last 5 orders table |
| `PendingActions` | Table + Actions | Quick actions to change statuses |

### Organized Navigation

```
Admin Panel
├── Dashboard
├── Orders (with pending badge)
├── Items
├── Catalogs
│   ├── Product Types
│   ├── Colors
│   └── Sizes
└── Configuration
    ├── Pricing
    └── Dimensions
```

---

## Creating Resources (CRUD)

### Command to Create Resource

```bash
# Basic resource
php artisan make:filament-resource Product

# With auto-generated fields from the database
php artisan make:filament-resource Product --generate

# Simple resource (no separate pages)
php artisan make:filament-resource Product --simple

# Read-only (view)
php artisan make:filament-resource Product --view
```

### Anatomy of a Resource

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    // Associated model
    protected static ?string $model = Product::class;

    // Navigation icon (Heroicons)
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Custom labels
    protected static ?string $navigationLabel = 'Items';
    protected static ?string $modelLabel = 'Item';
    protected static ?string $pluralModelLabel = 'Items';

    // Navigation order
    protected static ?int $navigationSort = 2;

    // Navigation group (optional)
    // protected static ?string $navigationGroup = 'Catalogs';

    // Define form (create/edit)
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Form components here
        ]);
    }

    // Define table (listing)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Columns here
            ])
            ->filters([
                // Filters here
            ])
            ->actions([
                // Row actions
            ])
            ->bulkActions([
                // Bulk actions
            ]);
    }

    // Relationships (Relation Managers)
    public static function getRelations(): array
    {
        return [];
    }

    // Resource pages
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
```

---

## Form Components

### Sections and Layout

```php
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;

public static function form(Form $form): Form
{
    return $form->schema([
        // Section with title
        Section::make('Basic Information')
            ->description('Main item data')
            ->schema([
                // Fields here
            ])
            ->columns(2)        // 2 columns
            ->collapsible(),    // Collapsible

        // Grid for custom layout
        Grid::make(3)->schema([
            // 3 columns
        ]),
    ]);
}
```

### Text Fields

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

// Simple text input
TextInput::make('title')
    ->label('Title')
    ->required()
    ->maxLength(255)
    ->placeholder('Enter the title')
    ->helperText('Maximum 255 characters'),

// Numeric input with format
TextInput::make('price')
    ->label('Price')
    ->numeric()
    ->required()
    ->prefix('$')
    ->suffix('USD')
    ->minValue(0)
    ->step(0.01),

// Phone
TextInput::make('phone')
    ->label('Phone')
    ->tel()
    ->required(),

// Email
TextInput::make('email')
    ->label('Email')
    ->email()
    ->required(),

// URL
TextInput::make('website')
    ->label('Website')
    ->url(),

// Textarea
Textarea::make('description')
    ->label('Description')
    ->rows(3)
    ->columnSpanFull(),
```

### Selectors

```php
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;

// Simple select
Select::make('status')
    ->label('Status')
    ->options([
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'delivered' => 'Delivered',
    ])
    ->required()
    ->default('pending'),

// Select with relationship
Select::make('model_id')
    ->label('Product Type')
    ->relationship('productModel', 'name')
    ->required()
    ->preload()                    // Preload options
    ->searchable()                 // Enable search
    ->createOptionForm([           // Create new from select
        TextInput::make('name')->required(),
    ]),

// Toggle (switch)
Toggle::make('is_active')
    ->label('Active')
    ->default(true)
    ->onColor('success')
    ->offColor('danger'),

// Checkbox
Checkbox::make('accept_terms')
    ->label('I accept terms and conditions')
    ->required(),

// Radio buttons
Radio::make('priority')
    ->label('Priority')
    ->options([
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ])
    ->inline(),
```

### Dates and Colors

```php
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\ColorPicker;

// Date picker
DatePicker::make('birth_date')
    ->label('Birth Date')
    ->format('Y-m-d')
    ->displayFormat('m/d/Y'),

// Date and time picker
DateTimePicker::make('estimated_delivery')
    ->label('Estimated Delivery')
    ->format('Y-m-d H:i')
    ->displayFormat('m/d/Y H:i'),

// Color picker
ColorPicker::make('hex_code')
    ->label('Color')
    ->required(),
```

### Advanced Fields

```php
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

// Tags
TagsInput::make('tags')
    ->label('Tags')
    ->separator(',')
    ->suggestions(['Premium', 'Basic', 'Featured']),

// Repeater (dynamic list)
Repeater::make('photos')
    ->label('Item Photos')
    ->simple(
        TextInput::make('url')
            ->label('Image URL')
            ->url()
            ->required(),
    )
    ->defaultItems(1)
    ->reorderable()
    ->collapsible()
    ->columnSpanFull(),

// File upload
FileUpload::make('image')
    ->label('Image')
    ->image()
    ->directory('products')
    ->maxSize(2048)              // 2MB
    ->acceptedFileTypes(['image/jpeg', 'image/png']),

// Rich editor (WYSIWYG)
RichEditor::make('content')
    ->label('Content')
    ->toolbarButtons([
        'bold', 'italic', 'underline',
        'bulletList', 'orderedList',
        'link', 'h2', 'h3',
    ])
    ->columnSpanFull(),
```

---

## Tables and Columns

### Text Columns

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ColorColumn;

// Basic text
TextColumn::make('title')
    ->label('Title')
    ->searchable()              // Enable search
    ->sortable()                // Enable sorting
    ->copyable()                // Copy on click
    ->limit(50)                 // Limit characters
    ->wrap(),                   // Wrap text

// Money format
TextColumn::make('total')
    ->label('Total')
    ->money('USD')
    ->sortable(),

// Formatted date
TextColumn::make('created_at')
    ->label('Date')
    ->dateTime('m/d/Y H:i')
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true),

// Relationship
TextColumn::make('productModel.name')
    ->label('Type')
    ->sortable(),

// Badge with dynamic colors
TextColumn::make('status')
    ->label('Status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'pending' => 'warning',
        'confirmed' => 'info',
        'in_production' => 'primary',
        'ready' => 'success',
        'delivered' => 'success',
        'cancelled' => 'danger',
        default => 'gray',
    })
    ->formatStateUsing(fn (string $state): string => match ($state) {
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'in_production' => 'In Production',
        'ready' => 'Ready',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        default => $state,
    }),

// Relationship counter
TextColumn::make('products_count')
    ->label('Items')
    ->counts('products')
    ->badge()
    ->color('info'),
```

### Special Columns

```php
// Image
ImageColumn::make('main_photo')
    ->label('Photo')
    ->circular()                           // Circular shape
    ->defaultImageUrl('/placeholder.png'), // Default image

// Boolean icon
IconColumn::make('is_active')
    ->label('Active')
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle')
    ->trueColor('success')
    ->falseColor('danger'),

// Color
ColorColumn::make('hex_code')
    ->label('Color'),
```

### Table Configuration

```php
public static function table(Table $table): Table
{
    return $table
        // Default sort
        ->defaultSort('created_at', 'desc')

        // Group records
        ->defaultGroup('productModel.name')

        // Enable drag & drop reordering
        ->reorderable('display_order')

        // Pagination
        ->paginated([10, 25, 50, 100])

        // Columns
        ->columns([...])

        // Filters
        ->filters([...])

        // Row actions
        ->actions([...])

        // Bulk actions
        ->bulkActions([...]);
}
```

---

## Filters and Actions

### Filters

```php
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;

->filters([
    // Select filter
    SelectFilter::make('status')
        ->label('Status')
        ->options([
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'delivered' => 'Delivered',
        ]),

    // Relationship filter
    SelectFilter::make('model_id')
        ->label('Type')
        ->relationship('productModel', 'name'),

    // Ternary filter (Yes/No/All)
    TernaryFilter::make('is_active')
        ->label('Active'),

    // Custom filter
    Filter::make('created_recently')
        ->label('Recent')
        ->query(fn ($query) => $query->where('created_at', '>=', now()->subDays(7))),
])
```

### Row Actions

```php
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

->actions([
    // Built-in actions
    ViewAction::make()->label('View'),
    EditAction::make()->label('Edit'),
    DeleteAction::make()->label('Delete'),

    // Simple custom action
    Action::make('toggle_active')
        ->label(fn (Product $record): string => $record->is_active ? 'Deactivate' : 'Activate')
        ->icon(fn (Product $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
        ->color(fn (Product $record): string => $record->is_active ? 'warning' : 'success')
        ->action(fn (Product $record) => $record->update(['is_active' => !$record->is_active])),

    // Action with confirmation
    Action::make('confirm')
        ->label('Confirm')
        ->icon('heroicon-o-check')
        ->color('info')
        ->visible(fn (Order $record): bool => $record->status === 'pending')
        ->requiresConfirmation()
        ->modalHeading('Confirm Order')
        ->modalDescription('Are you sure you want to confirm this order?')
        ->modalSubmitActionLabel('Yes, confirm')
        ->action(fn (Order $record) => $record->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ])),

    // Action with modal form
    Action::make('add_note')
        ->label('Add Note')
        ->icon('heroicon-o-pencil')
        ->form([
            Textarea::make('note')
                ->label('Note')
                ->required(),
        ])
        ->action(fn (Order $record, array $data) => $record->update(['notes' => $data['note']])),
])
```

### Bulk Actions

```php
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkAction;

->bulkActions([
    BulkActionGroup::make([
        // Delete selected
        DeleteBulkAction::make()->label('Delete selected'),

        // Custom bulk action
        BulkAction::make('activate')
            ->label('Activate selected')
            ->icon('heroicon-o-check')
            ->color('success')
            ->requiresConfirmation()
            ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),

        // Export
        BulkAction::make('export')
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function (Collection $records) {
                // Export logic
            }),
    ]),
])
```

---

## Navigation and Groups

### Configure Navigation in Resource

```php
class ProductResource extends Resource
{
    // Navigation icon (Heroicons)
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Menu label
    protected static ?string $navigationLabel = 'Items';

    // Navigation group
    protected static ?string $navigationGroup = 'Catalogs';

    // Order within group
    protected static ?int $navigationSort = 2;

    // Notification badge
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    // Badge color
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // Conditionally hide from navigation
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_products');
    }
}
```

### Available Icons (Heroicons)

```php
// Outline - use 'heroicon-o-'
'heroicon-o-shopping-bag'       // Shopping bag
'heroicon-o-shopping-cart'      // Cart
'heroicon-o-currency-dollar'    // Money
'heroicon-o-tag'                // Tag
'heroicon-o-swatch'             // Color palette
'heroicon-o-arrows-pointing-out' // Arrows (sizes)
'heroicon-o-cog-6-tooth'        // Configuration
'heroicon-o-check'              // Check
'heroicon-o-check-circle'       // Check with circle
'heroicon-o-truck'              // Truck
'heroicon-o-eye'                // Eye
'heroicon-o-eye-slash'          // Eye slash
'heroicon-o-pencil'             // Pencil
'heroicon-o-trash'              // Trash
'heroicon-o-plus'               // Plus
'heroicon-o-document'           // Document
'heroicon-o-users'              // Users
'heroicon-o-chart-bar'          // Chart

// Solid - use 'heroicon-s-'
'heroicon-s-star'               // Filled star
```

Full reference: https://heroicons.com/

---

## Theme Customization

### Panel Colors

In `AdminPanelProvider.php`:

```php
use Filament\Support\Colors\Color;

return $panel
    ->colors([
        // Primary color
        'primary' => Color::Amber,

        // Additional colors
        'danger' => Color::Rose,
        'gray' => Color::Gray,
        'info' => Color::Blue,
        'success' => Color::Emerald,
        'warning' => Color::Orange,
    ]);
```

### Available Colors

```php
Color::Slate
Color::Gray
Color::Zinc
Color::Neutral
Color::Stone
Color::Red
Color::Orange
Color::Amber     // Current project color
Color::Yellow
Color::Lime
Color::Green
Color::Emerald
Color::Teal
Color::Cyan
Color::Sky
Color::Blue
Color::Indigo
Color::Violet
Color::Purple
Color::Fuchsia
Color::Pink
Color::Rose
```

### Custom Color (Hex)

```php
use Filament\Support\Colors\Color;

return $panel
    ->colors([
        'primary' => Color::hex('#1e40af'),  // Custom blue
    ]);
```

### Branding and Logo

```php
return $panel
    // Sidebar logo
    ->brandLogo(asset('images/logo.svg'))
    ->darkModeBrandLogo(asset('images/logo-dark.svg'))

    // Logo size
    ->brandLogoHeight('2rem')

    // Brand name
    ->brandName('SaaS Template Admin')

    // Favicon
    ->favicon(asset('favicon.ico'));
```

### Dark Mode

```php
return $panel
    // Enable dark mode
    ->darkMode(true)

    // Force dark mode
    ->darkMode(fn () => true);
```

### Advanced CSS Customization

1. Create file `resources/css/filament/admin/theme.css`:

```css
@import '/vendor/filament/filament/resources/css/theme.css';

:root {
    --primary-50: 255 251 235;
    --primary-100: 254 243 199;
    --primary-200: 253 230 138;
    /* ... more variables */
}

/* Customize sidebar */
.fi-sidebar {
    @apply bg-gray-900;
}

/* Customize header */
.fi-header {
    @apply border-b-2 border-amber-500;
}
```

2. Compile with Vite in `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
        }),
    ],
});
```

3. Register in the panel:

```php
return $panel
    ->viteTheme('resources/css/filament/admin/theme.css');
```

---

## Widgets and Dashboard

### Create Widget

```bash
php artisan make:filament-widget StatsOverview
```

### Stats Widget

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Orders', Order::where('status', 'pending')->count())
                ->description('Require attention')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Active Items', Product::where('is_active', true)->count())
                ->description('In catalog')
                ->color('success'),

            Stat::make('Monthly Sales', '$' . number_format(Order::whereMonth('created_at', now())->sum('total'), 2))
                ->description('Total revenue')
                ->color('primary'),
        ];
    }
}
```

### Chart Widget

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Orders by Month';

    protected function getData(): array
    {
        $data = Order::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => array_values($data),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // line, bar, pie, doughnut, radar, polarArea
    }
}
```

### Table Widget

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Latest Orders';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')->label('Customer'),
                Tables\Columns\TextColumn::make('total')->money('USD'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('m/d/Y'),
            ]);
    }
}
```

### Register Widgets in Dashboard

```php
// In AdminPanelProvider.php
return $panel
    ->widgets([
        Widgets\AccountWidget::class,
        \App\Filament\Widgets\StatsOverview::class,
        \App\Filament\Widgets\OrdersChart::class,
        \App\Filament\Widgets\RevenueChart::class,
        \App\Filament\Widgets\LatestOrders::class,
        \App\Filament\Widgets\PendingActions::class,
    ]);
```

### Widgets Implemented in the Project

The project has 5 widgets on the dashboard:

| Widget | Type | File | Description |
|--------|------|---------|-------------|
| `StatsOverview` | Stats | `app/Filament/Widgets/StatsOverview.php` | 4 key metrics with mini charts |
| `OrdersChart` | Line Chart | `app/Filament/Widgets/OrdersChart.php` | Orders last 30 days |
| `RevenueChart` | Bar Chart | `app/Filament/Widgets/RevenueChart.php` | Revenue last 30 days |
| `LatestOrders` | Table | `app/Filament/Widgets/LatestOrders.php` | Last 5 orders with status |
| `PendingActions` | Table | `app/Filament/Widgets/PendingActions.php` | Quick status actions |

#### StatsOverview - Key Metrics

```php
// Displays 4 statistics:
// - Orders Today (with 7-day mini chart)
// - Pending (require confirmation)
// - In Production (in process)
// - Revenue Today (confirmed sales)
```

#### PendingActions - Quick Actions

Special widget that allows changing order statuses directly from the dashboard:

```php
Action::make('next_step')
    ->label(fn (Order $record): string => match ($record->status) {
        'pending' => 'Confirm',
        'confirmed' => 'Production',
        'in_production' => 'Ready',
        'ready' => 'Deliver',
        default => 'Action',
    })
    ->requiresConfirmation()
    ->action(function (Order $record) {
        $nextStatus = match ($record->status) {
            'pending' => 'confirmed',
            'confirmed' => 'in_production',
            'in_production' => 'ready',
            'ready' => 'delivered',
            default => $record->status,
        };
        $record->update(['status' => $nextStatus, "{$nextStatus}_at" => now()]);
    });
```

---

## Best Practices

### 1. Custom Labels

Always customize labels for the end user:

```php
protected static ?string $navigationLabel = 'Items';
protected static ?string $modelLabel = 'Item';
protected static ?string $pluralModelLabel = 'Items';
```

### 2. Organize by Groups

Group related resources for better navigation:

```php
protected static ?string $navigationGroup = 'Catalogs';
protected static ?int $navigationSort = 20;
```

### 3. Use Sections in Forms

Divide long forms into logical sections:

```php
Section::make('Basic Information')->schema([...]),
Section::make('Details')->schema([...])->collapsible(),
Section::make('Advanced Configuration')->schema([...])->collapsed(),
```

### 4. Clear Validation

Add help messages and validation:

```php
TextInput::make('price')
    ->required()
    ->numeric()
    ->minValue(0)
    ->helperText('Price in USD without tax'),
```

### 5. Actions with Confirmation

Always confirm destructive or important actions:

```php
Action::make('delete')
    ->requiresConfirmation()
    ->modalHeading('Delete Record')
    ->modalDescription('This action cannot be undone.')
    ->modalSubmitActionLabel('Yes, delete'),
```

### 6. Badges for Statuses

Use badges with semantic colors for statuses:

```php
TextColumn::make('status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'pending' => 'warning',
        'active' => 'success',
        'cancelled' => 'danger',
        default => 'gray',
    }),
```

### 7. Preload Relationships

Avoid N+1 queries by preloading relationships:

```php
Select::make('model_id')
    ->relationship('productModel', 'name')
    ->preload(),
```

### 8. Optional vs Required Fields

Be clear about which fields are required:

```php
TextInput::make('email')
    ->email()
    ->required(),

TextInput::make('phone')
    ->tel()
    ->nullable()
    ->helperText('Optional'),
```

---

## Useful Commands

```bash
# Create resource
php artisan make:filament-resource ModelName

# Create resource with auto-generated fields
php artisan make:filament-resource ModelName --generate

# Create widget
php artisan make:filament-widget WidgetName

# Create custom page
php artisan make:filament-page PageName

# Create relation manager
php artisan make:filament-relation-manager ProductResource orders OrderResource

# Create admin user
php artisan make:filament-user

# Clear Filament cache
php artisan filament:cache-components
php artisan filament:clear-cached-components

# Update assets
php artisan filament:assets
```

---

## Wizard (Multi-Step Forms)

Filament supports wizards for complex forms. Used in the Order Wizard (5 steps).

### Basic Structure

```php
use Filament\Forms\Components\Wizard;

public static function form(Form $form): Form
{
    return $form->schema([
        Wizard::make([
            Wizard\Step::make('Customer')
                ->icon('heroicon-o-user')
                ->schema(static::getCustomerSchema()),
            Wizard\Step::make('Items')
                ->icon('heroicon-o-shopping-bag')
                ->schema(static::getItemsSchema()),
            Wizard\Step::make('Delivery')
                ->icon('heroicon-o-truck')
                ->schema(static::getDeliverySchema()),
        ])
        ->columnSpanFull()
        ->skippable(false),
    ]);
}
```

### Separate Schema Methods

Extract each step to a static method to keep code organized:

```php
private static function getCustomerSchema(): array
{
    return [
        Forms\Components\TextInput::make('customer_phone')
            ->label('Phone')
            ->required()
            ->tel(),
        // ...
    ];
}
```

### Access Fields Between Steps

Use `$get()` to read values from other steps in the same wizard:

```php
Forms\Components\Placeholder::make('total_summary')
    ->content(function (Forms\Get $get): string {
        $subtotal = (int) ($get('subtotal') ?? 0);
        $shipping = (int) ($get('shipping_cost') ?? 0);
        return '$' . number_format($subtotal + $shipping, 2);
    }),
```

### Limitation: FileUpload Between Steps

`$get('file_field')` between wizard steps returns `[]` for FileUpload because temporary files are not exposed via `$get()`. Solution:

```php
// In the FileUpload step, use afterStateUpdated to save the temporary URL
Forms\Components\FileUpload::make('design_image')
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $set('design_image_preview', $state->temporaryUrl());
        }
    }),
Forms\Components\Hidden::make('design_image_preview'),

// In the summary step, read the Hidden field
Forms\Components\Placeholder::make('preview')
    ->content(fn (Forms\Get $get) => $get('design_image_preview')),
```

---

## Actions with Modal and Form

For actions that require user input (like "Quote Shipping"), use `Forms\Components\Actions\Action` with `form()`:

### Structure

```php
Forms\Components\Actions::make([
    Forms\Components\Actions\Action::make('quote_shipping')
        ->label('Quote Shipping')
        ->icon('heroicon-o-truck')
        ->color('info')
        ->form(function () use ($quotes): array {
            // This form is INDEPENDENT from the parent form
            return [
                Forms\Components\Radio::make('selected_quote')
                    ->options($options)
                    ->required(),
            ];
        })
        ->action(function (array $data, \Livewire\Component $livewire): void {
            // $data contains ONLY the modal form fields
            $selected = $data['selected_quote'];
            // ...
        }),
]),
```

### CRITICAL: $set() from a Modal DOES NOT Reach the Parent Form

This is the most common mistake. When an Action has its own `form()`, the `$set` in `action()` is **scoped to the modal**, not the parent form:

```php
// THIS DOES NOT WORK - $set is scoped to the modal
->action(function (array $data, Forms\Set $set): void {
    $set('shipping_cost', 185); // Does not reach parent form
})

// THIS ALSO DOES NOT WORK - path navigation doesn't cross boundaries
->action(function (array $data, Forms\Set $set): void {
    $set('../../shipping_cost', 185); // Does not work
})

// SOLUTION: use $livewire->js() with $wire.$set()
->action(function (array $data, \Livewire\Component $livewire): void {
    $livewire->js("\$wire.\$set('data.shipping_cost', 185)");
})
```

### Avoid JSON as Radio Keys

Livewire corrupts JSON strings used as Radio keys. Use numeric indexes + Hidden field:

```php
// BAD: JSON as key gets corrupted
$options = [];
foreach ($quotes as $quote) {
    $options[json_encode($quote)] = $quote['description']; // Gets corrupted
}

// GOOD: numeric indexes + Hidden with the data
$options = [];
foreach ($quotes as $index => $quote) {
    $options[$index] = "{$quote['carrier']} — \${$quote['price']}";
}
return [
    Forms\Components\Radio::make('selected_quote')
        ->options($options)
        ->required(),
    Forms\Components\Hidden::make('quotes_data')
        ->default(json_encode($quotes)),
];

// In the action, retrieve by index
->action(function (array $data, \Livewire\Component $livewire): void {
    $quotes = json_decode($data['quotes_data'], true);
    $selected = $quotes[(int) $data['selected_quote']];
    // Use $livewire->js() to set parent form fields
})
```

---

## Current Project Resources

| Resource | Model | Key Features |
|----------|--------|-------------|
| OrderResource | Order | 5-step wizard, shipping quote, dynamic pricing |
| ProductResource | Product | ImageColumn, activation toggle |
| CustomerResource | Customer | Phone autocomplete |
| LocationResource | Location | GPS location |
| ItemCategoryResource | ItemCategory | Basic CRUD |
| SizeResource | Size | Badges by size_type |
| ColorResource | Color | ColorPicker and preview |
| PricingRuleResource | PricingRule | Grouped by model |
| removedResource | removed | By size and side |

---

## Additional Resources

- **Official Documentation**: https://filamentphp.com/docs
- **Official Plugins**: https://filamentphp.com/plugins
- **Heroicons**: https://heroicons.com/
- **Tailwind CSS**: https://tailwindcss.com/docs

---

## Next Step

With this guide you have everything you need to:
1. Create new CRUD Resources
2. Customize forms and tables
3. Add filters and actions
4. Customize the panel theme
5. Create widgets for the dashboard

For advanced integrations (payments, AI, social media), see the report in `docs/reports/filament-capabilities-report.md`.
