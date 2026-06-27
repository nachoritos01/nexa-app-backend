<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'is_active' => true,
            'loyalty_points' => 0,
            'loyalty_lifetime_points' => 0,
            'first_purchase_bonus' => false,
        ];
    }

    public function withPoints(int $points, ?int $lifetime = null): static
    {
        return $this->state(fn () => [
            'loyalty_points' => $points,
            'loyalty_lifetime_points' => $lifetime ?? $points,
        ]);
    }

    public function silverTier(): static
    {
        return $this->withPoints(500, 500);
    }

    public function goldTier(): static
    {
        return $this->withPoints(1500, 1500);
    }

    public function vipTier(): static
    {
        return $this->withPoints(3000, 3000);
    }
}
