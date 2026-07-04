<?php

namespace Tests\Feature\Api\Agency;

/**
 * Covers the `api-tenant` throttle limiter that guards the /api/agency/* group
 * (AppServiceProvider::boot). Repointed from the retired /api/v1 surface — see
 * docs/auditoria-y-plan-api-agency.md.
 */
class RateLimitTest extends AgencyTestCase
{
    public function test_pro_plan_has_300_limit(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/agency/clients');

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit', 300);
    }

    public function test_rate_limit_headers_present(): void
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/agency/clients');

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    public function test_exceeding_limit_returns_429(): void
    {
        config(['saas.api.rate_limits.pro' => 2]);

        $this->withToken($this->token)->getJson('/api/agency/clients')->assertOk();
        $this->withToken($this->token)->getJson('/api/agency/clients')->assertOk();

        $this->withToken($this->token)
            ->getJson('/api/agency/clients')
            ->assertStatus(429);
    }
}
