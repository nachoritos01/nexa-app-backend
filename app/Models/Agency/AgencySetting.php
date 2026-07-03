<?php

namespace App\Models\Agency;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AgencySetting extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'agency_settings';

    protected $fillable = [
        'tenant_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
