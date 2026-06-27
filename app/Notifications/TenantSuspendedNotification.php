<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantSuspendedNotification extends Notification
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your account has been suspended')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your account has been suspended because the trial period and grace period have ended.')
            ->line('Your data is safe. Subscribe to reactivate your account and regain access.')
            ->action('View plans', url('/suspended'))
            ->line('If you need help, reply to this email.');
    }
}
