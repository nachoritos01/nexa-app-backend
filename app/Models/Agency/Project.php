<?php

namespace App\Models\Agency;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use BelongsToTenant;
    use HasUuids;

    protected $table = 'agency_projects';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'client_id',
        'type',
        'status',
        'priority',
        'budget',
        'start_date',
        'end_date',
        'team_members',
        'tasks',
        'progress',
        'comments',
        'files',
    ];

    protected $casts = [
        'budget' => 'float',
        'progress' => 'integer',
        'team_members' => 'array',
        'tasks' => 'array',
        'comments' => 'array',
        'files' => 'array',
    ];
}
