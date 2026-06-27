<?php

namespace App\Filament\Resources;

use App\Enums\OrderPriority;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Location;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $modelLabel = 'Order';

    protected static ?string $pluralModelLabel = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Customer')
                        ->icon('heroicon-o-user')
                        ->description('Customer information')
                        ->schema(static::getCustomerFormSchema()),

                    Wizard\Step::make('Items')
                        ->icon('heroicon-o-cube')
                        ->description('Order items')
                        ->schema(static::getItemsFormSchema()),

                    Wizard\Step::make('Details')
                        ->icon('heroicon-o-document-text')
                        ->description('Order details')
                        ->schema(static::getDetailsFormSchema()),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    public static function getCustomerFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Customer')
                ->schema([
                    Forms\Components\TextInput::make('customer_name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('customer_phone')
                        ->label('Phone')
                        ->tel()
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $state, Set $set) {
                            $customer = Customer::where('phone', $state)->first();
                            if ($customer) {
                                $set('customer_name', $customer->name);
                                $set('customer_email', $customer->email);
                                $set('customer_id', $customer->id);
                            }
                        }),
                    Forms\Components\TextInput::make('customer_email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\Hidden::make('customer_id'),
                ])->columns(3),
        ];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    public static function getItemsFormSchema(): array
    {
        return [
            Forms\Components\Repeater::make('lines')
                ->relationship()
                ->label('Order Lines')
                ->schema([
                    Forms\Components\Select::make('item_id')
                        ->label('Item')
                        ->options(fn () => Item::active()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $item = Item::find($state);
                                if ($item) {
                                    $set('unit_price', $item->price ?? 0);
                                    $set('description', $item->name);
                                }
                            }
                        }),
                    Forms\Components\TextInput::make('description')
                        ->label('Description')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('variant')
                        ->label('Variant')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('quantity')
                        ->label('Qty')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $qty = (int) ($get('quantity') ?? 0);
                            $price = (float) ($get('unit_price') ?? 0);
                            $set('subtotal', number_format($qty * $price, 2, '.', ''));
                        }),
                    Forms\Components\TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('$')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $qty = (int) ($get('quantity') ?? 0);
                            $price = (float) ($get('unit_price') ?? 0);
                            $set('subtotal', number_format($qty * $price, 2, '.', ''));
                        }),
                    Forms\Components\TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(),
                ])
                ->columns(6)
                ->defaultItems(1)
                ->addActionLabel('Add line')
                ->reorderable(false)
                ->collapsible()
                ->itemLabel(fn (array $state): string => ($state['description'] ?? 'Item') . ' x' . ($state['quantity'] ?? 1)),
        ];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    public static function getDetailsFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Order Details')
                ->schema([
                    Forms\Components\Select::make('location_id')
                        ->label('Location')
                        ->options(fn () => Location::active()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->visible(fn () => hasModule('locations')),
                    Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->options(OrderPriority::options())
                        ->default(OrderPriority::Normal->value),
                    Forms\Components\DatePicker::make('estimated_at')
                        ->label('Estimated Completion'),
                    Forms\Components\TextInput::make('initial_payment')
                        ->label('Initial Payment')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('$')
                        ->default(0),
                ])->columns(2),

            Forms\Components\Section::make('Notes & Attachments')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('attachments')
                        ->label('Attachments')
                        ->multiple()
                        ->disk('public')
                        ->directory('order-attachments')
                        ->maxFiles(5)
                        ->columnSpanFull(),
                ])->collapsible(),
        ];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    public static function getWizardSteps(): array
    {
        return [
            Wizard\Step::make('Customer')
                ->icon('heroicon-o-user')
                ->description('Customer information')
                ->schema(static::getCustomerFormSchema()),

            Wizard\Step::make('Items')
                ->icon('heroicon-o-cube')
                ->description('Order items')
                ->schema(static::getItemsFormSchema()),

            Wizard\Step::make('Details')
                ->icon('heroicon-o-document-text')
                ->description('Order details')
                ->schema(static::getDetailsFormSchema()),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (OrderPriority $state): string => $state->color())
                    ->formatStateUsing(fn (OrderPriority $state): string => $state->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn () => hasModule('locations')),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money()
                    ->color(fn (Order $record): string => $record->balance > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderStatus::options()),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options(OrderPriority::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('advance_status')
                    ->label(fn (Order $record): string => match ($record->status) {
                        OrderStatus::Draft => 'Submit',
                        OrderStatus::Pending => 'Confirm',
                        OrderStatus::Confirmed => 'Start',
                        OrderStatus::InProgress => 'Complete',
                        default => 'Action',
                    })
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => ! in_array($record->status, [
                        OrderStatus::Completed,
                        OrderStatus::Cancelled,
                    ]))
                    ->action(function (Order $record) {
                        match ($record->status) {
                            OrderStatus::Draft, OrderStatus::Pending => $record->confirm(),
                            OrderStatus::Confirmed, OrderStatus::InProgress => $record->complete(),
                            default => null,
                        };
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => ! in_array($record->status, [
                        OrderStatus::Completed,
                        OrderStatus::Cancelled,
                    ]))
                    ->action(fn (Order $record) => $record->cancel()),
                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (Order $record) {
                        $newOrder = $record->duplicate();

                        Notification::make()
                            ->title('Order duplicated')
                            ->body("New order #{$newOrder->id} created as draft.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return array_filter([
            hasModule('payments') ? RelationManagers\PaymentsRelationManager::class : null,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
