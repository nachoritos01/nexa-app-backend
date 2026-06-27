<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use App\Notifications\LowHealthScoreAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendRetentionAlerts extends Command
{
    protected $signature = 'saas:retention-alerts';

    protected $description = 'Send proactive retention alerts (inactivity reminders + low health alerts)';

    public function handle(): int
    {
        $this->sendInactivityReminders();
        $this->sendLowHealthScoreAlerts();

        return self::SUCCESS;
    }

    private function sendInactivityReminders(): void
    {
        $days = config('saas.retention.inactivity_reminder_days', 14);

        $tenants = Tenant::where('is_active', true)
            ->with('owner')
            ->whereNotNull('subscribed_at')
            ->get();

        $sent = 0;

        foreach ($tenants as $tenant) {
            // Check dedup: max 1 reminder per 14 days
            $settings = $tenant->settings ?? [];
            $lastSent = $settings['inactivity_reminder_sent_at'] ?? null;

            if ($lastSent && now()->diffInDays(Carbon::parse($lastSent), absolute: true) < $days) {
                continue;
            }

            // Check if tenant has recent orders
            $lastOrder = Order::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->max('created_at');

            if ($lastOrder && now()->diffInDays(Carbon::parse($lastOrder), absolute: true) < $days) {
                continue;
            }

            $owner = $tenant->owner;

            if (! $owner) {
                continue;
            }

            $owner->notify(new InactivityReminderNotification($tenant));

            $settings['inactivity_reminder_sent_at'] = now()->toISOString();
            $tenant->updateQuietly(['settings' => $settings]);
            $sent++;
        }

        $this->info("Inactivity reminders sent: {$sent}");
    }

    private function sendLowHealthScoreAlerts(): void
    {
        $threshold = config('saas.retention.low_health_threshold', 50);

        $lowHealthTenants = Tenant::where('is_active', true)
            ->whereNotNull('health_score')
            ->where('health_score', '<', $threshold)
            ->get();

        if ($lowHealthTenants->isEmpty()) {
            $this->info('No low health score tenants found.');

            return;
        }

        $superAdmins = User::where('is_super_admin', true)->get();

        if ($superAdmins->isEmpty()) {
            $this->warn('No super-admins found to notify.');

            return;
        }

        foreach ($superAdmins as $admin) {
            $admin->notify(new LowHealthScoreAlert($lowHealthTenants));
        }

        $this->info("Low health alert sent to {$superAdmins->count()} super-admin(s) for {$lowHealthTenants->count()} tenant(s).");
    }
}
