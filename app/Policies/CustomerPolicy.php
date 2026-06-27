<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.manage');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('customers.manage');
    }

    public function create(User $user): bool
    {
        if (currentTenant()?->isAtLimit('customers')) {
            return false;
        }

        return $user->can('customers.manage');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.manage');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.manage');
    }
}
