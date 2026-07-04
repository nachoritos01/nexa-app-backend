<?php

namespace App\Models\Agency;

use App\Models\Agency\Concerns\LogsAgencyActivity;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use BelongsToTenant;
    use LogsAgencyActivity;
    use HasUuids;

    protected $table = 'agency_quotes';

    protected $fillable = [
        'tenant_id',
        'number',
        'client_id',
        'date',
        'valid_until',
        'status',
        'items',
        'discount',
        'tax',
        'subtotal',
        'total',
        'terms',
        'notes',
    ];

    protected $casts = [
        'items' => 'array',
        'discount' => 'float',
        'tax' => 'float',
        'subtotal' => 'float',
        'total' => 'float',
    ];
}
