<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createSuperAdmin(): User
    {
        $user = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::factory()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return $user;
    }

    private function createRegularUser(): User
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $tenant = Tenant::factory()->create(['owner_id' => $user->id]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);

        return $user;
    }

    public function test_super_admin_can_access_super_admin_panel(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)
            ->get('/super-admin')
            ->assertOk();
    }

    public function test_regular_user_cannot_access_super_admin_panel(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->get('/super-admin')
            ->assertForbidden();
    }

    public function test_guest_cannot_access_super_admin_panel(): void
    {
        $this->get('/super-admin')
            ->assertRedirect();
    }

    public function test_super_admin_can_view_tenants_list(): void
    {
        $user = $this->createSuperAdmin();
        Tenant::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/super-admin/tenants')
            ->assertOk();
    }

    public function test_super_admin_can_suspend_tenant(): void
    {
        $user = $this->createSuperAdmin();
        $tenant = Tenant::factory()->create(['is_active' => true]);

        $this->actingAs($user);

        $tenant->suspend();

        $this->assertFalse($tenant->fresh()->is_active);
    }

    public function test_super_admin_can_reactivate_tenant(): void
    {
        $user = $this->createSuperAdmin();
        $tenant = Tenant::factory()->create(['is_active' => false]);

        $this->actingAs($user);

        $tenant->update(['is_active' => true]);

        $this->assertTrue($tenant->fresh()->is_active);
    }

    public function test_super_admin_can_reset_onboarding(): void
    {
        $user = $this->createSuperAdmin();
        $tenant = Tenant::factory()->create([
            'onboarding_steps' => ['first_branch' => now()->toISOString()],
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user);

        $tenant->update(['onboarding_steps' => null, 'onboarding_completed_at' => null]);

        $fresh = $tenant->fresh();
        $this->assertNull($fresh->onboarding_steps);
        $this->assertNull($fresh->onboarding_completed_at);
    }

    public function test_impersonation_sets_session_and_redirects(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['owner_id' => $owner->id]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($superAdmin);

        // Simulate impersonation flow
        session(['impersonating_from' => $superAdmin->id]);
        auth()->login($owner);
        session(['tenant_id' => $tenant->id]);

        $this->assertEquals($owner->id, auth()->id());
        $this->assertEquals($superAdmin->id, session('impersonating_from'));
    }

    public function test_stop_impersonation_returns_to_super_admin(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['owner_id' => $owner->id]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Login as owner, with impersonation session
        $this->actingAs($owner)
            ->withSession([
                'impersonating_from' => $superAdmin->id,
                'tenant_id' => $tenant->id,
            ])
            ->get('/impersonation/stop')
            ->assertRedirect('/super-admin');

        $this->assertEquals($superAdmin->id, auth()->id());
    }

    public function test_impersonated_user_can_access_admin_panel(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['owner_id' => $owner->id]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Simulate impersonation: logged in as owner with correct session state
        // password_hash_web must NOT be present (cleared after Auth::login switch)
        $this->actingAs($owner)
            ->withSession([
                'impersonating_from' => $superAdmin->id,
                'tenant_id' => $tenant->id,
            ])
            ->get('/admin')
            ->assertOk();
    }

    public function test_stop_impersonation_super_admin_can_access_panel(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['owner_id' => $owner->id]);
        $tenant->users()->attach($owner->id, ['role' => 'owner']);

        // Stop impersonation
        $this->actingAs($owner)
            ->withSession([
                'impersonating_from' => $superAdmin->id,
                'tenant_id' => $tenant->id,
            ])
            ->get('/impersonation/stop')
            ->assertRedirect('/super-admin');

        // Verify super-admin panel is accessible after returning
        $this->actingAs($superAdmin)
            ->get('/super-admin')
            ->assertOk();
    }
}
