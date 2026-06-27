<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowHealthScoreAlert extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, \App\Models\Tenant>  $tenants
     */
    public function __construct(
        private Collection $tenants,
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
        $message = (new MailMessage())
            ->subject('Alerta: tenants con health score critico')
            ->greeting('Alerta de retencion')
            ->line("Hay {$this->tenants->count()} tenant(s) con health score bajo:");

        foreach ($this->tenants as $tenant) {
            $message->line("- {$tenant->name}: {$tenant->health_score} ({$tenant->healthLabel()})");
        }

        return $message
            ->action('Ver en Super Admin', url('/super-admin/tenants'))
            ->line('Considera contactarlos para ofrecer soporte.');
    }
}
