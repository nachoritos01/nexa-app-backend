<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class WebhookSettings extends Page
{
    protected static ?string $title = 'Webhooks';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-top-right-on-square';

    protected static ?string $navigationLabel = 'Webhooks';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.webhook-settings';

    public string $webhookUrl = '';

    public string $webhookSecret = '';

    public bool $webhookEnabled = false;

    /** @var list<string> */
    public array $webhookEvents = [];

    public static function canAccess(): bool
    {
        if (! hasModule('api')) {
            return false;
        }

        $user = auth()->user();

        if (! $user?->can('settings.manage')) {
            return false;
        }

        $tenant = currentTenant();

        return $tenant && $tenant->plan === 'pro';
    }

    public function mount(): void
    {
        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        $settings = $tenant->settings ?? [];
        $this->webhookUrl = $settings['webhook_url'] ?? '';
        $this->webhookSecret = $tenant->getSecureSetting('webhook_secret') ?? '';
        $this->webhookEnabled = ! empty($settings['webhook_enabled']);
        $this->webhookEvents = $settings['webhook_events'] ?? [];

        if (empty($this->webhookSecret)) {
            $this->webhookSecret = Str::random(32);
        }
    }

    public function getEventOptions(): array
    {
        return [
            'order.created' => 'Order created',
            'order.status_changed' => 'Order status changed',
            'payment.received' => 'Payment received',
        ];
    }

    public function save(): void
    {
        $this->validate([
            'webhookUrl' => 'required_if:webhookEnabled,true|nullable|url|max:2048',
        ]);

        $tenant = currentTenant();

        if (! $tenant) {
            return;
        }

        $settings = $tenant->settings ?? [];
        $settings['webhook_url'] = $this->webhookUrl;
        $settings['webhook_enabled'] = $this->webhookEnabled;
        $settings['webhook_events'] = $this->webhookEvents;
        $tenant->update(['settings' => $settings]);

        // Store secret encrypted separately
        $tenant->setSecureSetting('webhook_secret', $this->webhookSecret);

        Notification::make()
            ->title('Webhook settings saved')
            ->success()
            ->send();
    }

    public function regenerateSecret(): void
    {
        $this->webhookSecret = Str::random(32);

        Notification::make()
            ->title('New secret generated')
            ->body('Remember to save to apply the change.')
            ->warning()
            ->send();
    }

    public function testWebhook(): void
    {
        if (empty($this->webhookUrl)) {
            Notification::make()
                ->title('Configure a URL first')
                ->danger()
                ->send();

            return;
        }

        $payload = json_encode([
            'id' => 'wh_test_' . Str::random(12),
            'event' => 'test',
            'created_at' => now()->toIso8601String(),
            'data' => ['message' => 'This is a test webhook.'],
        ]);

        $signature = hash_hmac('sha256', $payload, $this->webhookSecret);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => 'test',
                ])
                ->withBody($payload, 'application/json')
                ->post($this->webhookUrl);

            Notification::make()
                ->title("Test enviado — HTTP {$response->status()}")
                ->color($response->successful() ? 'success' : 'warning')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error sending test')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
