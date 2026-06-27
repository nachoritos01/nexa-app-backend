<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'event',
        'url',
        'payload',
        'status_code',
        'response',
        'attempt',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_code' => 'integer',
        'attempt' => 'integer',
    ];
}
