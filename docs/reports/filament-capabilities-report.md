# Report: FilamentPHP Customization Capabilities

**Date:** 2026-02-05
**Version Analyzed:** FilamentPHP 3.x
**Context:** Capability evaluation for Generic Multi-Tenant SaaS Template

---

## Executive Summary

FilamentPHP is **extremely customizable**, allowing everything from simple cosmetic changes to complex enterprise implementations. This report evaluates each requested area.

| Capability | Support Level | Complexity |
|-----------|------------------|-------------|
| Color/UI Customization | ✅ Native | Low |
| Step Flows (Wizards) | ✅ Native | Medium |
| Payment Integrations | ✅ Possible | Medium-High |
| WYSIWYG Editor/Blog | ✅ Native + Plugins | Low-Medium |
| AI Modules | ✅ Possible | High |
| Social Integrations | ✅ Possible | Medium |

---

## 1. Visual Customization (Colors, Buttons, UI)

### Verdict: ✅ VERY POWERFUL

FilamentPHP offers complete visual customization without touching CSS.

### 1.1 Change Panel Colors

```php
// app/Providers/Filament/AdminPanelProvider.php

use Filament\Panel;
use Filament\Support\Colors\Color;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->colors([
            'primary' => Color::Blue,      // Primary color
            'danger' => Color::Rose,       // Delete buttons
            'gray' => Color::Slate,        // Backgrounds and text
            'info' => Color::Sky,          // Information
            'success' => Color::Emerald,   // Success
            'warning' => Color::Amber,     // Warnings
        ]);
}
```

### 1.2 Custom Colors (Hex/RGB)

```php
use Filament\Support\Colors\Color;

->colors([
    'primary' => Color::hex('#1e40af'),  // Brand blue
    'primary' => Color::rgb('rgb(30, 64, 175)'),

    // Or define the full palette
    'primary' => [
        50 => '#eff6ff',
        100 => '#dbeafe',
        200 => '#bfdbfe',
        300 => '#93c5fd',
        400 => '#60a5fa',
        500 => '#3b82f6',
        600 => '#2563eb',
        700 => '#1d4ed8',
        800 => '#1e40af',
        900 => '#1e3a8a',
        950 => '#172554',
    ],
])
```

### 1.3 Customize Specific Buttons

```php
// In any Resource or Action
use Filament\Tables\Actions\Action;

Action::make('confirm')
    ->label('Confirm Order')
    ->icon('heroicon-o-check-circle')
    ->color('success')           // Colors: primary, secondary, success, warning, danger, info, gray
    ->size('lg')                 // Sizes: xs, sm, md, lg, xl
    ->outlined()                 // Outlined variant
    ->button()                   // Force button style
    ->extraAttributes([
        'class' => 'bg-gradient-to-r from-green-500 to-emerald-600',
    ]);
```

### 1.4 Complete Custom Themes

```php
// config/filament.php or in the Panel Provider

->viteTheme('resources/css/filament/admin/theme.css')

// resources/css/filament/admin/theme.css
@import '/vendor/filament/filament/resources/css/theme.css';

:root {
    --primary-50: 239 246 255;
    --primary-100: 219 234 254;
    --primary-500: 59 130 246;
    --primary-600: 37 99 235;
    --primary-700: 29 78 216;
}

.fi-sidebar {
    @apply bg-gradient-to-b from-blue-900 to-indigo-900;
}

.fi-btn-primary {
    @apply shadow-lg hover:shadow-xl transition-all duration-200;
}
```

### 1.5 Dark Mode

```php
->darkMode(true)           // Enable dark mode
->darkModeForced()         // Force dark mode always
```

---

## 2. Step Flows (Wizards/Steps)

### Verdict: ✅ FULL NATIVE SUPPORT

FilamentPHP includes a native Wizard system for multi-step flows.

### 2.1 Basic Form Wizard

