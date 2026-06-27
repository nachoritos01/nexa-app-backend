<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WinbackNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Tenant $tenant,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $promotionUrl = config('saas.retention.winback.promotion_url')
            ?? url('/admin/register');

        return (new MailMessage())
            ->subject('Come back — 1 free month')
            ->greeting("Hi {$this->tenant->name}!")
            ->line('We know you cancelled your subscription, and we would like you to give us another chance.')
            ->line('We have been working on improvements and want to offer you 1 free month to see for yourself.')
            ->action('Claim my free month', $promotionUrl)
            ->line('If you have questions or need help, do not hesitate to contact us.');
    }
}
