<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyRewardResource\Pages;
use App\Models\LoyaltyReward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Loyalty Rewards';

    protected static ?string $modelLabel = 'Reward';

    protected static ?string $pluralModelLabel = 'Loyalty Rewards';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    public static function canAccess(): bool
    {
        return hasModule('loyalty');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return hasModule('loyalty');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'discount_percent' => 'Discount (%)',
                                'free_month' => 'Free Month',
                                'free_months' => 'Free Months',
                                'storage_upgrade' => 'Storage Upgrade',
                                'feature_unlock' => 'Feature Unlock',
                                'plan_upgrade' => 'Plan Upgrade',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('points_cost')
                            ->label('Points Cost')
                            ->numeric()
                            ->required()
                            ->minValue(1),
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->helperText('E.g. 5.00 for 5% discount, 10.00 for 10 GB storage'),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon')
                            ->maxLength(50)
                            ->default('gift')
                            ->helperText('Heroicon name without prefix (e.g. tag, fire, sparkles)'),
                    ])->columns(2),

                Forms\Components\Section::make('Availability')
                    ->schema([
                        Forms\Components\Select::make('min_tier')
                            ->label('Minimum Tier')
                            ->options([
                                0 => 'Bronze (All)',
                                1 => 'Silver+',
                                2 => 'Gold+',
                                3 => 'VIP only',
                            ])
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('points_cost')
                    ->label('Points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->numeric(2)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('min_tier')
                    ->label('Min Tier')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'All',
                        1 => 'Silver+',
                        2 => 'Gold+',
                        3 => 'VIP',
                        default => '-',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyRewards::route('/'),
            'create' => Pages\CreateLoyaltyReward::route('/create'),
            'edit' => Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }
}