```php
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;

public static function form(Form $form): Form
{
    return $form->schema([
        Wizard::make([
            Step::make('Customer Information')
                ->description('Contact details')
                ->icon('heroicon-o-user')
                ->schema([
                    TextInput::make('customer_name')
                        ->label('Name')
                        ->required(),
                    TextInput::make('customer_phone')
                        ->label('Phone')
                        ->tel()
                        ->required(),
                    TextInput::make('customer_email')
                        ->label('Email')
                        ->email(),
                ]),

            Step::make('Items')
                ->description('Select items')
                ->icon('heroicon-o-shopping-cart')
                ->schema([
                    Repeater::make('items')
                        ->label('Line Items')
                        ->schema([
                            Select::make('item_id')
                                ->label('Item')
                                ->options(Item::pluck('name', 'id'))
                                ->required(),
                            Select::make('variant')
                                ->label('Variant')
                                ->options(['Default', 'Small', 'Medium', 'Large']),
                            TextInput::make('quantity')
                                ->label('Quantity')
                                ->numeric()
                                ->minValue(1)
                                ->default(1),
                        ])
                        ->columns(3),
                ]),

            Step::make('Payment')
                ->description('Payment method')
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'card' => 'Credit/Debit Card',
                            'transfer' => 'Bank Transfer',
                            'cash' => 'Cash',
                            'paypal' => 'PayPal',
                        ])
                        ->required(),
                    Placeholder::make('total')
                        ->label('Total to Pay')
                        ->content(fn ($get) => '$' . number_format($this->calculateTotal($get('items')), 2)),
                ]),

            Step::make('Confirmation')
                ->description('Review your order')
                ->icon('heroicon-o-check')
                ->schema([
                    Placeholder::make('summary')
                        ->label('Order Summary')
                        ->content(fn ($get) => view('filament.order-summary', ['data' => $get()])),
                ]),
        ])
        ->startOnStep(1)
        ->skippable()                    // Allow skipping steps
        ->persistStepInQueryString()     // Save step in URL
        ->submitAction(
            Action::make('submit')
                ->label('Create Order')
                ->submit('create')
        ),
    ]);
}
```

### 2.2 Wizard with Per-Step Validation

```php
Step::make('Information')
    ->schema([...])
    ->afterValidation(function () {
        // Logic after validating this step
        Notification::make()
            ->title('Step completed')
            ->success()
            ->send();
    })
    ->beforeValidation(function () {
        // Logic before validation
    }),
```

### 2.3 Conditional Wizard (Dynamic Steps)

```php
Step::make('Custom Details')
    ->schema([
        FileUpload::make('attachment')
            ->label('Attachment file')
            ->acceptedFileTypes(['image/*', 'application/pdf']),
        Textarea::make('special_notes')
            ->label('Special instructions'),
    ])
    ->visible(fn ($get) => $get('has_custom_details') === true),
```

### 2.4 Custom Wizard Page

```php
// app/Filament/Pages/CreateOrder.php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Wizard;

class CreateOrder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static string $view = 'filament.pages.create-order';

    public function getFormSchema(): array
    {
        return [
            Wizard::make([
                // Steps here
            ])
            ->contained(false),
        ];
    }
}
```

---

## 3. Payment Integrations (Stripe, PayPal)

### Verdict: ✅ FULLY POSSIBLE

FilamentPHP does not include native payment integrations, but Laravel has excellent packages.

### 3.1 Recommended Architecture

```
┌───────────────────────────────────────────────────────────────────┐
│                      PAYMENT FLOW                                  │
├───────────────────────────────────────────────────────────────────┤
│                                                                    │
│  Filament Form → PaymentService → Gateway (Stripe/PayPal) → Webhook│
│       │                │                    │               │      │
│       ▼                ▼                    ▼               ▼      │
│  Create Order   Process Payment     Redirect to        Confirm    │
│  (status:       with SDK           gateway             Payment    │
│   pending)                                                         │
│                                                                    │
└───────────────────────────────────────────────────────────────────┘
```

### 3.2 Stripe Integration (via Laravel Cashier)

```bash
composer require laravel/cashier
```

```php
// app/Services/PaymentService.php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;

class StripePaymentService implements PaymentGatewayInterface
{
    public function charge(int $amountCents, array $metadata = []): array
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $intent = $stripe->paymentIntents->create([
            'amount' => $amountCents,
            'currency' => config('cashier.currency', 'usd'),
            'metadata' => $metadata,
        ]);

        return [
            'success' => true,
            'transaction_id' => $intent->id,
            'error' => null,
        ];
    }

    public function refund(string $transactionId, int $amountCents = 0): array
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $params = ['payment_intent' => $transactionId];
        if ($amountCents > 0) {
            $params['amount'] = $amountCents;
        }

        $refund = $stripe->refunds->create($params);

        return [
            'success' => true,
            'refund_id' => $refund->id,
            'error' => null,
        ];
    }
}
```

