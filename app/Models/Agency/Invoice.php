<?php

namespace App\Models\Agency;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'agency_invoices';

    protected $fillable = [
        'tenant_id',
        'number',
        'client_id',
        'project_id',
        'quote_id',
        'date',
        'due_date',
        'status',
        'items',
        'subtotal',
        'tax',
        'total',
        'notes',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'float',
        'tax' => 'float',
        'total' => 'float',
    ];
}
