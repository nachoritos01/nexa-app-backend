<?php

namespace App\Filament\Pages\Auth;

use App\Enums\PlanType;
use App\Events\TenantCreated;
use App\Models\Referral;
use App\Models\Tenant;
use App\Services\TenantSeedService;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    #[\Livewire\Attributes\Url]
    public ?string $plan = null;

    #[\Livewire\Attributes\Url]
    public ?string $ref = null;

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getBusinessNameFormComponent(),
                        $this->getPhoneFormComponent(),
                        $this->getCityFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getBusinessNameFormComponent(): Component
    {
        return TextInput::make('business_name')
            ->label('Business name')
            ->required()
            ->maxLength(255)
            ->placeholder('e.g. My Business');
    }

    protected function getPhoneFormComponent(): Component
    {
        return TextInput::make('phone')
            ->label('Phone')
            ->tel()
            ->required()
            ->maxLength(20)
            ->placeholder('e.g. 5551234567');
    }

    protected function getCityFormComponent(): Component
    {
        return TextInput::make('city')
            ->label('City')
            ->required()
            ->maxLength(100)
            ->placeholder('e.g. New York');
    }

    /**
     * Override to remove dehydrateStateUsing(Hash::make).
     * The User model has 'password' => 'hashed' cast which handles hashing.
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute'));
    }

    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create user (password will be hashed by 'hashed' cast)
            $user = $this->getUserModel()::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            // Look up referrer before creating tenant (to calculate bonus trial)
            $referrer = null;
            if ($this->ref) {
                $referrer = Tenant::where('referral_code', $this->ref)->first();
            }

            // Calculate trial days: base + referral bonus for referred tenant
            $trialDays = config('saas.trial.days', 14);
            if ($referrer) {
                $trialDays += config('saas.trial.referral_bonus_referred', 7);
            }

            // Create tenant
            $slug = Tenant::generateSlug($data['business_name']);

            // Pre-select plan from ?plan= query param (Livewire #[Url] property)
            $selectedPlan = in_array($this->plan, PlanType::values()) ? $this->plan : PlanType::default()->value;

            $tenant = Tenant::create([
                'name' => $data['business_name'],
                'slug' => $slug,
                'plan' => $selectedPlan,
                'owner_id' => $user->id,
                'is_active' => true,
                'trial_ends_at' => now()->addDays($trialDays),
                'settings' => [
                    'phone' => $data['phone'],
                    'city' => $data['city'],
                    'business_name' => $data['business_name'],
                    'contact_phone' => $data['phone'],
                    'address' => $data['city'],
                ],
            ]);

            // Attach user as owner
            $tenant->users()->attach($user->id, ['role' => 'owner']);

            // Assign Spatie owner role
            if (Role::where('name', 'owner')->exists()) {
                $user->assignRole('owner');
            }

            // Bind tenant for seed service
            session(['tenant_id' => $tenant->id]);

            // Seed template data
            app(TenantSeedService::class)->seedForTenant($tenant);

            TenantCreated::dispatch($tenant);

            // Capture referral and give bonus to referrer
            if ($referrer && $referrer->id !== $tenant->id) {
                Referral::create([
                    'referrer_tenant_id' => $referrer->id,
                    'referred_tenant_id' => $tenant->id,
                ]);

                $referrer->addReferralBonusDays(
                    config('saas.trial.referral_bonus_referrer', 15),
                    cap: config('saas.trial.referral_max_registration_days', 90),
                );
            }

            return $user;
        });
    }
}