### 3.3 Payment Action in Filament

```php
// app/Filament/Resources/OrderResource.php

Tables\Actions\Action::make('process_payment')
    ->label('Process Payment')
    ->icon('heroicon-o-credit-card')
    ->color('info')
    ->visible(fn (Order $record) => $record->status === 'pending' && !$record->payment_id)
    ->action(function (Order $record) {
        // Redirect to payment gateway
        return redirect(route('payment.checkout', $record));
    }),
```

### 3.4 PayPal Integration

```bash
composer require srmklive/paypal
```

```php
// app/Services/PayPalService.php

namespace App\Services;

use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalService
{
    protected PayPalClient $provider;

    public function __construct()
    {
        $this->provider = new PayPalClient;
        $this->provider->setApiCredentials(config('paypal'));
        $this->provider->getAccessToken();
    }

    public function createOrder(Order $order): array
    {
        $response = $this->provider->createOrder([
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $order->id,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($order->total, 2, '.', ''),
                    ],
                    'description' => "Order #{$order->id}",
                ],
            ],
            'application_context' => [
                'return_url' => route('paypal.success', $order),
                'cancel_url' => route('paypal.cancel', $order),
            ],
        ]);

        return $response;
    }

    public function capturePayment(string $orderId): array
    {
        return $this->provider->capturePaymentOrder($orderId);
    }
}
```

### 3.5 Payment Dashboard Widget

```php
// app/Filament/Widgets/PaymentStatsWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pending Payments', Order::where('status', 'pending')->count())
                ->description('Awaiting payment')
                ->color('warning')
                ->icon('heroicon-o-clock'),

            Stat::make('Payments Today', Order::whereDate('paid_at', today())->sum('total'))
                ->description("Today's revenue")
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8]),

            Stat::make('This Month', Order::whereMonth('paid_at', now())->sum('total'))
                ->description('Monthly revenue')
                ->color('info'),
        ];
    }
}
```

---

## 4. WYSIWYG Editor and Blog Content

### Verdict: ✅ NATIVE SUPPORT + PLUGINS

FilamentPHP includes a native rich editor and advanced plugins are available.

### 4.1 Native Rich Editor

```php
use Filament\Forms\Components\RichEditor;

RichEditor::make('content')
    ->label('Content')
    ->toolbarButtons([
        'blockquote',
        'bold',
        'bulletList',
        'codeBlock',
        'h2',
        'h3',
        'italic',
        'link',
        'orderedList',
        'redo',
        'strike',
        'underline',
        'undo',
    ])
    ->fileAttachmentsDisk('public')
    ->fileAttachmentsDirectory('blog-attachments')
    ->fileAttachmentsVisibility('public')
    ->columnSpanFull(),
```

### 4.2 Plugin Tiptap Editor (Avanzado)

```bash
composer require awcodes/filament-tiptap-editor
```

```php
use FilamentTiptapEditor\TiptapEditor;

TiptapEditor::make('content')
    ->profile('default')           // Perfiles: default, simple, minimal, custom
    ->tools([
        'heading',
        'bold',
        'italic',
        'strike',
        'link',
        'bullet-list',
        'ordered-list',
        'blockquote',
        'code-block',
        'media',                    // Images and videos
        'table',                    // Tables
        'youtube',                  // Embed de YouTube
        'vimeo',                    // Embed de Vimeo
        'grid',                     // Layouts en grid
        'details',                  // Bloques colapsables
    ])
    ->disk('public')
    ->directory('blog-media')
    ->acceptedFileTypes(['image/*', 'video/*'])
    ->maxSize(10240),              // 10MB
```

### 4.3 Sistema de Blog Completo

```php
// app/Models/Post.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category_id',
        'author_id',
        'status',           // draft, published, scheduled
        'published_at',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
```

