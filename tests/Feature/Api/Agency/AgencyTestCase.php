<?php

namespace Tests\Feature\Api\Agency;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shared setup for the agency API tests: an authenticated owner with a
 * tenant-scoped Sanctum token. Requests use $this->withToken($this->token).
 */
abstract class AgencyTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $token;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        [$this->user, $this->tenant, $this->token] = $this->makeTenantUser();
        app()->instance('currentTenant', $this->tenant);
    }

    /**
     * Create a user + pro tenant + a Sanctum token scoped to that tenant.
     *
     * @return array{0: User, 1: Tenant, 2: string}
     */
    protected function makeTenantUser(): array
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->pro()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->syncRoles(['owner']);

        $sanctumToken = $user->createToken('Test');
        $sanctumToken->accessToken->update(['tenant_id' => $tenant->id]);

        return [$user, $tenant, $sanctumToken->plainTextToken];
    }
}
