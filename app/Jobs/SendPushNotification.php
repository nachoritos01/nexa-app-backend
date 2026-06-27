<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  list<string>  $pushTokens  Expo push tokens
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array<string, mixed>  $data  Custom data (type, order_id, etc.)
     */
    public function __construct(
        private array $pushTokens,
        private string $title,
        private string $body,
        private array $data = [],
    ) {
    }

    public function handle(): void
    {
        $tokens = array_filter($this->pushTokens, fn (string $token) => str_starts_with($token, 'ExponentPushToken['));

        if (empty($tokens)) {
            return;
        }

        $messages = array_map(fn (string $token) => [
            'to' => $token,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'sound' => 'default',
            'priority' => 'high',
        ], $tokens);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $messages);

        if ($response->failed()) {
            Log::warning('Push notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'tokens_count' => count($tokens),
            ]);
        }
    }
}
