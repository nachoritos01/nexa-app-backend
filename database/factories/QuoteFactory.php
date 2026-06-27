<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $items = [
            [
                'description' => fake()->words(3, true),
                'quantity' => fake()->numberBetween(1, 10),
                'unit_price' => fake()->numberBetween(50, 500),
            ],
        ];

        $subtotal = collect($items)->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

        return [
            'tenant_id' => Tenant::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'title' => fake()->sentence(3),
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'accepted_at' => now(),
        ]);
    }
}
