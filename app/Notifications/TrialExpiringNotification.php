<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $daysRemaining,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dayLabel = $this->daysRemaining === 1 ? 'day' : 'days';

        return (new MailMessage())
            ->subject("Your free trial ends in {$this->daysRemaining} {$dayLabel}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your free trial ends in **{$this->daysRemaining} {$dayLabel}**.")
            ->line('Subscribe now to keep access to your data and continue using the platform.')
            ->action('Subscribe', url('/admin/billing'))
            ->line('If you have any questions, reply to this email and we will help you.');
    }
}
