<?php

namespace App\Events;

use App\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $previousPlan,
        public string $newPlan,
    ) {
    }
}
