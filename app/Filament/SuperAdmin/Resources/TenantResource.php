<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $modelLabel = 'Tenant';

    protected static ?string $pluralModelLabel = 'Tenants';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('plan')
                    ->options([
                        'starter' => 'Starter',
                        'growth' => 'Growth',
                        'pro' => 'Pro',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pro' => 'success',
                        'growth' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscribed_at')
                    ->label('Subscribed')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('health_score')
                    ->label('Health')
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}" : 'N/A')
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= config('saas.retention.health_score.healthy_threshold', 70) => 'success',
                        $state >= config('saas.retention.health_score.at_risk_threshold', 50) => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users'),
                Tables\Columns\TextColumn::make('active_plugins_count')
                    ->label('Plugins')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('active_plugins_list')
                    ->label('Active Plugins')
                    ->getStateUsing(fn (Tenant $record) => $record->plugins->pluck('name')->join(', '))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'starter' => 'Starter',
                        'growth' => 'Growth',
                        'pro' => 'Pro',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('health_status')
                    ->label('Health')
                    ->options([
                        'healthy' => 'Healthy (' . config('saas.retention.health_score.healthy_threshold', 70) . '+)',
                        'at_risk' => 'At Risk (' . config('saas.retention.health_score.at_risk_threshold', 50) . '-' . (config('saas.retention.health_score.healthy_threshold', 70) - 1) . ')',
                        'critical' => 'Critical (<' . config('saas.retention.health_score.at_risk_threshold', 50) . ')',
                    ])
                    ->query(function ($query, array $data) {
                        $healthy = config('saas.retention.health_score.healthy_threshold', 70);
                        $atRisk = config('saas.retention.health_score.at_risk_threshold', 50);

                        return match ($data['value'] ?? null) {
                            'healthy' => $query->where('health_score', '>=', $healthy),
                            'at_risk' => $query->where('health_score', '>=', $atRisk)->where('health_score', '<', $healthy),
                            'critical' => $query->where('health_score', '<', $atRisk),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Tenant $record): bool => $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Tenant $record): void {
                        $record->suspend();
                        Notification::make()->title('Tenant suspended')->success()->send();
                    }),
                Tables\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Tenant $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Tenant $record): void {
                        $record->update(['is_active' => true]);
                        Notification::make()->title('Tenant reactivated')->success()->send();
                    }),
                Tables\Actions\Action::make('reset_onboarding')
                    ->label('Reset Onboarding')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Tenant $record): void {
                        $record->update(['onboarding_steps' => null, 'onboarding_completed_at' => null]);
                        Notification::make()->title('Onboarding reset')->success()->send();
                    }),
                Tables\Actions\Action::make('subscribe')
                    ->label('Subscribe')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Tenant $record): bool => $record->subscribed_at === null)
                    ->form([
                        Forms\Components\Select::make('plan')
                            ->options([
                                'starter' => 'Starter',
                                'growth' => 'Growth',
                                'pro' => 'Pro',
                            ])
                            ->default(fn (Tenant $record): string => $record->plan)
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Subscribe Tenant')
                    ->modalDescription(fn (Tenant $record): string => "Mark \"{$record->name}\" as subscribed.")
                    ->action(function (Tenant $record, array $data): void {
                        $record->update([
                            'plan' => $data['plan'],
                            'subscribed_at' => now(),
                            'is_active' => true,
                        ]);

                        app(\App\Services\BillingHistoryService::class)
                            ->recordSubscriptionStarted($record, $data['plan']);

                        Notification::make()->title('Tenant subscribed to ' . ucfirst($data['plan']))->success()->send();
                    }),
                Tables\Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (Tenant $record): bool => $record->owner_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate Tenant Owner')
                    ->modalDescription(fn (Tenant $record): string => "You will be logged in as {$record->owner?->name} ({$record->owner?->email})")
                    ->action(function (Tenant $record): void {
                        $owner = $record->owner;

                        if (! $owner) {
                            Notification::make()->title('No owner found')->danger()->send();

                            return;
                        }

                        session(['impersonating_from' => Auth::id()]);
                        Auth::login($owner);
                        session()->forget('password_hash_web');
                        session(['tenant_id' => $record->id]);

                        redirect('/admin');
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Tenant Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('slug'),
                        Infolists\Components\TextEntry::make('plan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pro' => 'success',
                                'growth' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->dateTime('d/m/Y'),
                        Infolists\Components\TextEntry::make('subscribed_at')
                            ->label('Subscribed')
                            ->dateTime('d/m/Y')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('health_score')
                            ->label('Health')
                            ->badge()
                            ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}" : 'N/A')
                            ->color(fn (?int $state): string => match (true) {
                                $state === null => 'gray',
                                $state >= config('saas.retention.health_score.healthy_threshold', 70) => 'success',
                                $state >= config('saas.retention.health_score.at_risk_threshold', 50) => 'warning',
                                default => 'danger',
                            }),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Active Plugins')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('plugins')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Plugin'),
                                Infolists\Components\TextEntry::make('pivot.billing_type')
                                    ->label('Billing')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'paid' => 'warning',
                                        'included' => 'success',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('pivot.activated_at')
                                    ->label('Activated')
                                    ->dateTime('d/m/Y'),
                                Infolists\Components\TextEntry::make('pivot.stripe_subscription_item_id')
                                    ->label('Stripe Item ID')
                                    ->placeholder('—'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }
}
