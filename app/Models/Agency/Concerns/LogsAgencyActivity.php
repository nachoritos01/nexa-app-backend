<?php

namespace App\Models\Agency\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Adds spatie activity logging to an agency model. Logs the business (fillable)
 * fields only, dirty-only, under the `agency` log name, and stamps the record's
 * tenant_id on every activity so the /api/agency/activity endpoint can filter
 * safely per tenant.
 *
 * Heavy JSON columns (items, tasks, comments, files, team_members) are excluded
 * to keep the log readable — the interesting change is the status/total/etc., not
 * a full line-item dump.
 */
trait LogsAgencyActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['tenant_id', 'items', 'tasks', 'comments', 'files', 'team_members'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('agency');
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        // Mirrors App\Models\Concerns\LogsActivityWithTenant: the subject carries
        // tenant_id (BelongsToTenant), with currentTenant() as a fallback.
        $activity->setAttribute('tenant_id', $this->getAttribute('tenant_id') ?? currentTenant()?->id);
    }
}
