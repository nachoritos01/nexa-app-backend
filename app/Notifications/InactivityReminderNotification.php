<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InactivityReminderNotification extends Notification
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
        return (new MailMessage())
            ->subject('We miss you')
            ->greeting("Hi {$this->tenant->name}!")
            ->line('We noticed you have not created any orders on the platform for a while.')
            ->line('Here are some ideas to get back on track:')
            ->line('- Review your products and update prices')
            ->line('- Add new clients to your directory')
            ->line('- Create a test order to get reacquainted')
            ->action('Go to dashboard', url('/admin'))
            ->line('If you need help, do not hesitate to contact us.');
    }
}
