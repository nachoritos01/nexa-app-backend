<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Agency\Client;

/**
 * Verifies the agency activity log: mutations through the API are recorded with the
 * authenticated causer and the tenant, exposed read-only via GET /api/agency/activity,
 * and never leak across tenants.
 */
class ActivityApiTest extends AgencyTestCase
{
    public function test_creating_an_entity_is_logged_with_causer_and_tenant(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/agency/clients', ['name' => 'ACME'])
            ->assertCreated();

        $response = $this->withToken($this->token)
            ->getJson('/api/agency/activity?subjectType=client')
            ->assertOk();

        $response->assertJsonPath('data.0.description', 'created')
            ->assertJsonPath('data.0.subjectType', 'Client')
            ->assertJsonPath('data.0.causerName', $this->user->name)
            ->assertJsonPath('data.0.changes.attributes.name', 'ACME');
    }

    public function test_updating_logs_old_and_new_values(): void
    {
        $clientId = $this->withToken($this->token)
            ->postJson('/api/agency/clients', ['name' => 'Old name'])
            ->json('data.id');

        $this->withToken($this->token)
            ->putJson("/api/agency/clients/{$clientId}", ['name' => 'New name'])
            ->assertOk();

        // Newest first → the update is entry 0.
        $this->withToken($this->token)
            ->getJson("/api/agency/activity?subjectType=client&subjectId={$clientId}")
            ->assertOk()
            ->assertJsonPath('data.0.description', 'updated')
            ->assertJsonPath('data.0.changes.attributes.name', 'New name')
            ->assertJsonPath('data.0.changes.old.name', 'Old name');
    }

    public function test_a_tenant_cannot_see_another_tenants_activity(): void
    {
        // Tenant A records an activity via a direct mutation (logged with tenant_id = A).
        // Done without an HTTP call so tenant B's request below is the only authenticated
        // one — chaining two different tokens leaks the first user through the sanctum guard.
        Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'A client',
            'status' => 'Activo',
            'pipeline_stage' => 'Lead',
        ]);

        // Tenant B: a separate owner + tenant + token.
        [, , $tokenB] = $this->makeTenantUser();

        $response = $this->withToken($tokenB)
            ->getJson('/api/agency/activity')
            ->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    public function test_perPage_is_capped_at_50(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/agency/activity?perPage=100')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['perPage']);
    }

    public function test_unknown_subject_type_is_rejected(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/agency/activity?subjectType=App\\Models\\User')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subjectType']);
    }
}
