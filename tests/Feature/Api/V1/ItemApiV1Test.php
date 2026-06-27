<?php

namespace Tests\Feature\Api\V1;

use App\Models\Item;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiV1Test extends TestCase
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

    public function test_list_items_returns_paginated_results(): void
    {
        Item::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/items');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total', 'page', 'per_page']]);
    }

    public function test_show_item_returns_details(): void
    {
        $item = Item::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/items/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }

    public function test_other_tenant_token_does_not_see_our_items(): void
    {
        Item::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create another tenant with its own token
        $otherUser = User::factory()->create();
        $otherTenant = Tenant::factory()->pro()->create(['owner_id' => $otherUser->id]);
        $otherTenant->users()->attach($otherUser->id, ['role' => 'owner']);

        $otherSanctumToken = $otherUser->createToken('Other');
        $otherSanctumToken->accessToken->update(['tenant_id' => $otherTenant->id]);

        $response = $this->withToken($otherSanctumToken->plainTextToken)
            ->getJson('/api/v1/items');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0);
    }
}
