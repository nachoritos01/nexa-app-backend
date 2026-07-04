<?php

namespace App\Models\Agency;

use App\Models\Agency\Concerns\LogsAgencyActivity;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToTenant;
    use LogsAgencyActivity;
    use HasUuids;

    protected $table = 'agency_suppliers';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'contact_name',
        'category',
        'address',
        'website',
        'tax_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
