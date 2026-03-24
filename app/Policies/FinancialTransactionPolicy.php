<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\MachineryOwner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FinancialTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('financial_transaction.view');
    }

    public function view(User $user, FinancialTransaction $financialTransaction): bool
    {
        // إذا كان المستخدم صاحب آلية، يجب ألا يرى إلا كشف حسابه (المعاملات المرتبطة به شخصياً)
        if ($user->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', $user->id)->first();

            return $owner &&
                   $financialTransaction->related_entity_type === MachineryOwner::class &&
                   $financialTransaction->related_entity_id === $owner->id;
        }

        // لبقية الأدوار المصرّح لها (مثل المحاسب والمدير)
        return $user->can('financial_transaction.view');
    }

    public function create(User $user): bool
    {
        return $user->can('financial_transaction.create');
    }

    public function update(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('financial_transaction.update');
    }

    public function delete(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('financial_transaction.delete');
    }

    public function restore(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('financial_transaction.delete');
    }

    public function forceDelete(User $user, FinancialTransaction $financialTransaction): bool
    {
        return $user->can('financial_transaction.delete');
    }
}
