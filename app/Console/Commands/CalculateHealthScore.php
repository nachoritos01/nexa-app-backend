<?php

namespace App\Console\Commands;

use App\Models\FeatureUsage;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CalculateHealthScore extends Command
{
    protected $signature = 'saas:health-score';

    protected $description = 'Calculate health score for all active tenants';

    public function handle(): int
    {
        $tenants = Tenant::where('is_active', true)->with('users')->get();

        $this->info("Calculating health scores for {$tenants->count()} active tenants...");

        $weights = config('saas.retention.health_score.weights');
        $lookback = config('saas.retention.health_score.lookback_days', 30);
        $activeUserDays = config('saas.retention.health_score.active_user_days', 7);
        $trackedFeatures = config('saas.retention.tracked_features', []);

        foreach ($tenants as $tenant) {
            $score = $this->calculateScore($tenant, $weights, $lookback, $activeUserDays, $trackedFeatures);

            $tenant->updateQuietly([
                'health_score' => $score,
                'health_score_calculated_at' => now(),
            ]);

            $this->line("  {$tenant->name}: {$score}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $weights
     * @param  list<string>  $trackedFeatures
     */
    private function calculateScore(Tenant $tenant, array $weights, int $lookback, int $activeUserDays, array $trackedFeatures): int
    {
        $loginScore = $this->loginRecencyScore($tenant, $lookback);
        $orderScore = $this->orderRecencyScore($tenant, $lookback);
        $featureScore = $this->featureAdoptionScore($tenant, $lookback, $trackedFeatures);
        $userScore = $this->userActivityScore($tenant, $activeUserDays);

        $total = ($loginScore * $weights['login_recency'] / 100)
            + ($orderScore * $weights['order_recency'] / 100)
            + ($featureScore * $weights['feature_adoption'] / 100)
            + ($userScore * $weights['user_activity'] / 100);

        return (int) round(min(100, max(0, $total)));
    }

    private function loginRecencyScore(Tenant $tenant, int $lookback): float
    {
        $lastLogin = $tenant->users
            ->whereNotNull('last_login_at')
            ->max('last_login_at');

        if (! $lastLogin) {
            return 0;
        }

        $daysAgo = (int) now()->diffInDays(Carbon::parse($lastLogin), absolute: true);

        if ($daysAgo >= $lookback) {
            return 0;
        }

        return 100 * (1 - $daysAgo / $lookback);
    }

    private function orderRecencyScore(Tenant $tenant, int $lookback): float
    {
        $lastOrder = Order::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->max('created_at');

        if (! $lastOrder) {
            return 0;
        }

        $daysAgo = (int) now()->diffInDays(Carbon::parse($lastOrder), absolute: true);

        if ($daysAgo >= $lookback) {
            return 0;
        }

        return 100 * (1 - $daysAgo / $lookback);
    }

    /**
     * @param  list<string>  $trackedFeatures
     */
    private function featureAdoptionScore(Tenant $tenant, int $lookback, array $trackedFeatures): float
    {
        if (empty($trackedFeatures)) {
            return 100;
        }

        $usedFeatures = FeatureUsage::where('tenant_id', $tenant->id)
            ->where('used_at', '>=', now()->subDays($lookback))
            ->distinct('feature')
            ->count('feature');

        return 100 * ($usedFeatures / count($trackedFeatures));
    }

    private function userActivityScore(Tenant $tenant, int $activeUserDays): float
    {
        $totalUsers = $tenant->users->count();

        if ($totalUsers === 0) {
            return 0;
        }

        $activeUsers = $tenant->users
            ->where('last_login_at', '>=', now()->subDays($activeUserDays))
            ->count();

        return 100 * ($activeUsers / $totalUsers);
    }
}
