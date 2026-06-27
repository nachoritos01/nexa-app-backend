<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PluginResource\Pages;
use App\Models\Plugin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PluginResource extends Resource
{
    protected static ?string $model = Plugin::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Plugins';

    protected static ?string $modelLabel = 'Plugin';

    protected static ?string $pluralModelLabel = 'Plugins';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('icon')
                            ->default('heroicon-o-puzzle-piece')
                            ->maxLength(255),
                        Forms\Components\Select::make('category')
                            ->options([
                                'billing' => 'Billing',
                                'engagement' => 'Engagement',
                                'operations' => 'Operations',
                                'developer' => 'Developer',
                                'reporting' => 'Reporting',
                                'general' => 'General',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Toggle::make('is_free')
                            ->default(true)
                            ->reactive(),
                        Forms\Components\TextInput::make('price_monthly')
                            ->label('Price (cents/mo)')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Forms\Get $get): bool => ! $get('is_free')),
                        Forms\Components\TextInput::make('stripe_price_id')
                            ->label('Stripe Price ID')
                            ->maxLength(255)
                            ->helperText('Required for paid plugins. Create a recurring price in Stripe Dashboard.')
                            ->required(fn (Forms\Get $get): bool => ! $get('is_free'))
                            ->visible(fn (Forms\Get $get): bool => ! $get('is_free')),
                    ])->columns(2),

                Forms\Components\Section::make('Plan & Module Configuration')
                    ->schema([
                        Forms\Components\CheckboxList::make('included_in_plans')
                            ->options([
                                'starter' => 'Starter',
                                'growth' => 'Growth',
                                'pro' => 'Pro',
                            ])
                            ->columns(3),
                        Forms\Components\CheckboxList::make('required_modules')
                            ->options(
                                collect(config('modules', []))
                                    ->mapWithKeys(fn ($enabled, $key) => [$key => ucfirst(str_replace('_', ' ', $key))])
                                    ->all()
                            )
                            ->columns(3)
                            ->helperText('Global module flags that must be enabled for this plugin to be available.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'billing' => 'success',
                        'engagement' => 'info',
                        'operations' => 'warning',
                        'developer' => 'danger',
                        'reporting' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Price')
                    ->formatStateUsing(fn (Plugin $record): string => $record->formattedPrice()),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('tenants_count')
                    ->label('Tenants')
                    ->counts('tenants'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'billing' => 'Billing',
                        'engagement' => 'Engagement',
                        'operations' => 'Operations',
                        'developer' => 'Developer',
                        'reporting' => 'Reporting',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlugins::route('/'),
            'create' => Pages\CreatePlugin::route('/create'),
            'edit' => Pages\EditPlugin::route('/{record}/edit'),
        ];
    }
}
