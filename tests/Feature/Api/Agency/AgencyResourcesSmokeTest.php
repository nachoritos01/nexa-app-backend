<?php

namespace Tests\Feature\Api\Agency;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Smoke coverage for the remaining generic CRUD resources (Project/Client have
 * their own dedicated tests). Confirms each route + controller is wired and the
 * required-field validation fires.
 */
class AgencyResourcesSmokeTest extends AgencyTestCase
{
    /** @return array<string, array{0: string, 1: array<string,mixed>, 2: string}> */
    public static function resources(): array
    {
        return [
            // resource path, minimal-valid payload, the required field to omit for the 422 case
            'services' => ['services', ['name' => 'Web'], 'name'],
            'suppliers' => ['suppliers', ['name' => 'Adobe'], 'name'],
            'team' => ['team', ['name' => 'Sofía'], 'name'],
            'quotes' => ['quotes', ['number' => 'COT-1'], 'number'],
            'invoices' => ['invoices', ['number' => 'FAC-1'], 'number'],
            'expenses' => ['expenses', ['description' => 'Hosting'], 'description'],
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    #[DataProvider('resources')]
    public function test_resource_crud_and_validation(string $path, array $payload, string $required): void
    {
        // List starts empty.
        $this->withToken($this->token)
            ->getJson("/api/agency/{$path}")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Create returns 201 with a server-generated id.
        $id = $this->withToken($this->token)
            ->postJson("/api/agency/{$path}", $payload)
            ->assertCreated()
            ->json('data.id');
        $this->assertNotEmpty($id);

        // It now shows up in the list and by id.
        $this->withToken($this->token)
            ->getJson("/api/agency/{$path}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($this->token)
            ->getJson("/api/agency/{$path}/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id);

        // Delete → 204.
        $this->withToken($this->token)
            ->deleteJson("/api/agency/{$path}/{$id}")
            ->assertNoContent();

        // Missing the required field → 422.
        $this->withToken($this->token)
            ->postJson("/api/agency/{$path}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors($required);
    }
}
