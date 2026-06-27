<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class EncryptTenantSettings extends Command
{
    protected $signature = 'saas:encrypt-settings';

    protected $description = 'Encrypt sensitive tenant settings (idempotent — skips already encrypted values)';

    public function handle(): int
    {
        $sensitiveKeys = Tenant::getSensitiveSettingsKeys();
        $tenants = Tenant::all();
        $encrypted = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $settings = $tenant->settings ?? [];
            $changed = false;

            foreach ($sensitiveKeys as $key) {
                if (! isset($settings[$key]) || $settings[$key] === '') {
                    continue;
                }

                // Check if already encrypted
                try {
                    Crypt::decryptString($settings[$key]);
                    $skipped++;

                    continue; // Already encrypted
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    // Plain text — encrypt it
                }

                $settings[$key] = Crypt::encryptString($settings[$key]);
                $changed = true;
                $encrypted++;
            }

            if ($changed) {
                $tenant->updateQuietly(['settings' => $settings]);
            }
        }

        $this->info("Encrypted {$encrypted} values, skipped {$skipped} (already encrypted).");

        return self::SUCCESS;
    }
}
