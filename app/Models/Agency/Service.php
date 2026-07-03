<?php

namespace App\Models\Agency;

use App\Models\Agency\Concerns\LogsAgencyActivity;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use BelongsToTenant;
    use LogsAgencyActivity;
    use HasUuids;

    protected $table = 'agency_services';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'base_price',
        'estimated_hours',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'float',
        'estimated_hours' => 'integer',
        'is_active' => 'boolean',
    ];
}
