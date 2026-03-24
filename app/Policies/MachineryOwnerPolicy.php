<?php

namespace App\Policies;

use App\Models\MachineryOwner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MachineryOwnerPolicy
{
    /**
     * هل يسمح للمستخدم برؤية القائمة الكاملة؟
     * صاحب الآلية لا يجب أن يرى قائمة "كل الملاك"، لذا نمنعه هنا.
     */
    public function viewAny(User $user): bool
    {
        // نمنع المالك من رؤية القائمة الكاملة للملاك الآخرين
        if ($user->hasRole('Machinery Owner')) {
            return false;
        }

        return $user->can('machinery_owner.view');
    }

    /**
     * عرض تفاصيل (أو تقرير) مالك محدد
     */
    public function view(User $user, MachineryOwner $machineryOwner): bool
    {
        // 1. السماح لمدير النظام دائماً
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // 2. إذا كان المستخدم هو "صاحب الآلية"، نتحقق من ملكيته للملف
        if ($user->hasRole('Machinery Owner')) {
            return $user->id === $machineryOwner->user_id;
        }

        // 3. لبقية الأدوار (أدمن، محاسب)، نتحقق من الصلاحية
        return $user->can('machinery_owner.view');
    }

    public function create(User $user): bool
    {
        // المالك لا يمكنه إنشاء ملاك آخرين
        if ($user->hasRole('Machinery Owner')) {
            return false;
        }
        return $user->can('machinery_owner.create');
    }

    public function update(User $user, MachineryOwner $machineryOwner): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // ملاحظة: عادة لا نسمح للمالك بتعديل بياناته بنفسه (مثل الاسم أو الرصيد)
        // لضمان دقة المحاسبة، التعديل يتم فقط عبر الإدارة.
        if ($user->hasRole('Machinery Owner')) {
            return false;
        }

        return $user->can('machinery_owner.update');
    }

    public function delete(User $user, MachineryOwner $machineryOwner): bool
    {
        if ($user->hasRole('Machinery Owner')) {
            return false;
        }
        return $user->can('machinery_owner.delete');
    }
}