```php
// app/Filament/Resources/PostResource.php

namespace App\Filament\Resources;

use App\Models\Post;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Blog';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Content')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Title')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, $set) =>
                                    $set('slug', Str::slug($state))),

                            Forms\Components\TextInput::make('slug')
                                ->label('URL')
                                ->required()
                                ->unique(ignoreRecord: true),

                            Forms\Components\Textarea::make('excerpt')
                                ->label('Extracto')
                                ->rows(3)
                                ->maxLength(300),

                            Forms\Components\RichEditor::make('content')
                                ->label('Content')
                                ->required()
                                ->fileAttachmentsDisk('public')
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('SEO')
                        ->schema([
                            Forms\Components\TextInput::make('meta_title')
                                ->label('Meta Title')
                                ->maxLength(60),
                            Forms\Components\Textarea::make('meta_description')
                                ->label('Meta Description')
                                ->rows(2)
                                ->maxLength(160),
                        ])
                        ->collapsed(),
                ])
                ->columnSpan(['lg' => 2]),

            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Publishing')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Estado')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Publicado',
                                    'scheduled' => 'Programado',
                                ])
                                ->default('draft')
                                ->required(),

                            Forms\Components\DateTimePicker::make('published_at')
                                ->label('Publish date')
                                ->default(now())
                                ->visible(fn ($get) => $get('status') !== 'draft'),

                            Forms\Components\Select::make('author_id')
                                ->label('Autor')
                                ->relationship('author', 'name')
                                ->default(auth()->id())
                                ->required(),
                        ]),

                    Forms\Components\Section::make('Categorization')
                        ->schema([
                            Forms\Components\Select::make('category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                ]),

                            Forms\Components\Select::make('tags')
                                ->label('Tags')
                                ->relationship('tags', 'name')
                                ->multiple()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->required(),
                                ]),
                        ]),

                    Forms\Components\Section::make('Image')
                        ->schema([
                            Forms\Components\FileUpload::make('featured_image')
                                ->label('Featured image')
                                ->image()
                                ->imageEditor()
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('16:9')
                                ->disk('public')
                                ->directory('blog-images'),
                        ]),
                ])
                ->columnSpan(['lg' => 1]),
        ])
        ->columns(3);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge(),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autor'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'published',
                        'info' => 'scheduled',
                    ]),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicado')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Publicado',
                        'scheduled' => 'Programado',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vista previa')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Post $record) => route('blog.show', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ]);
    }
}
```

### 4.4 Full CMS Plugin

```bash
# Z3d0X Filament Fabricator - Page Builder
composer require z3d0x/filament-fabricator

# Spatie Laravel Translatable (multi-idioma)
composer require spatie/laravel-translatable
composer require filament/spatie-laravel-translatable-plugin
```

---

## 5. Artificial Intelligence Modules

### Verdict: ✅ TOTALMENTE POSIBLE

FilamentPHP can integrate with any AI API.

### 5.1 OpenAI Integration

```bash
composer require openai-php/laravel
```

```php
// app/Services/AIContentService.php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class AIContentService
{
    public function generateProductDescription(string $productName, string $material): string
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in product marketing. Write attractive and concise descriptions.',
                ],
                [
                    'role' => 'user',
                    'content' => "Generate a product description for '{$productName}' made of {$material}. Maximum 150 words.",
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    public function generateSocialPost(string $productName, string $platform = 'instagram'): string
    {
        $prompt = match($platform) {
            'instagram' => "Create an Instagram post with emojis to promote '{$productName}'. Include relevant hashtags.",
            'facebook' => "Create a professional Facebook post to promote '{$productName}'.",
            'twitter' => "Crea un tweet corto (max 280 caracteres) promocionando '{$productName}'.",
        };

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    public function suggestTags(string $content): array
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Suggest 5 relevant tags for this content: '{$content}'. Reply only with the tags separated by commas.",
                ],
            ],
        ]);

        return array_map('trim', explode(',', $response->choices[0]->message->content));
    }
}
```

### 5.2 Acciones de IA en Filament

