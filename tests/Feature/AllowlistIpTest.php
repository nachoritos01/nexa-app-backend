<?php

namespace Tests\Feature;

use App\Http\Middleware\AllowlistIp;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AllowlistIpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(AllowlistIp::class)->get('_test/guarded', fn () => 'ok');
    }

    private function getFromIp(string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->get('_test/guarded');
    }

    public function test_empty_allowlist_allows_any_ip(): void
    {
        config(['security.admin_ip_allowlist' => '']);

        $this->getFromIp('198.51.100.23')->assertOk()->assertSee('ok');
    }

    public function test_ip_not_in_allowlist_gets_404(): void
    {
        config(['security.admin_ip_allowlist' => '203.0.113.4, 203.0.113.5']);

        $this->getFromIp('198.51.100.23')->assertNotFound();
    }

    public function test_exact_ip_in_allowlist_is_allowed(): void
    {
        config(['security.admin_ip_allowlist' => '203.0.113.4, 198.51.100.23']);

        $this->getFromIp('198.51.100.23')->assertOk();
    }

    public function test_cidr_range_in_allowlist_is_allowed(): void
    {
        config(['security.admin_ip_allowlist' => '10.8.0.0/24']);

        $this->getFromIp('10.8.0.57')->assertOk();
        $this->getFromIp('10.9.0.1')->assertNotFound();
    }
}
