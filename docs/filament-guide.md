# FilamentPHP Guide - From Zero to Expert

> Complete guide to implement and customize FilamentPHP in Laravel.
> Based on the SaaS Template implementation.

**Version**: FilamentPHP 3.3.47
**Livewire**: 3.7.8
**Laravel**: 12.x

---

## Table of Contents

1. [Installation](#1-installation)
2. [Project Structure](#2-project-structure)
3. [Creating Resources](#3-creating-resources)
4. [Table Customization](#4-table-customization)
5. [Form Customization](#5-form-customization)
6. [Actions and Workflows](#6-actions-and-workflows)
7. [Navigation and Groups](#7-navigation-and-groups)
8. [Widgets and Dashboard](#8-widgets-and-dashboard)
9. [Best Practices](#9-best-practices)
10. [Command Reference](#10-command-reference)

---

## 1. Installation

### 1.1 Prerequisites

```
PHP 8.1+
Laravel 10+ / 11+ / 12+
Livewire 3.x (Filament 3 does NOT support Livewire 4)
```

### 1.2 Install FilamentPHP

```bash
# Option A: If you already have Livewire 4, downgrade
composer require livewire/livewire:"^3.5" filament/filament:"^3.3" -W

# Option B: Clean installation
composer require filament/filament:"^3.3"
```

### 1.3 Configure Admin Panel

```bash
# Install the panel (creates AdminPanelProvider)
php artisan filament:install --panels

# Publish assets
php artisan filament:assets

# Run migrations
php artisan migrate
```

### 1.4 Create Admin User

```bash
# Interactive
php artisan make:filament-user

# Programmatic (via tinker)
php artisan tinker --execute="
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
]);
"
```

### 1.5 Verify Installation

```bash
# Start server
php artisan serve

# Visit: http://localhost:8000/admin
```

---

## 2. Project Structure

### 2.1 Generated Files

```
app/
├── Filament/
│   ├── Resources/           # CRUD Resources
│   │   ├── OrderResource.php
│   │   └── OrderResource/
│   │       └── Pages/
│   │           ├── ListOrders.php
│   │           ├── CreateOrder.php
│   │           └── EditOrder.php
│   ├── Pages/               # Custom pages
│   └── Widgets/             # Dashboard widgets
├── Providers/
│   └── Filament/
│       └── AdminPanelProvider.php  # Panel configuration
```

### 2.2 Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     AdminPanelProvider                       │
│  (Configures routes, middleware, theme, navigation)         │
└─────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
   ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
   │  Resources  │     │   Pages     │     │  Widgets    │
   │  (CRUD)     │     │  (Custom)   │     │ (Dashboard) │
   └─────────────┘     └─────────────┘     └─────────────┘
          │
          ▼
   ┌─────────────────────────────────────────┐
   │              Resource                    │
   │  ┌─────────┐ ┌─────────┐ ┌─────────┐   │
   │  │  Table  │ │  Form   │ │ Actions │   │
   │  │ (List)  │ │(Create/ │ │(Buttons)│   │
   │  │         │ │  Edit)  │ │         │   │
   │  └─────────┘ └─────────┘ └─────────┘   │
   └─────────────────────────────────────────┘
```

---

## 3. Creating Resources

### 3.1 Basic Command

```bash
# Create resource with auto-generated CRUD
php artisan make:filament-resource Order --generate

# Create empty resource (manual)
php artisan make:filament-resource Order

# Create resource with soft deletes
php artisan make:filament-resource Order --generate --soft-deletes

# Create resource with view page
php artisan make:filament-resource Order --generate --view
```

### 3.2 Create Multiple Resources

```bash
# Catalogs
php artisan make:filament-resource ItemCategory --generate
php artisan make:filament-resource Size --generate
php artisan make:filament-resource Color --generate

# Configuration
php artisan make:filament-resource PricingRule --generate
php artisan make:filament-resource removed --generate
```

### 3.3 Resource Structure

```php
<?php

namespace App\Filament\Resources;

use App\Models\Order;
use Filament\Resources\Resource;

class OrderResource extends Resource
{
    // Associated model
    protected static ?string $model = Order::class;

    // Navigation icon (Heroicons)
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Custom labels
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $modelLabel = 'Order';
    protected static ?string $pluralModelLabel = 'Orders';

    // Navigation order
    protected static ?int $navigationSort = 1;

    // Navigation group
    protected static ?string $navigationGroup = 'Sales';

    // Define table
    public static function table(Table $table): Table { ... }

    // Define form
    public static function form(Form $form): Form { ... }

    // Define pages
    public static function getPages(): array { ... }
}
```

---

## 4. Table Customization

### 4.1 Basic Columns

```php
use Filament\Tables;
use Filament\Tables\Table;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            // Simple text
            Tables\Columns\TextColumn::make('customer_name')
                ->label('Customer')
                ->searchable()
                ->sortable(),

            // Formatted number
            Tables\Columns\TextColumn::make('total')
                ->label('Total')
                ->money('USD')
                ->sortable(),

            // Date
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('m/d/Y H:i')
                ->sortable(),

            // Boolean
            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),

            // Image
            Tables\Columns\ImageColumn::make('photo')
                ->circular(),

            // Color
            Tables\Columns\ColorColumn::make('hex_code')
                ->label('Color'),
        ]);
}
```

### 4.2 Badges with Dynamic Colors

```php
Tables\Columns\TextColumn::make('status')
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
```

### 4.3 Filters

```php
->filters([
    // Select filter
    Tables\Filters\SelectFilter::make('status')
        ->label('Status')
        ->options([
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'in_production' => 'In Production',
        ]),

    // Relationship filter
    Tables\Filters\SelectFilter::make('model_id')
        ->label('Type')
        ->relationship('productModel', 'name'),

    // Ternary filter (Yes/No/All)
    Tables\Filters\TernaryFilter::make('is_active')
        ->label('Active'),
])
```

### 4.4 Sorting and Grouping

```php
return $table
    ->defaultSort('created_at', 'desc')
    ->defaultGroup('productModel.name')
    ->reorderable('display_order')  // Drag & drop
    ->columns([...]);
```

---

## 5. Form Customization

### 5.1 Basic Fields

```php
use Filament\Forms;
use Filament\Forms\Form;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Text
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            // Email
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required(),

            // Phone
            Forms\Components\TextInput::make('phone')
                ->label('Phone')
                ->tel(),

            // Number
            Forms\Components\TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->prefix('$')
                ->suffix('USD'),

            // Textarea
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->columnSpanFull(),

            // Toggle
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            // Color picker
            Forms\Components\ColorPicker::make('hex_code')
                ->label('Color'),

            // Date
            Forms\Components\DateTimePicker::make('delivery_date')
                ->label('Delivery Date'),
        ]);
}
```

### 5.2 Selects and Relationships

```php
// Simple select
Forms\Components\Select::make('status')
    ->label('Status')
    ->options([
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
    ])
    ->required(),

// Select with relationship
Forms\Components\Select::make('model_id')
    ->label('Product Type')
    ->relationship('productModel', 'name')
    ->required()
    ->preload()
    ->searchable(),

// Tags input
Forms\Components\TagsInput::make('tags')
    ->label('Tags')
    ->separator(','),
```

### 5.3 Sections and Layouts

```php
return $form
    ->schema([
        Forms\Components\Section::make('Customer Information')
            ->schema([
                Forms\Components\TextInput::make('customer_name'),
                Forms\Components\TextInput::make('customer_phone'),
                Forms\Components\TextInput::make('customer_email'),
            ])
            ->columns(3),  // 3 columns

        Forms\Components\Section::make('Order Details')
            ->schema([
                Forms\Components\Select::make('status'),
                Forms\Components\TextInput::make('total'),
            ])
            ->columns(2)
            ->collapsible(),  // Can be collapsed

        Forms\Components\Section::make('Notes')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ])
            ->collapsed(),  // Starts collapsed
    ]);
```

---

## 6. Actions and Workflows

### 6.1 Table Actions

```php
use Filament\Tables\Actions\Action;

->actions([
    // Custom action
    Action::make('confirm')
        ->label('Confirm')
        ->icon('heroicon-o-check')
        ->color('info')
        ->visible(fn (Order $record): bool => $record->status === 'pending')
        ->requiresConfirmation()
        ->action(fn (Order $record) => $record->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ])),

    // Action with modal form
    Action::make('add_note')
        ->label('Add Note')
        ->icon('heroicon-o-pencil')
        ->form([
            Forms\Components\Textarea::make('note')
                ->label('Note')
                ->required(),
        ])
        ->action(function (Order $record, array $data): void {
            $record->update(['notes' => $data['note']]);
        }),

    // Standard actions
    Tables\Actions\EditAction::make()->label('Edit'),
    Tables\Actions\DeleteAction::make()->label('Delete'),
])
```

### 6.2 Bulk Actions

```php
->bulkActions([
    Tables\Actions\BulkActionGroup::make([
        Tables\Actions\DeleteBulkAction::make()
            ->label('Delete selected'),

        Tables\Actions\BulkAction::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check')
            ->action(fn ($records) => $records->each->update(['is_active' => true]))
            ->requiresConfirmation(),

        Tables\Actions\BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-mark')
            ->action(fn ($records) => $records->each->update(['is_active' => false]))
            ->color('danger'),
    ]),
])
```

### 6.3 Status Workflow

```php
// Flow: pending -> confirmed -> in_production -> ready -> delivered