```php
// En ProductResource.php

use App\Services\AIContentService;

Tables\Actions\Action::make('generate_description')
    ->label('Generate description with AI')
    ->icon('heroicon-o-sparkles')
    ->color('warning')
    ->requiresConfirmation()
    ->modalHeading('Generate description with AI')
    ->modalDescription('A new description will be generated for this item.')
    ->action(function (Product $record, AIContentService $ai) {
        $description = $ai->generateProductDescription(
            $record->title,
            $record->material
        );

        $record->update(['description' => $description]);

        Notification::make()
            ->title('Description generated')
            ->success()
            ->send();
    }),

Tables\Actions\Action::make('generate_social')
    ->label('Crear post social')
    ->icon('heroicon-o-share')
    ->form([
        Forms\Components\Select::make('platform')
            ->label('Plataforma')
            ->options([
                'instagram' => 'Instagram',
                'facebook' => 'Facebook',
                'twitter' => 'Twitter/X',
            ])
            ->required(),
    ])
    ->action(function (Product $record, array $data, AIContentService $ai) {
        $post = $ai->generateSocialPost($record->title, $data['platform']);

        // Guardar o mostrar el post generado
        Notification::make()
            ->title('Post generado')
            ->body($post)
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('copy')
                    ->label('Copiar')
                    ->action(fn () => null), // JS handling
            ])
            ->send();
    }),
```

### 5.3 Widget de Sugerencias IA

```php
// app/Filament/Widgets/AISuggestionsWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AISuggestionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.ai-suggestions';

    protected int | string | array $columnSpan = 'full';

    public function getSuggestions(): array
    {
        // Analizar datos y generar sugerencias
        $lowStockProducts = Product::where('stock', '<', 10)->count();
        $pendingOrders = Order::where('status', 'pending')->count();

        $suggestions = [];

        if ($lowStockProducts > 0) {
            $suggestions[] = [
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => 'Stock bajo',
                'message' => "{$lowStockProducts} items need restocking.",
                'action' => 'View items',
                'url' => route('filament.admin.resources.products.index', ['tableFilters[low_stock][value]' => true]),
            ];
        }

        if ($pendingOrders > 5) {
            $suggestions[] = [
                'icon' => 'heroicon-o-clock',
                'title' => 'Pending orders',
                'message' => "You have {$pendingOrders} orders awaiting confirmation.",
                'action' => 'View orders',
                'url' => route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'pending']),
            ];
        }

        return $suggestions;
    }
}
```

### 5.4 Claude API Integration (Anthropic)

```bash
composer require anthropic-ai/anthropic-php
```

```php
// app/Services/ClaudeService.php

namespace App\Services;

use Anthropic\Anthropic;

class ClaudeService
{
    protected Anthropic $client;

    public function __construct()
    {
        $this->client = Anthropic::client(config('services.anthropic.api_key'));
    }

    public function analyzeOrder(Order $order): string
    {
        $response = $this->client->messages()->create([
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 1024,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Analyze this order and suggest improvements or complementary items: " .
                                 json_encode($order->toArray()),
                ],
            ],
        ]);

        return $response->content[0]->text;
    }
}
```

---

## 6. Social Media Integrations (Facebook, Instagram)

### Verdict: ✅ POSSIBLE VIA APIs

### 6.1 Integration Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                  INTEGRACIONES SOCIALES                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   Filament Panel                                                 │
│        │                                                         │
│        ├──▶ Facebook Graph API                                   │
│        │        ├── Publicar posts                               │
│        │        ├── Upload images                                 │
│        │        └── Programar publicaciones                      │
│        │                                                         │
│        ├──▶ Instagram Graph API                                  │
│        │        ├── Publicar al feed                             │
│        │        ├── Stories (Business accounts)                  │
│        │        └── Metrics and insights                          │
│        │                                                         │
│        └──▶ Meta Business Suite API                              │
│                 └── Unified management                           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Facebook Integration

```bash
composer require facebook/graph-sdk
```

