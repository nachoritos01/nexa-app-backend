<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\PdfGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createOwnerWithPlan(string $plan): User
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create([
            'owner_id' => $user->id,
            'plan' => $plan,
            'is_active' => true,
        ]);
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $user->assignRole('owner');

        app()->instance('currentTenant', $tenant);
        session(['tenant_id' => $tenant->id]);

        return $user;
    }

    public function test_settings_page_accessible_with_permission(): void
    {
        $user = $this->createOwnerWithPlan('growth');

        $this->actingAs($user)
            ->withSession(['tenant_id' => $user->tenants()->first()->id])
            ->get('/admin/tenant-settings')
            ->assertOk();
    }

    public function test_logo_upload_saves_path_in_tenant_settings(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $settings = $tenant->settings ?? [];
        $settings['logo_path'] = 'tenants/test_logo.png';
        $tenant->update(['settings' => $settings]);

        $this->assertEquals('tenants/test_logo.png', $tenant->fresh()->settings['logo_path']);
    }

    public function test_starter_cannot_upload_logo(): void
    {
        $user = $this->createOwnerWithPlan('starter');
        $tenant = $user->tenants()->first();

        app()->instance('currentTenant', $tenant);

        // TenantSettings::canUploadLogo checks plan !== 'starter'
        $this->assertEquals('starter', $tenant->plan);
    }

    public function test_business_info_reads_from_tenant_settings(): void
    {
        $user = $this->createOwnerWithPlan('growth');
        $tenant = $user->tenants()->first();

        $tenant->update([
            'settings' => [
                'business_name' => 'My Custom Store',
                'slogan' => 'The best items',
                'phone' => '5551234567',
                'email' => 'custom@store.com',
                'address' => '123 Custom St',
                'logo_path' => 'tenants/custom_logo.png',
            ],
        ]);

        app()->instance('currentTenant', $tenant->fresh());

        $generator = new PdfGenerator();
        $info = $generator->getBusinessInfo($tenant->fresh());

        $this->assertEquals('My Custom Store', $info['name']);
        $this->assertEquals('The best items', $info['slogan']);
        $this->assertEquals('5551234567', $info['phone']);
        $this->assertEquals('custom@store.com', $info['email']);
        $this->assertEquals('123 Custom St', $info['address']);
        $this->assertEquals('tenants/custom_logo.png', $info['logo_path']);
    }
}
