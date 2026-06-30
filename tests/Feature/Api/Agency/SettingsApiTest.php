<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Agency\AgencySetting;

class SettingsApiTest extends AgencyTestCase
{
    public function test_requires_authentication(): void
    {
        $this->getJson('/api/agency/settings')->assertUnauthorized();
    }

    public function test_get_returns_empty_when_unset(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/agency/settings')
            ->assertOk()
            ->assertJsonMissingPath('data.companyName');
    }

    public function test_put_merges_and_persists(): void
    {
        $this->withToken($this->token)
            ->putJson('/api/agency/settings', ['companyName' => 'NexaDigital'])
            ->assertOk()
            ->assertJsonPath('data.companyName', 'NexaDigital');

        // A second PUT merges (doesn't replace) the existing data.
        $this->withToken($this->token)
            ->putJson('/api/agency/settings', ['theme' => 'dark'])
            ->assertOk()
            ->assertJsonPath('data.companyName', 'NexaDigital')
            ->assertJsonPath('data.theme', 'dark');

        $this->withToken($this->token)
            ->getJson('/api/agency/settings')
            ->assertOk()
            ->assertJsonPath('data.companyName', 'NexaDigital')
            ->assertJsonPath('data.theme', 'dark');

        // Singleton: only one row per tenant.
        $this->assertDatabaseCount('agency_settings', 1);
    }

    public function test_is_tenant_scoped(): void
    {
        AgencySetting::create([
            'tenant_id' => $this->tenant->id,
            'data' => ['companyName' => 'Tenant A Co'],
        ]);

        [, , $otherToken] = $this->makeTenantUser();

        $this->withToken($otherToken)
            ->getJson('/api/agency/settings')
            ->assertOk()
            ->assertJsonMissingPath('data.companyName');
    }
}
