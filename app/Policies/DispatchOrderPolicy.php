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
        // 1. إذا كان المستخدم "صاحب آلية" (المنطق القديم مع تحسين بسيط)
        if ($user->hasRole('Machinery Owner')) {
            // يتحقق ما إذا كان لديه أي رحلة (Trip) داخل هذا الأمر تخص آلياته
            return $dispatchOrder->trips()->whereHas('machinery', function ($q) use ($user) {
                $q->where('owner_id', $user->machineryOwner?->id);
            })->exists();
        }

        // 2. [الإضافة الجديدة] إذا كان المستخدم "مورد" (Supplier)
        if ($user->hasRole('Supplier')) {
            // المورد يرى الإذن فقط إذا كان هو المورد المسجل في رأس الطلب
            // نفترض أن علاقة supplier موجودة في موديل User أو نجلبها عبر ID
            $supplier = \App\Models\Supplier::where('user_id', $user->id)->first();
            return $supplier && $dispatchOrder->supplier_id === $supplier->id;
        }

        // لبقية الأدوار الإدارية (مدير، مشرف، محاسب)
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
