<?php

namespace App\Policies;

use App\Models\DispatchOrder;
use App\Models\MachineryOwner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DispatchOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dispatch_order.view');
    }

    public function view(User $user, DispatchOrder $dispatchOrder): bool
    {
        // إذا كان المستخدم صاحب آلية، نتحقق من أن هذا الإذن يخص إحدى آلياته
        if ($user->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', $user->id)->first();
            // نتحقق من وجود المالك، وأن الآلية المذكورة في الإذن تعود ملكيتها له
            return $owner && $owner->id === $dispatchOrder->machinery->owner_id;
        }

        // لبقية الأدوار (مدير، مشرف، محاسب)
        return $user->can('dispatch_order.view');
    }

    public function create(User $user): bool
    {
        return $user->can('dispatch_order.create');
    }

    public function update(User $user, DispatchOrder $dispatchOrder): bool
    {
        return $user->can('dispatch_order.update');
    }

    public function delete(User $user, DispatchOrder $dispatchOrder): bool
    {
        return $user->can('dispatch_order.delete');
    }

    public function restore(User $user, DispatchOrder $dispatchOrder): bool
    {
        return $user->can('dispatch_order.delete');
    }

    public function forceDelete(User $user, DispatchOrder $dispatchOrder): bool
    {
        return $user->can('dispatch_order.delete');
    }
}
