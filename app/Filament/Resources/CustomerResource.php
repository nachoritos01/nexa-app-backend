<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return hasModule('customer_portal');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return hasModule('customer_portal');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Addresses')
                    ->schema([
                        Forms\Components\Repeater::make('addresses')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('label')
                                    ->label('Label')
                                    ->options([
                                        'home' => 'Home',
                                        'office' => 'Office',
                                        'other' => 'Other',
                                    ])
                                    ->default('home'),
                                Forms\Components\TextInput::make('street')
                                    ->label('Street')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('district')
                                    ->label('District')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('state')
                                    ->label('State / Province')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('zip')
                                    ->label('Postal Code')
                                    ->maxLength(20),
                                Forms\Components\Textarea::make('references')
                                    ->label('References')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add address')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => match ($state['label'] ?? 'home') {
                                'home' => 'Home',
                                'office' => 'Office',
                                default => 'Other',
                            } . ($state['street'] ? ' - ' . $state['street'] : '')),
                    ])->collapsible(),

                Forms\Components\Section::make('Portal Access')
                    ->schema([
                        Forms\Components\Toggle::make('portal_enabled')
                            ->label('Enable Portal')
                            ->helperText('The customer will use their phone + password to access.')
                            ->live()
                            ->afterStateHydrated(fn (Forms\Components\Toggle $component, ?Customer $record) => $component->state(filled($record?->password)))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('portal_enabled'))
                            ->required(fn (Forms\Get $get, string $operation, ?Customer $record): bool => (bool) $get('portal_enabled') && ($operation === 'create' || ! filled($record?->password)))
                            ->maxLength(255)
                            ->helperText(fn (string $operation): string => $operation === 'edit' ? 'Leave empty to keep current password.' : ''),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('portal_enabled'))
                            ->dehydrated(false),
                    ])->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Loyalty')
                    ->schema([
                        Forms\Components\Placeholder::make('loyalty_points_display')
                            ->label('Available Points')
                            ->content(fn (?Customer $record): string => $record ? number_format($record->loyalty_points) : '0'),
                        Forms\Components\Placeholder::make('loyalty_lifetime_display')
                            ->label('Lifetime Points')
                            ->content(fn (?Customer $record): string => $record ? number_format($record->loyalty_lifetime_points) : '0'),
                        Forms\Components\Placeholder::make('loyalty_tier_display')
                            ->label('Current Tier')
                            ->content(fn (?Customer $record): string => $record ? $record->loyalty_tier->label() . ' (x' . $record->loyalty_tier->multiplier() . ')' : 'Bronze'),
                        Forms\Components\Toggle::make('first_purchase_bonus')
                            ->label('First Purchase Bonus Credited')
                            ->helperText('Whether the first purchase bonus has been credited.'),
                    ])->columns(4)
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (): bool => hasModule('loyalty')),

                Forms\Components\Section::make('Tags & Metadata')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->separator(','),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata'),
                        Forms\Components\DatePicker::make('birthday')
                            ->label('Birthday'),
                    ])->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('password')
                    ->label('Portal')
                    ->boolean()
                    ->getStateUsing(fn (Customer $record): bool => filled($record->password))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
