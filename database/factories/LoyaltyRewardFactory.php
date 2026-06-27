<?php

namespace Database\Factories;

use App\Models\LoyaltyReward;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyRewardFactory extends Factory
{
    protected $model = LoyaltyReward::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'type' => 'percentage',
            'points_cost' => 100,
            'value' => 10.00,
            'icon' => 'heroicon-o-gift',
            'min_tier' => 0,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forTier(int $tier): static
    {
        return $this->state(fn () => ['min_tier' => $tier]);
    }

    public function costing(int $points): static
    {
        return $this->state(fn () => ['points_cost' => $points]);
    }
}
