<?php

namespace App\Policies;

use App\Models\Machinery;
use App\Models\MachineryOwner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MachineryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('machinery.view');
    }

    public function view(User $user, Machinery $machinery): bool
    {
        // إذا كان المستخدم صاحب آلية، نتحقق من ملكيته لهذه الآلية المحددة
        if ($user->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', $user->id)->first();
            return $owner && $owner->id === $machinery->owner_id;
        }

        // لبقية الأدوار (مدير، مشرف، إلخ)
        return $user->can('machinery.view');
    }

    public function create(User $user): bool
    {
        return $user->can('machinery.create');
    }

    public function update(User $user, Machinery $machinery): bool
    {
        return $user->can('machinery.update');
    }

    public function delete(User $user, Machinery $machinery): bool
    {
        return $user->can('machinery.delete');
    }

    public function restore(User $user, Machinery $machinery): bool
    {
        return $user->can('machinery.delete');
    }

    public function forceDelete(User $user, Machinery $machinery): bool
    {
        return $user->can('machinery.delete');
    }
}