Action::make('confirm')
    ->visible(fn ($record) => $record->status === 'pending')
    ->action(fn ($record) => $record->update(['status' => 'confirmed'])),

Action::make('start_production')
    ->visible(fn ($record) => $record->status === 'confirmed')
    ->action(fn ($record) => $record->update(['status' => 'in_production'])),

Action::make('mark_ready')
    ->visible(fn ($record) => $record->status === 'in_production')
    ->action(fn ($record) => $record->update(['status' => 'ready'])),

Action::make('deliver')
    ->visible(fn ($record) => $record->status === 'ready')
    ->action(fn ($record) => $record->update(['status' => 'delivered'])),
```

---

## 7. Navigation and Groups

### 7.1 Configure Navigation in Resource

```php
class OrderResource extends Resource
{
    // Icon (use Heroicons: https://heroicons.com)
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Menu label
    protected static ?string $navigationLabel = 'Orders';

    // Order (lower = higher position)
    protected static ?int $navigationSort = 1;

    // Navigation group
    protected static ?string $navigationGroup = 'Sales';
}
```

### 7.2 Navigation Badge

```php
// Show pending counter
public static function getNavigationBadge(): ?string
{
    return static::getModel()::where('status', 'pending')->count() ?: null;
}

// Badge color
public static function getNavigationBadgeColor(): ?string
{
    return 'warning';
}
```

### 7.3 Recommended Navigation Structure

```
┌─────────────────────────────────────┐
│  Dashboard                          │
├─────────────────────────────────────┤
│  SALES                              │
│  ├── Orders [5]  <- Badge           │
│  └── Quotes                         │
├─────────────────────────────────────┤
│  CATALOG                            │
│  ├── Items                          │
│  ├── Product Types                  │
│  ├── Colors                         │
│  └── Sizes                          │
├─────────────────────────────────────┤
│  CONFIGURATION                      │
│  ├── Pricing                        │
│  └── Dimensions                     │
└─────────────────────────────────────┘
```

---

## 8. Widgets and Dashboard

### 8.1 Create Stats Widget

```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Orders Today', Order::whereDate('created_at', today())->count())
                ->icon('heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make('Pending', Order::where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Revenue Today', '$' . number_format(
                Order::whereDate('created_at', today())->sum('total')
            ))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
