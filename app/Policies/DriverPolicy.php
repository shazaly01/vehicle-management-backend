<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('driver.view');
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->can('driver.view');
    }

    public function create(User $user): bool
    {
        return $user->can('driver.create');
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->can('driver.update');
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->can('driver.delete');
    }

    public function restore(User $user, Driver $driver): bool
    {
        return $user->can('driver.delete');
    }

    public function forceDelete(User $user, Driver $driver): bool
    {
        return $user->can('driver.delete');
    }
}
