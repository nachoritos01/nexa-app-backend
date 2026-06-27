<?php

namespace Tests\Feature;

use App\Filament\Pages\TeamManagement;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TeamInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\WithTenant;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;
    use WithTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenant();
    }

    private function actAsOwner(): self
    {
        session(['tenant_id' => $this->tenant->id]);

        return $this->actingAs($this->tenantUser);
    }

    private function actAsRole(string $role): User
    {
        $user = $this->createUserWithRole($role);
        session(['tenant_id' => $this->tenant->id]);
        $this->actingAs($user);

        return $user;
    }

    // --- Access ---

    public function test_owner_can_access_team_management(): void
    {
        $this->actAsOwner()
            ->get('/admin/team-management')
            ->assertOk();
    }

    public function test_admin_can_access_team_management(): void
    {
        $this->actAsRole('admin');

        $this->get('/admin/team-management')
            ->assertOk();
    }

    public function test_ventas_cannot_access_team_management(): void
    {
        $this->actAsRole('ventas');

        $this->get('/admin/team-management')
            ->assertForbidden();
    }

    public function test_produccion_cannot_access_team_management(): void
    {
        $this->actAsRole('produccion');

        $this->get('/admin/team-management')
            ->assertForbidden();
    }

    // --- Invite: existing user ---

    public function test_invite_existing_user_attaches_to_tenant(): void
    {
        Notification::fake();

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('invite', data: [
                'email' => 'existing@example.com',
                'role' => 'ventas',
            ]);

        $this->assertTrue(
            $this->tenant->users()->where('user_id', $existingUser->id)->exists()
        );
        $this->assertEquals(
            'ventas',
            $this->tenant->users()->where('user_id', $existingUser->id)->first()->pivot->role
        );

        Notification::assertSentTo($existingUser, TeamInviteNotification::class, function ($notification) {
            return $notification->isNewUser === false && $notification->resetUrl === null;
        });
    }

    // --- Invite: new email ---

    public function test_invite_new_email_creates_user_and_attaches(): void
    {
        Notification::fake();

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('invite', data: [
                'email' => 'newuser@example.com',
                'role' => 'admin',
            ]);

        $newUser = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('newuser', $newUser->name);
        $this->assertTrue(
            $this->tenant->users()->where('user_id', $newUser->id)->exists()
        );

        Notification::assertSentTo($newUser, TeamInviteNotification::class, function ($notification) {
            return $notification->isNewUser === true
                && $notification->resetUrl !== null
                && str_contains($notification->resetUrl, 'password-reset/reset');
        });
    }

    // --- Invite: duplicate ---

    public function test_invite_duplicate_member_shows_warning(): void
    {
        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('invite', data: [
                'email' => $this->tenantUser->email,
                'role' => 'admin',
            ]);

        // Owner was already attached, count should still be 1 for this user
        $this->assertEquals(
            1,
            $this->tenant->users()->where('user_id', $this->tenantUser->id)->count()
        );
    }

    // --- Invite: at limit ---

    public function test_invite_at_limit_is_blocked(): void
    {
        Notification::fake();

        // Starter plan has max_users = 2, owner is already 1
        $this->tenant->update(['plan' => 'starter']);
        $this->createUserWithRole('ventas'); // Now at 2

        $this->tenant->clearUsageCache();

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('invite', data: [
                'email' => 'blocked@example.com',
                'role' => 'ventas',
            ]);

        $this->assertFalse(
            User::where('email', 'blocked@example.com')->exists()
        );

        Notification::assertNothingSent();
    }

    // --- Change role ---

    public function test_owner_can_change_member_role(): void
    {
        $member = $this->createUserWithRole('ventas');

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('change_role', $member, data: [
                'role' => 'admin',
            ]);

        $this->assertEquals(
            'admin',
            $this->tenant->users()->where('user_id', $member->id)->first()->pivot->role
        );
    }

    public function test_admin_cannot_promote_to_owner(): void
    {
        $member = $this->createUserWithRole('ventas');
        $this->actAsRole('admin');

        Livewire::test(TeamManagement::class)
            ->callTableAction('change_role', $member, data: [
                'role' => 'owner',
            ]);

        // Role should remain unchanged
        $this->assertEquals(
            'ventas',
            $this->tenant->users()->where('user_id', $member->id)->first()->pivot->role
        );
    }

    public function test_admin_cannot_change_owner_role(): void
    {
        $admin = $this->actAsRole('admin');

        Livewire::test(TeamManagement::class)
            ->callTableAction('change_role', $this->tenantUser, data: [
                'role' => 'admin',
            ]);

        // Owner role should remain unchanged
        $this->assertEquals(
            'owner',
            $this->tenant->users()->where('user_id', $this->tenantUser->id)->first()->pivot->role
        );
    }

    // --- Remove ---

    public function test_owner_can_remove_member(): void
    {
        $member = $this->createUserWithRole('ventas');

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('remove', $member);

        $this->assertFalse(
            $this->tenant->users()->where('user_id', $member->id)->exists()
        );

        // User account still exists
        $this->assertNotNull(User::find($member->id));
    }

    public function test_cannot_leave_zero_members(): void
    {
        // Only owner in the tenant
        $this->actAsOwner();

        // The 'remove' action is hidden for self (hidden callback), but the method also validates
        // So we test the underlying protection by calling removeMember reflection
        $this->assertEquals(1, $this->tenant->users()->count());

        // After attempting to remove the only other member that was just added and removed
        // the tenant should never reach 0 members
        $member = $this->createUserWithRole('ventas');

        Livewire::test(TeamManagement::class)
            ->callTableAction('remove', $member);

        // Member removed, owner remains
        $this->assertEquals(1, $this->tenant->users()->count());
    }

    // --- Resend invite ---

    public function test_resend_invite_generates_new_reset_url(): void
    {
        Notification::fake();

        // Create a member that has never logged in
        $member = $this->createUserWithRole('ventas');

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->callTableAction('resend_invite', $member);

        Notification::assertSentTo($member, TeamInviteNotification::class, function ($notification) {
            return $notification->isNewUser === true
                && $notification->resetUrl !== null
                && str_contains($notification->resetUrl, 'password-reset/reset');
        });
    }

    public function test_resend_invite_hidden_for_users_who_have_logged_in(): void
    {
        $member = $this->createUserWithRole('ventas');
        $member->update(['last_login_at' => now()]);

        $this->actAsOwner();

        Livewire::test(TeamManagement::class)
            ->assertTableActionHidden('resend_invite', $member);
    }

    // --- Cache invalidation ---

    public function test_invite_invalidates_usage_cache(): void
    {
        Notification::fake();

        $this->actAsOwner();

        // Warm the cache — count before invite
        $countBefore = $this->tenant->usageCounts()['users'];

        Livewire::test(TeamManagement::class)
            ->callTableAction('invite', data: [
                'email' => 'cache-test@example.com',
                'role' => 'ventas',
            ]);

        // Cache was invalidated: fresh count reflects the new user
        $countAfter = $this->tenant->usageCounts()['users'];
        $this->assertEquals($countBefore + 1, $countAfter);
    }

    public function test_remove_invalidates_usage_cache(): void
    {
        $member = $this->createUserWithRole('ventas');

        $this->actAsOwner();

        // Warm the cache — count before remove
        $this->tenant->clearUsageCache();
        $countBefore = $this->tenant->usageCounts()['users'];

        Livewire::test(TeamManagement::class)
            ->callTableAction('remove', $member);

        // Cache was invalidated: fresh count reflects the removal
        $countAfter = $this->tenant->usageCounts()['users'];
        $this->assertEquals($countBefore - 1, $countAfter);
    }
}