```

### 8.2 Create Chart Widget

```bash
php artisan make:filament-widget OrdersChart --chart
```

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Orders (last 7 days)';

    protected function getData(): array
    {
        $data = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($d) =>
                \Carbon\Carbon::parse($d)->format('m/d')
            )->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // bar, line, pie, doughnut
    }
}
```

### 8.3 Table Widget (Latest Orders)

```bash
php artisan make:filament-widget LatestOrders
```

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?string $heading = 'Latest Orders';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->since(),
            ]);
    }
}
```

---

## 9. Best Practices

### 9.1 Code Organization

```php
// Always define custom labels
protected static ?string $navigationLabel = 'Orders';
protected static ?string $modelLabel = 'Order';
```

### 9.2 Use Groups

```php
// Group related resources
protected static ?string $navigationGroup = 'Catalogs';

// Clear structure:
// - Catalogs -> Items, Colors, Sizes
// - Configuration -> Pricing, Dimensions
```

### 9.3 Form Validation

```php
Forms\Components\TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true)  // Unique except when editing
    ->maxLength(255),

Forms\Components\TextInput::make('price')
    ->numeric()
    ->required()
    ->minValue(0)
    ->maxValue(10000),
```

### 9.4 Relationships

```php
// In the form
Forms\Components\Select::make('model_id')
    ->relationship('productModel', 'name')
    ->preload()        // Preload options
    ->searchable()     // Enable search
    ->createOptionForm([  // Create new from select
        Forms\Components\TextInput::make('name')->required(),
    ]),

