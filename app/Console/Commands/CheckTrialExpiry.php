<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Notifications\TenantSuspendedNotification;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialExpiringNotification;
use Illuminate\Console\Command;

class CheckTrialExpiry extends Command
{
    protected $signature = 'saas:check-trial-expiry';

    protected $description = 'Check trial expiry, send notifications, and suspend expired tenants';

    public function handle(): int
    {
        $this->sendExpiringNotifications();
        $this->sendExpiredNotifications();
        $this->suspendExpiredTenants();

        return self::SUCCESS;
    }

    private function sendExpiringNotifications(): void
    {
        // Query tenants expiring in exactly 1 or 3 days (date-based)
        $tenants = Tenant::query()
            ->with('owner')
            ->whereNotNull('trial_ends_at')
            ->whereNull('subscribed_at')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereDate('trial_ends_at', today()->addDays(3))
                    ->orWhereDate('trial_ends_at', today()->addDay());
            })
            ->get();

        foreach ($tenants as $tenant) {
            $days = (int) today()->diffInDays($tenant->trial_ends_at->startOfDay(), false);

            $owner = $tenant->owner;
            if (! $owner) {
                continue;
            }

            $owner->notify(new TrialExpiringNotification($days));
            $this->info("Expiring notification sent to {$owner->email} ({$days}d remaining)");
        }
    }

    private function sendExpiredNotifications(): void
    {
        $tenants = Tenant::query()
            ->with('owner')
            ->whereNotNull('trial_ends_at')
            ->whereNull('subscribed_at')
            ->where('is_active', true)
            ->whereDate('trial_ends_at', today())
            ->get();

        foreach ($tenants as $tenant) {
            $owner = $tenant->owner;
            if (! $owner) {
                continue;
            }

            $owner->notify(new TrialExpiredNotification());
            $this->info("Expired notification sent to {$owner->email}");
        }
    }

    private function suspendExpiredTenants(): void
    {
        $graceDays = config('saas.trial.grace_days', 3);

        $tenants = Tenant::query()
            ->with('owner')
            ->whereNotNull('trial_ends_at')
            ->whereNull('subscribed_at')
            ->where('is_active', true)
            ->where('trial_ends_at', '<', now()->subDays($graceDays))
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->suspend();

            $owner = $tenant->owner;
            if ($owner) {
                $owner->notify(new TenantSuspendedNotification());
                $this->info("Tenant '{$tenant->name}' suspended, notification sent to {$owner->email}");
            } else {
                $this->info("Tenant '{$tenant->name}' suspended (no owner)");
            }
        }
    }
}
