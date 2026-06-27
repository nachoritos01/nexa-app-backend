<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

trait WithApiToken
{
    protected function createApiToken(User $user, Tenant $tenant, string $name = 'Test'): NewAccessToken
    {
        $token = $user->createToken($name);
        $token->accessToken->update(['tenant_id' => $tenant->id]);

        return $token;
    }
}
