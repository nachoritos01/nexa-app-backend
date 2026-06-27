<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Tenant;
use Illuminate\Support\Str;

class WebhookService
{
    public function dispatch(Tenant $tenant, string $event, array $data): void
    {
        $settings = $tenant->settings ?? [];

        if (empty($settings['webhook_enabled'])) {
            return;
        }

        if (empty($settings['webhook_url'])) {
            return;
        }

        $subscribedEvents = $settings['webhook_events'] ?? [];

        if (! in_array($event, $subscribedEvents)) {
            return;
        }

        $payload = [
            'id' => 'wh_' . Str::random(20),
            'event' => $event,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ];

        SendWebhookJob::dispatch(
            $tenant->id,
            $settings['webhook_url'],
            $tenant->getSecureSetting('webhook_secret') ?? '',
            $event,
            $payload,
        );
    }
}
