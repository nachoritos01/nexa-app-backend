<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, Location $location): bool
    {
        return $user->can('settings.manage');
    }

    public function create(User $user): bool
    {
        if (currentTenant()?->isAtLimit('locations')) {
            return false;
        }

        return $user->can('settings.manage');
    }

    public function update(User $user, Location $location): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, Location $location): bool
    {
        return $user->can('settings.manage');
    }
}
