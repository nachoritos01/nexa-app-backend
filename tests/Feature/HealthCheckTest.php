<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_returns_200_when_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson(['status' => 'healthy']);
    }

    public function test_health_check_returns_correct_json_structure(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'services' => ['database', 'cache', 'storage'],
                'timestamp',
            ]);
    }

    public function test_health_check_services_are_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson([
                'services' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                    'storage' => 'ok',
                ],
            ]);
    }

    public function test_health_check_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
    }
}
