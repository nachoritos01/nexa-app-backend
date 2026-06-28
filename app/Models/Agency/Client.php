<?php

namespace App\Models\Agency;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'agency_clients';

    protected $fillable = [
        'tenant_id',
        'name',
        'contact_name',
        'phone',
        'email',
        'website',
        'industry',
        'type',
        'origin',
        'status',
        'pipeline_stage',
        'notes',
        'rating',
        'potential_value',
        'logo',
        'address',
        'tax_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'potential_value' => 'float',
    ];
}
