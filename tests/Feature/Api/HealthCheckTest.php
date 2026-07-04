<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_api_health_check(): void
    {
        $this->markTestSkipped('API pública/V1 retirada — ver docs/auditoria-y-plan-api-agency.md');

        $response = $this->getJson('/api');

        $response->assertOk()
            ->assertJsonStructure(['name', 'version', 'status', 'timestamp'])
            ->assertJson(['status' => 'ok']);
    }
}
