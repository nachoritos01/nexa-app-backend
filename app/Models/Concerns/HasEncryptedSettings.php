<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Crypt;

trait HasEncryptedSettings
{
    /** @var list<string> */
    protected static array $sensitiveSettingsKeys = [
        'webhook_secret',
        'payment_gateway_key',
        'payment_gateway_secret',
    ];

    public function getSecureSetting(string $key): ?string
    {
        $settings = $this->settings ?? [];
        $value = $settings[$key] ?? null;

        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Legacy plain-text value — return as-is
            return $value;
        }
    }

    public function setSecureSetting(string $key, ?string $value): void
    {
        $settings = $this->settings ?? [];

        if ($value === null) {
            unset($settings[$key]);
        } else {
            $settings[$key] = Crypt::encryptString($value);
        }

        $this->update(['settings' => $settings]);
    }

    public static function getSensitiveSettingsKeys(): array
    {
        return static::$sensitiveSettingsKeys;
    }
}
