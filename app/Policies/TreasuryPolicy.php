<?php

namespace App\Policies;

use App\Models\Treasury;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TreasuryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treasury.view');
    }

    public function view(User $user, Treasury $treasury): bool
    {
        return $user->can('treasury.view');
    }

    public function create(User $user): bool
    {
        return $user->can('treasury.create');
    }

    public function update(User $user, Treasury $treasury): bool
    {
        return $user->can('treasury.update');
    }

    public function delete(User $user, Treasury $treasury): bool
    {
        return $user->can('treasury.delete');
    }

    public function restore(User $user, Treasury $treasury): bool
    {
        return $user->can('treasury.delete');
    }

    public function forceDelete(User $user, Treasury $treasury): bool
    {
        return $user->can('treasury.delete');
    }
}
