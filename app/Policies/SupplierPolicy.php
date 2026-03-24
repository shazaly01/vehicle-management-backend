<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('supplier.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.view');
    }

    public function create(User $user): bool
    {
        return $user->can('supplier.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.update');
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.delete');
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.delete');
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $user->can('supplier.delete');
    }
}
