<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createUserWithTenant(string $role = 'owner', string $plan = 'pro'): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $tenant = Tenant::factory()->create([
            'plan' => $plan,
            'owner_id' => $user->id,
        ]);
        $tenant->users()->attach($user->id, ['role' => $role]);
        $user->syncRoles([$role]);

        return $user;
    }

    // Login tests

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = $this->createUserWithTenant();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'tenants',
                'token',
                'token_type',
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $user = $this->createUserWithTenant();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_login_with_nonexistent_email_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = $this->createUserWithTenant();

        // 5 attempts/min allowed; the 6th for the same IP+email is throttled.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_returns_tenants_with_role(): void
    {
        $user = $this->createUserWithTenant('admin');

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'tenants')
            ->assertJsonPath('tenants.0.role', 'admin');
    }

    public function test_login_returns_role_and_agency_permissions(): void
    {
        $user = $this->createUserWithTenant('owner');

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'owner');

        $permissions = $response->json('user.permissions');
        $this->assertContains('agency.view', $permissions);
        $this->assertContains('agency.manage', $permissions);
        // Only agency.* is exposed to the panel — never the e-commerce permissions.
        $this->assertNotContains('orders.view', $permissions);
    }

    public function test_login_view_only_role_has_no_manage_permission(): void
    {
        // 'ventas' is seeded with agency.view but not agency.manage.
        $user = $this->createUserWithTenant('ventas');

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonPath('user.role', 'ventas');

        $permissions = $response->json('user.permissions');
        $this->assertContains('agency.view', $permissions);
        $this->assertNotContains('agency.manage', $permissions);
    }

    public function test_login_updates_last_login_at(): void
    {
        $user = $this->createUserWithTenant();
        $this->assertNull($user->last_login_at);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    // Me tests

    public function test_me_returns_user_and_tenants(): void
    {
        $user = $this->createUserWithTenant();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role', 'permissions'],
                'tenants',
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'owner');
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    // Logout tests

    public function test_logout_revokes_current_token(): void
    {
        $user = $this->createUserWithTenant();
        $sanctumToken = $user->createToken('test');
        $token = $sanctumToken->plainTextToken;

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $sanctumToken->accessToken->id,
        ]);

        $response = $this->withToken($token)
            ->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $sanctumToken->accessToken->id,
        ]);
    }

    // Push token tests

    public function test_push_token_stores_on_user(): void
    {
        $this->markTestSkipped('push-token (mobile-only) retirado — ver docs/auditoria-y-plan-api-agency.md');

        $user = $this->createUserWithTenant();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/push-token', [
                'push_token' => 'ExponentPushToken[abc123]',
            ]);

        $response->assertOk();

        $user->refresh();
        $this->assertEquals('ExponentPushToken[abc123]', $user->push_token);
    }

    public function test_push_token_validates_required(): void
    {
        $this->markTestSkipped('push-token (mobile-only) retirado — ver docs/auditoria-y-plan-api-agency.md');

        $user = $this->createUserWithTenant();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/auth/push-token', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['push_token']);
    }
}
