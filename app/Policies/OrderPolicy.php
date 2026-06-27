<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.view');
    }

    public function create(User $user): bool
    {
        if (currentTenant()?->isAtLimit('orders')) {
            return false;
        }

        return $user->can('orders.create');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.edit');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->can('orders.delete');
    }
}
