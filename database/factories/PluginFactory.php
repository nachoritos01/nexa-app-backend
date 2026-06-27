<?php

namespace Database\Factories;

use App\Models\Plugin;
use Illuminate\Database\Eloquent\Factories\Factory;

class PluginFactory extends Factory
{
    protected $model = Plugin::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => 'heroicon-o-puzzle-piece',
            'category' => 'general',
            'is_active' => true,
            'is_free' => true,
            'price_monthly' => 0,
            'included_in_plans' => ['starter', 'growth', 'pro'],
            'required_modules' => [],
            'sort_order' => 0,
        ];
    }

    public function paid(int $priceCents = 999): static
    {
        return $this->state(fn () => [
            'is_free' => false,
            'price_monthly' => $priceCents,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forPlans(array $plans): static
    {
        return $this->state(fn () => ['included_in_plans' => $plans]);
    }

    public function withStripePrice(string $priceId = 'price_test_123'): static
    {
        return $this->state(fn () => [
            'is_free' => false,
            'price_monthly' => 999,
            'stripe_price_id' => $priceId,
        ]);
    }
}
