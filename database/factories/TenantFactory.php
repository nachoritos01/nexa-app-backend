<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'plan' => 'starter',
            'owner_id' => User::factory(),
            'is_active' => true,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn () => ['plan' => 'pro']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function trial(?int $days = null): static
    {
        $days ??= config('saas.trial.days', 14);

        return $this->state(fn () => [
            'trial_ends_at' => now()->addDays($days),
        ]);
    }

    public function onboarded(): static
    {
        return $this->state(fn () => [
            'onboarding_completed_at' => now(),
        ]);
    }

    public function subscribed(?string $plan = 'growth'): static
    {
        return $this->state(fn () => [
            'plan' => $plan,
            'subscribed_at' => now(),
        ]);
    }
}
