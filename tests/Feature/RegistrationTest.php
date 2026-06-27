<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_page_is_accessible(): void
    {
        $this->get('/admin/register')
            ->assertOk();
    }

    public function test_registration_creates_user_and_tenant(): void
    {
        Livewire::test(\App\Filament\Pages\Auth\Register::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'business_name' => 'My Test Business',
                'phone' => '3312345678',
                'city' => 'Guadalajara',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('register')
            ->assertRedirect('/admin/onboarding');

        // User created
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);

        // Tenant created
        $tenant = Tenant::where('slug', 'my-test-business')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('My Test Business', $tenant->name);
        $this->assertEquals('starter', $tenant->plan);
        $this->assertEquals($user->id, $tenant->owner_id);
        $this->assertTrue($tenant->is_active);
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertNull($tenant->onboarding_completed_at);

        // Trial is 14 days
        $this->assertTrue($tenant->trial_ends_at->isAfter(now()->addDays(13)));
        $this->assertTrue($tenant->trial_ends_at->isBefore(now()->addDays(15)));

        // Settings stored
        $this->assertEquals('3312345678', $tenant->settings['phone']);
        $this->assertEquals('Guadalajara', $tenant->settings['city']);

        // Pivot and role
        $this->assertTrue($tenant->users()->where('user_id', $user->id)->exists());
        $this->assertEquals('owner', $tenant->users()->first()->pivot->role);
        $this->assertTrue($user->fresh()->hasRole('owner'));
    }

    public function test_registration_generates_unique_slug(): void
    {
        // Create first tenant with slug 'mi-imprenta'
        Tenant::factory()->create(['slug' => 'mi-imprenta']);

        Livewire::test(\App\Filament\Pages\Auth\Register::class)
            ->fillForm([
                'name' => 'Another User',
                'email' => 'another@example.com',
                'business_name' => 'Mi Imprenta',
                'phone' => '3300000000',
                'city' => 'CDMX',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('register')
            ->assertRedirect('/admin/onboarding');

        // Should get a different slug
        $tenants = Tenant::where('slug', 'like', 'mi-imprenta%')->get();
        $this->assertCount(2, $tenants);
        $this->assertTrue($tenants->pluck('slug')->contains('mi-imprenta'));
        $this->assertTrue($tenants->pluck('slug')->contains('mi-imprenta-1'));
    }

    public function test_registration_requires_all_fields(): void
    {
        Livewire::test(\App\Filament\Pages\Auth\Register::class)
            ->fillForm([
                'name' => '',
                'email' => '',
                'business_name' => '',
                'phone' => '',
                'city' => '',
                'password' => '',
                'passwordConfirmation' => '',
            ])
            ->call('register')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'required',
                'business_name' => 'required',
                'phone' => 'required',
                'city' => 'required',
                'password' => 'required',
            ]);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(\App\Filament\Pages\Auth\Register::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'existing@example.com',
                'business_name' => 'Test Business',
                'phone' => '3311111111',
                'city' => 'GDL',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('register')
            ->assertHasFormErrors(['email' => 'unique']);
    }
}