// In the table
Tables\Columns\TextColumn::make('productModel.name')
    ->label('Type'),
```

### 9.5 Performance

```php
// Eager loading relationships
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['productModel', 'color']);
}

// Limit records in widgets
->query(Order::latest()->limit(10))
```

---

## 10. Command Reference

### 10.1 Installation

```bash
# Install Filament (with Livewire 3)
composer require livewire/livewire:"^3.5" filament/filament:"^3.3" -W

# Configure panel
php artisan filament:install --panels

# Create admin user
php artisan make:filament-user
```

### 10.2 Code Generation

```bash
# Resource with CRUD
php artisan make:filament-resource Order --generate

# Resource with soft deletes
php artisan make:filament-resource Order --generate --soft-deletes

# Resource with view
php artisan make:filament-resource Order --generate --view

# Stats widget
php artisan make:filament-widget StatsOverview --stats-overview

# Chart widget
php artisan make:filament-widget OrdersChart --chart

# Table widget
php artisan make:filament-widget LatestOrders

# Custom page
php artisan make:filament-page Settings

# Relation Manager
php artisan make:filament-relation-manager OrderResource orderItems product_id
```

### 10.3 Maintenance

```bash
# Clear component cache
php artisan filament:clear-cached-components

# Regenerate assets
php artisan filament:assets

# Update Filament
composer update filament/filament

# View admin routes
php artisan route:list --path=admin
```

### 10.4 Development

```bash
# Start server
php artisan serve

# Clear everything
php artisan optimize:clear

# View logs
tail -f storage/logs/laravel.log
```

---

## Additional Resources

- [Official FilamentPHP Documentation](https://filamentphp.com/docs)
- [Heroicons (Icons)](https://heroicons.com)
- [Tailwind CSS (Styling)](https://tailwindcss.com/docs)
- [Livewire (Base)](https://livewire.laravel.com/docs)

---

## Project: SaaS Template

### Admin Credentials

| Field | Value |
|-------|-------|
| URL | http://localhost:8000/admin |
| Email | `admin@example.com` |
| Password | `admin123` |

### Implemented Resources

| Resource | Model | Group |
|----------|--------|-------|
| OrderResource | Order | - |
| ProductResource | Product | - |
| ItemCategoryResource | ItemCategory | Catalogs |
| ColorResource | Color | Catalogs |
| SizeResource | Size | Catalogs |
| PricingRuleResource | PricingRule | Configuration |
| removedResource | removed | Configuration |

---

*Guide created: 2026-02-04*
*Version: 1.0*
*Project: SaaS Template*
