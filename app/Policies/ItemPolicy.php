<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.manage');
    }

    public function view(User $user, Item $item): bool
    {
        return $user->can('products.manage');
    }

    public function create(User $user): bool
    {
        if (currentTenant()?->isAtLimit('items')) {
            return false;
        }

        return $user->can('products.manage');
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('products.manage');
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('products.manage');
    }
}
