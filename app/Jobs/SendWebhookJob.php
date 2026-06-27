<?php

namespace App\Jobs;

use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $tenantId,
        public string $url,
        public string $secret,
        public string $event,
        public array $payload,
    ) {
    }

    public function handle(): void
    {
        $body = json_encode($this->payload);
        $signature = hash_hmac('sha256', $body, $this->secret);

        $statusCode = null;
        $responseBody = null;

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->event,
                ])
                ->withBody($body, 'application/json')
                ->post($this->url);

            $statusCode = $response->status();
            $responseBody = substr($response->body(), 0, 2000);

            if ($response->failed()) {
                Log::warning('Webhook delivery failed', [
                    'tenant_id' => $this->tenantId,
                    'event' => $this->event,
                    'status' => $statusCode,
                ]);
            }
        } catch (\Exception $e) {
            $responseBody = $e->getMessage();

            Log::error('Webhook delivery error', [
                'tenant_id' => $this->tenantId,
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);

            $this->logAttempt($statusCode, $responseBody);

            throw $e;
        }

        $this->logAttempt($statusCode, $responseBody);
    }

    private function logAttempt(?int $statusCode, ?string $response): void
    {
        WebhookLog::create([
            'tenant_id' => $this->tenantId,
            'event' => $this->event,
            'url' => $this->url,
            'payload' => $this->payload,
            'status_code' => $statusCode,
            'response' => $response,
            'attempt' => $this->attempts(),
        ]);
    }
}
