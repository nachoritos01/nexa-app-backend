<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EncryptedSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->tenant = Tenant::factory()->create(['owner_id' => $user->id]);
    }

    public function test_set_secure_setting_encrypts_value(): void
    {
        $this->tenant->setSecureSetting('webhook_secret', 'my-secret-key');

        $this->tenant->refresh();
        $raw = $this->tenant->settings['webhook_secret'];

        // Raw value should not be plain text
        $this->assertNotEquals('my-secret-key', $raw);

        // But should be decryptable
        $this->assertEquals('my-secret-key', Crypt::decryptString($raw));
    }

    public function test_get_secure_setting_decrypts_correctly(): void
    {
        $this->tenant->setSecureSetting('webhook_secret', 'test-secret-123');

        $this->tenant->refresh();
        $decrypted = $this->tenant->getSecureSetting('webhook_secret');

        $this->assertEquals('test-secret-123', $decrypted);
    }

    public function test_legacy_plain_value_readable_without_error(): void
    {
        // Simulate legacy plain-text value
        $settings = $this->tenant->settings ?? [];
        $settings['webhook_secret'] = 'plain-text-legacy-secret';
        $this->tenant->updateQuietly(['settings' => $settings]);

        $this->tenant->refresh();
        $value = $this->tenant->getSecureSetting('webhook_secret');

        $this->assertEquals('plain-text-legacy-secret', $value);
    }

    public function test_encrypt_command_migrates_existing_values(): void
    {
        // Set plain-text secret
        $settings = $this->tenant->settings ?? [];
        $settings['webhook_secret'] = 'plain-secret';
        $this->tenant->updateQuietly(['settings' => $settings]);

        $this->artisan('saas:encrypt-settings')
            ->assertSuccessful()
            ->expectsOutputToContain('Encrypted 1');

        $this->tenant->refresh();

        // Should now be encrypted in DB
        $raw = $this->tenant->settings['webhook_secret'];
        $this->assertNotEquals('plain-secret', $raw);

        // But readable via accessor
        $this->assertEquals('plain-secret', $this->tenant->getSecureSetting('webhook_secret'));

        // Running again should skip (idempotent)
        $this->artisan('saas:encrypt-settings')
            ->assertSuccessful()
            ->expectsOutputToContain('skipped 1');
    }
}