```php
// app/Services/FacebookService.php

namespace App\Services;

use Facebook\Facebook;

class FacebookService
{
    protected Facebook $fb;
    protected string $pageId;
    protected string $pageAccessToken;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => config('services.facebook.app_id'),
            'app_secret' => config('services.facebook.app_secret'),
            'default_graph_version' => 'v18.0',
        ]);

        $this->pageId = config('services.facebook.page_id');
        $this->pageAccessToken = config('services.facebook.page_access_token');
    }

    public function publishPost(string $message, ?string $imageUrl = null): array
    {
        $data = ['message' => $message];

        if ($imageUrl) {
            $data['url'] = $imageUrl;
            $endpoint = "/{$this->pageId}/photos";
        } else {
            $endpoint = "/{$this->pageId}/feed";
        }

        $response = $this->fb->post(
            $endpoint,
            $data,
            $this->pageAccessToken
        );

        return $response->getDecodedBody();
    }

    public function schedulePost(string $message, \DateTime $publishTime): array
    {
        $response = $this->fb->post(
            "/{$this->pageId}/feed",
            [
                'message' => $message,
                'published' => false,
                'scheduled_publish_time' => $publishTime->getTimestamp(),
            ],
            $this->pageAccessToken
        );

        return $response->getDecodedBody();
    }

    public function getInsights(): array
    {
        $response = $this->fb->get(
            "/{$this->pageId}/insights?metric=page_impressions,page_engaged_users,page_fans",
            $this->pageAccessToken
        );

        return $response->getDecodedBody();
    }
}
```

### 6.3 Instagram Integration

```php
// app/Services/InstagramService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class InstagramService
{
    protected string $accessToken;
    protected string $accountId;

    public function __construct()
    {
        $this->accessToken = config('services.instagram.access_token');
        $this->accountId = config('services.instagram.account_id');
    }

    public function publishImage(string $imageUrl, string $caption): array
    {
        // Step 1: Crear contenedor de medios
        $containerResponse = Http::post(
            "https://graph.facebook.com/v18.0/{$this->accountId}/media",
            [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $this->accessToken,
            ]
        );

        $containerId = $containerResponse->json('id');

        // Step 2: Publicar el contenedor
        $publishResponse = Http::post(
            "https://graph.facebook.com/v18.0/{$this->accountId}/media_publish",
            [
                'creation_id' => $containerId,
                'access_token' => $this->accessToken,
            ]
        );

        return $publishResponse->json();
    }

    public function getMediaInsights(string $mediaId): array
    {
        $response = Http::get(
            "https://graph.facebook.com/v18.0/{$mediaId}/insights",
            [
                'metric' => 'engagement,impressions,reach,saved',
                'access_token' => $this->accessToken,
            ]
        );

        return $response->json();
    }
}
```

### 6.4 Social Posts Management Resource

```php
// app/Filament/Resources/SocialPostResource.php

namespace App\Filament\Resources;

use App\Models\SocialPost;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class SocialPostResource extends Resource
{
    protected static ?string $model = SocialPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-share';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Posts Sociales';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Item a promocionar')
                        ->relationship('product', 'title')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Textarea::make('content')
                        ->label('Post text')
                        ->rows(4)
                        ->maxLength(2200)
                        ->helperText('Instagram allows a maximum of 2,200 characters'),

                    Forms\Components\FileUpload::make('media')
                        ->label('Image/Video')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('social-media'),

                    Forms\Components\TagsInput::make('hashtags')
                        ->label('Hashtags')
                        ->separator(',')
                        ->suggestions([
                            '#saas', '#product', '#custom', '#business',
                            '#fashion', '#style', '#design', '#custom',
                        ]),
                ]),

            Forms\Components\Section::make('Publishing')
                ->schema([
                    Forms\Components\CheckboxList::make('platforms')
                        ->label('Plataformas')
                        ->options([
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'twitter' => 'Twitter/X',
                        ])
                        ->columns(3),

                    Forms\Components\Toggle::make('schedule')
                        ->label('Schedule post')
                        ->reactive(),

                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Fecha y hora')
                        ->visible(fn ($get) => $get('schedule'))
                        ->minDate(now()),
                ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('media')
                    ->label('Media')
                    ->circular(),

                Tables\Columns\TextColumn::make('content')
                    ->label('Content')
                    ->limit(50),

                Tables\Columns\TagsColumn::make('platforms')
                    ->label('Plataformas'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'scheduled',
                        'success' => 'published',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicado')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('publish_now')
                    ->label('Publicar ahora')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (SocialPost $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (SocialPost $record, FacebookService $fb, InstagramService $ig) {
                        foreach ($record->platforms as $platform) {
                            match($platform) {
                                'facebook' => $fb->publishPost($record->content, $record->media_url),
                                'instagram' => $ig->publishImage($record->media_url, $record->full_content),
                            };
                        }

                        $record->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Post publicado exitosamente')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_ai')
                    ->label('Generate with AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('tone')
                            ->label('Tono')
                            ->options([
                                'professional' => 'Professional',
                                'casual' => 'Casual',
                                'promotional' => 'Promocional',
                                'inspirational' => 'Inspirational',
                            ]),
                    ])
                    ->action(function (SocialPost $record, array $data, AIContentService $ai) {
                        $content = $ai->generateSocialPost(
                            $record->item->name ?? 'Product',
                            $record->platforms[0] ?? 'instagram'
                        );

                        $record->update(['content' => $content]);
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }
}
```

