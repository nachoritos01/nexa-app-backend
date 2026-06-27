<?php

namespace App\Console\Commands;

use App\Models\CancellationSurvey;
use App\Models\Tenant;
use App\Notifications\WinbackNotification;
use Illuminate\Console\Command;

class SendWinbackEmails extends Command
{
    protected $signature = 'saas:winback-emails';

    protected $description = 'Send win-back emails to tenants who cancelled N days ago';

    public function handle(): int
    {
        $days = config('saas.retention.winback.days_after_cancellation', 7);
        $targetDate = now()->subDays($days);

        $surveys = CancellationSurvey::whereDate('created_at', $targetDate->toDateString())
            ->get();

        $sent = 0;

        foreach ($surveys as $survey) {
            /** @var Tenant|null $tenant */
            $tenant = $survey->tenant;

            if (! $tenant) {
                continue;
            }

            // Skip if tenant re-subscribed
            if ($tenant->subscribed_at !== null) {
                continue;
            }

            // Dedup: skip if already sent
            $settings = $tenant->settings ?? [];
            if (! empty($settings['winback_email_sent_at'])) {
                continue;
            }

            /** @var \App\Models\User|null $owner */
            $owner = $tenant->owner;

            if (! $owner) {
                continue;
            }

            $owner->notify(new WinbackNotification($tenant));

            $settings['winback_email_sent_at'] = now()->toISOString();
            $tenant->updateQuietly(['settings' => $settings]);
            $sent++;
        }

        $this->info("Win-back emails sent: {$sent}");

        return self::SUCCESS;
    }
}
