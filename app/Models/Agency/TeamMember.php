<?php

namespace App\Models\Agency;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'agency_team_members';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'role',
        'department',
        'salary',
        'avatar',
        'is_active',
    ];

    protected $casts = [
        'salary' => 'float',
        'is_active' => 'boolean',
    ];
}