### 6.5 Social Metrics Widget

```php
// app/Filament/Widgets/SocialMetricsWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\FacebookService;
use App\Services\InstagramService;

class SocialMetricsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $fb = app(FacebookService::class);
        $ig = app(InstagramService::class);

        // Get metrics (cached for performance)
        $fbInsights = cache()->remember('fb_insights', 3600, fn () => $fb->getInsights());

        return [
            Stat::make('FB Followers', number_format($fbInsights['page_fans'] ?? 0))
                ->description('Page fans')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make('Weekly Reach', number_format($fbInsights['page_impressions'] ?? 0))
                ->description('Total impressions')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Posts This Month', SocialPost::whereMonth('published_at', now())->count())
                ->description('Publications')
                ->color('warning'),
        ];
    }
}
```

---

## 7. Customization Comparison

### FilamentPHP vs Other Solutions

| Feature | FilamentPHP | Nova | Backpack | WordPress |
|---------|-------------|------|----------|-----------|
| UI Customization | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| Wizards/Steps | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ | Plugins |
| Integrations | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| WYSIWYG | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Learning Curve | Medium | Medium | Low | Low |
| Cost | Free | $199/site | Free | Free |
| Performance | Excellent | Excellent | Good | Variable |

---

## 8. Conclusions and Recommendations

### FilamentPHP Strengths

1. **Extremely customizable** — From colors to complete components
2. **Active plugin ecosystem** — Solutions for almost everything
3. **Native TALL Stack** — Perfect integration with Laravel
4. **Free and open source** — No license costs
5. **Excellent documentation** — Easy to learn

### Limitations

1. **Requires PHP/Laravel knowledge** — Not drag-and-drop
2. **Integrations not included** — Must be developed or use packages
3. **Frequent updates** — May require maintenance

### Recommendations for SaaS Template

| Feature | Recommendation | Priority |
|---------|---------------|-----------|
| Visual customization | Implement custom brand theme | High |
| Order wizard | Create multi-step flow | High |
| Payments (Stripe) | Integrate for subscriptions | Medium |
| Blog/Content | Tiptap Editor plugin | Medium |
| AI Marketing | OpenAI for descriptions | Low |
| Social Media | Phase 2 of project | Low |

### Effort Estimation

| Module | Estimated Hours |
|--------|-----------------|
| Custom theme | 4-8 hrs |
| Order wizard | 8-16 hrs |
| Payment integration | 16-24 hrs |
| Blog system | 8-16 hrs |
| AI integration | 8-16 hrs |
| Social media | 24-40 hrs |

---

## 9. Additional Resources

### Official Documentation
- [FilamentPHP Docs](https://filamentphp.com/docs)
- [Filament Plugins](https://filamentphp.com/plugins)

### Recommended Packages
- [Tiptap Editor](https://github.com/awcodes/filament-tiptap-editor) — Advanced WYSIWYG
- [Spatie Media Library](https://github.com/spatie/laravel-medialibrary) — Media management
- [Spatie Permissions](https://github.com/spatie/laravel-permission) — Roles and permissions
- [Filament Fabricator](https://github.com/z3d0x/filament-fabricator) — Page Builder

### Tutorials
- [Filament 3 Crash Course](https://www.youtube.com/playlist?list=PL6tf8fRbavl3LxHRoxy5eHGXnKvZdBOkf)
- [Laracasts Filament](https://laracasts.com/series/build-advanced-components-for-filament)

---

**Report generated:** 2026-02-05
**Analyst:** Claude AI
**Project:** Generic Multi-Tenant SaaS Template
