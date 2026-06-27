<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $tenantName,
        public string $role,
        public bool $isNewUser = false,
        public ?string $resetUrl = null,
    ) {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabels = [
            'owner' => 'Owner',
            'admin' => 'Administrator',
            'ventas' => 'Sales',
            'produccion' => 'Production',
            'contabilidad' => 'Accounting',
        ];

        $roleLabel = $roleLabels[$this->role] ?? $this->role;

        $message = (new MailMessage())
            ->subject("You have been invited to the {$this->tenantName} team")
            ->greeting("Hi {$notifiable->name},");

        if ($this->isNewUser) {
            $message->line("An account has been created for you at **{$this->tenantName}** with the role of **{$roleLabel}**.")
                ->line('Click the button to set your password and access the dashboard.');

            if ($this->resetUrl) {
                $message->action('Set password', $this->resetUrl);
            } else {
                $message->action('Log in', url('/admin/login'));
            }
        } else {
            $message->line("You have been added to the **{$this->tenantName}** team with the role of **{$roleLabel}**.")
                ->action('Go to dashboard', url('/admin'));
        }

        return $message->line('If you have any questions, contact your team administrator.');
    }
}
