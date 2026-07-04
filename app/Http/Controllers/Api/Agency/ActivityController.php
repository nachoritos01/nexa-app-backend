<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\Agency\ActivityResource;
use App\Models\Agency\Client;
use App\Models\Agency\Expense;
use App\Models\Agency\Invoice;
use App\Models\Agency\Project;
use App\Models\Agency\Quote;
use App\Models\Agency\Service;
use App\Models\Agency\Supplier;
use App\Models\Agency\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only audit trail for the agency module. Returns spatie activity_log rows
 * scoped to the current tenant (stamped by LogsAgencyActivity). Reuses spatie's
 * activitylog — no custom logging logic here.
 */
class ActivityController extends Controller
{
    /**
     * Whitelist mapping the public subjectType slug → agency model FQCN. The client
     * never sends a class name; we only ever query these known models.
     *
     * @var array<string, class-string>
     */
    private const SUBJECT_TYPES = [
        'client' => Client::class,
        'project' => Project::class,
        'quote' => Quote::class,
        'invoice' => Invoice::class,
        'expense' => Expense::class,
        'service' => Service::class,
        'supplier' => Supplier::class,
        'team' => TeamMember::class,
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'subjectType' => ['sometimes', 'string', 'in:'.implode(',', array_keys(self::SUBJECT_TYPES))],
            'subjectId' => ['sometimes', 'string'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $tenant = currentTenant();
        abort_if($tenant === null, 403, 'No tenant in context.');

        $query = Activity::query()
            ->where('log_name', 'agency')
            ->where('tenant_id', $tenant->id)
            ->with('causer')
            ->orderByDesc('id');

        if (isset($validated['subjectType'])) {
            $query->where('subject_type', self::SUBJECT_TYPES[$validated['subjectType']]);
        }

        if (isset($validated['subjectId'])) {
            $query->where('subject_id', $validated['subjectId']);
        }

        return ActivityResource::collection(
            $query->paginate($validated['perPage'] ?? 20)
        );
    }
}
