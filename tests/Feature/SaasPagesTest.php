<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SaasPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_landing_page_is_accessible(): void
    {
        $this->get('/')
            ->assertOk();
    }

    public function test_pricing_page_is_accessible(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Growth')
            ->assertSee('Pro');
    }

    public function test_pricing_page_shows_plan_features(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('orders/month')
            ->assertSee('users');
    }

    public function test_registration_page_accepts_plan_query_param(): void
    {
        $this->get('/admin/register?plan=growth')
            ->assertOk();
    }

    public function test_registration_with_plan_preselection(): void
    {
        Livewire::test(\App\Filament\Pages\Auth\Register::class)
            ->set('plan', 'growth')
            ->fillForm([
                'name' => 'Plan Test User',
                'email' => 'plantest@example.com',
                'business_name' => 'Plan Test Biz',
                'phone' => '3399998888',
                'city' => 'CDMX',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ])
            ->call('register')
            ->assertRedirect('/admin/onboarding');

        $tenant = Tenant::where('slug', 'plan-test-biz')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('growth', $tenant->plan);
    }
}
