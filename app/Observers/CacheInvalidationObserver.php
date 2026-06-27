<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationObserver
{
    public function created(Model $model): void
    {
        $this->invalidateTenantCache($model);
    }

    public function updated(Model $model): void
    {
        $this->invalidateTenantCache($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidateTenantCache($model);
    }

    private function invalidateTenantCache(Model $model): void
    {
        $tenantId = $model->getAttribute('tenant_id');

        if (! $tenantId) {
            return;
        }

        Cache::forget("tenant:{$tenantId}:usage_counts");
        Cache::forget("tenant:{$tenantId}:dashboard_stats");
    }
}
