<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Onboarding extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string $view = 'filament.pages.onboarding';

    protected static ?string $title = 'Getting Started';

    protected static ?string $navigationLabel = 'Getting Started';

    protected static ?int $navigationSort = -1;

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = currentTenant();

        return $tenant && ! $tenant->isOnboardingComplete();
    }

    public function mount(): void
    {
        $tenant = currentTenant();

        if (! $tenant || $tenant->isOnboardingComplete()) {
            $this->redirect('/admin');

            return;
        }

        $this->form->fill([
            'business_name' => $tenant->settings['business_name'] ?? $tenant->name,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Setup Business')
                        ->icon('heroicon-o-building-storefront')
                        ->description('Configure your business details')
                        ->schema([
                            TextInput::make('business_name')
                                ->label('Business Name')
                                ->required()
                                ->maxLength(255),
                        ]),
                    Wizard\Step::make('Create First Item')
                        ->icon('heroicon-o-cube')
                        ->description('Add your first product or service')
                        ->schema([
                            TextInput::make('item_name')
                                ->label('Item Name')
                                ->helperText('You can add more items later from the Items menu.')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('item_price')
                                ->label('Price')
                                ->numeric()
                                ->prefix('$'),
                        ]),
                ])
                    ->submitAction(view('filament.pages.onboarding-submit-button')),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        /** @var Tenant $tenant */
        $tenant = currentTenant();

        // Step 1: Update business name
        if (! empty($data['business_name'])) {
            $settings = $tenant->settings ?? [];
            $settings['business_name'] = $data['business_name'];
            $tenant->settings = $settings;
            $tenant->save();
            $tenant->markOnboardingStep('setup_business');
        }

        // Step 2: Create first item
        if (! empty($data['item_name'])) {
            \App\Models\Item::create([
                'tenant_id' => $tenant->id,
                'name' => $data['item_name'],
                'price' => $data['item_price'] ?? null,
                'is_active' => true,
            ]);
            $tenant->markOnboardingStep('first_item');
        }

        // Mark onboarding complete
        $tenant->update(['onboarding_completed_at' => now()]);

        Notification::make()
            ->title('Welcome! Your setup is complete.')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
