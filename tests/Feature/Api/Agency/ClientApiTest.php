<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Agency\Client;

class ClientApiTest extends AgencyTestCase
{
    public function test_requires_authentication(): void
    {
        $this->getJson('/api/agency/clients')->assertUnauthorized();
    }

    public function test_create_maps_spanish_fields_to_columns(): void
    {
        $payload = [
            'name' => 'ACME Corp',
            'contactName' => 'Juan Pérez',
            'telefono' => '+52 55 0000',
            'email' => 'juan@acme.mx',
            'industria' => 'Tecnología',
            'estado' => 'Activo',
            'etapaPipeline' => 'Ganado',
            'origen' => 'Directo',
            'valorPotencial' => 150000,
        ];

        $data = $this->withToken($this->token)
            ->postJson('/api/agency/clients', $payload)
            ->assertCreated()
            ->json('data');

        $this->assertSame('ACME Corp', $data['name']);
        $this->assertSame('Juan Pérez', $data['contactName']);
        $this->assertSame('Activo', $data['estado']);
        $this->assertArrayHasKey('fechaCreacion', $data);
        $this->assertArrayHasKey('fechaActualizacion', $data);

        // camelCase/Spanish payload → snake_case columns.
        $this->assertDatabaseHas('agency_clients', [
            'id' => $data['id'],
            'tenant_id' => $this->tenant->id,
            'name' => 'ACME Corp',
            'contact_name' => 'Juan Pérez',
            'phone' => '+52 55 0000',
            'tax_id' => null,
        ]);
    }

    public function test_create_validates_required_name(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/agency/clients', ['email' => 'x@y.mx'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_and_delete(): void
    {
        $id = $this->withToken($this->token)
            ->postJson('/api/agency/clients', ['name' => 'Cli', 'estado' => 'Prospecto'])
            ->json('data.id');

        $this->withToken($this->token)
            ->putJson("/api/agency/clients/{$id}", ['estado' => 'Activo'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'Activo')
            ->assertJsonPath('data.name', 'Cli');

        $this->withToken($this->token)
            ->deleteJson("/api/agency/clients/{$id}")
            ->assertNoContent();
    }

    public function test_is_tenant_scoped(): void
    {
        $client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Secret',
            'status' => 'Activo',
            'pipeline_stage' => 'Lead',
        ]);

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->getJson("/api/agency/clients/{$client->id}")
            ->assertNotFound();
    }
}
