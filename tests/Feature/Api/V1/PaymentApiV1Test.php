<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiV1Test extends TestCase
{
    use RefreshDatabase;

    private string $token;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $this->tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->syncRoles(['owner']);

        app()->instance('currentTenant', $this->tenant);

        $sanctumToken = $user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $this->tenant->id]);
        $this->token = $sanctumToken->plainTextToken;
    }

    public function test_list_payments_returns_paginated_results(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 500,
            'method' => 'cash',
            'received_at' => now(),
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/payments');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total']]);
    }

    public function test_filter_payments_by_order_id(): void
    {
        $order1 = Order::factory()->create(['tenant_id' => $this->tenant->id]);
        $order2 = Order::factory()->create(['tenant_id' => $this->tenant->id]);

        Payment::create(['order_id' => $order1->id, 'amount' => 500, 'method' => 'cash', 'received_at' => now()]);
        Payment::create(['order_id' => $order2->id, 'amount' => 300, 'method' => 'cash', 'received_at' => now()]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/payments?order_id={$order1->id}");

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_show_payment_returns_details(): void
    {
        $order = Order::factory()->create(['tenant_id' => $this->tenant->id]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 750,
            'method' => 'transfer',
            'received_at' => now(),
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/payments/{$payment->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'amount', 'order']]);
    }
}
