<?php

namespace App\Models\Agency;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'agency_expenses';

    protected $fillable = [
        'tenant_id',
        'description',
        'amount',
        'category',
        'date',
        'status',
        'supplier_id',
        'project_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
