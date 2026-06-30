<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Agency\Project;
use App\Models\Tenant;
use App\Models\User;

class ProjectApiTest extends AgencyTestCase
{
    public function test_requires_authentication(): void
    {
        $this->getJson('/api/agency/projects')->assertUnauthorized();
    }

    public function test_list_starts_empty(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/agency/projects')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_create_returns_full_row_with_empty_arrays(): void
    {
        // Only the required field — defaults and unset jsonb arrays must still
        // come back in the response (create() refreshes + Resource coalesces null -> []).
        $response = $this->withToken($this->token)
            ->postJson('/api/agency/projects', ['name' => 'Minimal'])
            ->assertCreated();

        $data = $response->json('data');
        $this->assertNotEmpty($data['id']);                 // server-generated UUID
        $this->assertSame('Minimal', $data['name']);
        $this->assertSame('pending', $data['status']);       // DB default
        $this->assertSame(0, $data['progress']);
        $this->assertSame([], $data['teamMembers']);         // null jsonb -> []
        $this->assertSame([], $data['tasks']);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);
    }

    public function test_create_round_trips_camelcase_and_embedded_arrays(): void
    {
        $payload = [
            'name' => 'Portal',
            'clientId' => 'client-uuid-123',
            'type' => 'web_development',
            'status' => 'in_progress',
            'budget' => 180000,
            'startDate' => '2026-06-01',
            'teamMembers' => ['tm-1', 'tm-2'],
            'tasks' => [
                ['id' => 't1', 'name' => 'Wireframes', 'status' => 'completed', 'assignedTo' => ['tm-2'], 'createdAt' => '2026-06-01'],
            ],
            'progress' => 55,
        ];

        $data = $this->withToken($this->token)
            ->postJson('/api/agency/projects', $payload)
            ->assertCreated()
            ->json('data');

        $this->assertSame('client-uuid-123', $data['clientId']);
        $this->assertSame(['tm-1', 'tm-2'], $data['teamMembers']);
        $this->assertSame('Wireframes', $data['tasks'][0]['name']);
        $this->assertSame(55, $data['progress']);

        // Persisted to the snake_case column.
        $this->assertDatabaseHas('agency_projects', [
            'id' => $data['id'],
            'tenant_id' => $this->tenant->id,
            'client_id' => 'client-uuid-123',
            'name' => 'Portal',
        ]);
    }

    public function test_create_validates_required_name(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/agency/projects', ['budget' => 1000])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_show_update_delete(): void
    {
        $id = $this->withToken($this->token)
            ->postJson('/api/agency/projects', ['name' => 'P1', 'status' => 'pending'])
            ->json('data.id');

        $this->withToken($this->token)
            ->getJson("/api/agency/projects/{$id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'P1');

        // Partial update: only status; name must be preserved.
        $this->withToken($this->token)
            ->putJson("/api/agency/projects/{$id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.name', 'P1');

        $this->withToken($this->token)
            ->deleteJson("/api/agency/projects/{$id}")
            ->assertNoContent();

        $this->withToken($this->token)
            ->getJson("/api/agency/projects/{$id}")
            ->assertNotFound();
    }

    public function test_is_tenant_scoped(): void
    {
        // Create the "victim" row directly (currentTenant is the default tenant in
        // setUp) so the only HTTP request is the attacker's — switching tokens
        // mid-test would hit the auth guard's per-request user cache.
        $project = Project::create(['tenant_id' => $this->tenant->id, 'name' => 'Secret']);

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->getJson("/api/agency/projects/{$project->id}")
            ->assertNotFound();

        $this->withToken($otherToken)
            ->getJson('/api/agency/projects')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseHas('agency_projects', ['id' => $project->id, 'tenant_id' => $this->tenant->id]);
    }

    public function test_cannot_spoof_a_tenant_via_header(): void
    {
        // The panel's path: a token without a bound tenant_id + an X-Tenant-ID header.
        // A user may only resolve a tenant they actually belong to.
        $project = Project::create(['tenant_id' => $this->tenant->id, 'name' => 'Secret']);

        $outsider = User::factory()->create();
        $outsiderTenant = Tenant::factory()->pro()->create(['owner_id' => $outsider->id]);
        $outsiderTenant->users()->attach($outsider->id, ['role' => 'owner']);
        $outsider->syncRoles(['owner']);
        $token = $outsider->createToken('mobile')->plainTextToken; // no tenant_id on the token

        // Spoofing the default tenant (which they don't belong to) → 403.
        $this->withToken($token)
            ->withHeader('X-Tenant-ID', (string) $this->tenant->id)
            ->getJson("/api/agency/projects/{$project->id}")
            ->assertForbidden();
    }
}
