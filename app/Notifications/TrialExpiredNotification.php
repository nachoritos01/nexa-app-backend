<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredNotification extends Notification
{
    use Queueable;

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $graceDays = config('saas.trial.grace_days', 3);

        return (new MailMessage())
            ->subject('Your free trial has ended')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your free trial has ended.')
            ->line("You have **{$graceDays} grace days** to subscribe before your account is suspended.")
            ->action('Subscribe now', url('/admin/billing'))
            ->line('Your data will be safe, but you will not be able to access it until you subscribe.');
    }
}
