<?php

namespace App\Providers;

// --- نماذج وسياسات المستخدمين والأدوار ---
use App\Models\User;
use App\Policies\UserPolicy;
use Spatie\Permission\Models\Role;
use App\Policies\RolePolicy;

// --- النماذج والسياسات الجديدة للمشروع ---
use App\Models\Supplier;
use App\Policies\SupplierPolicy;
use App\Models\MachineryOwner;
use App\Policies\MachineryOwnerPolicy;
use App\Models\Driver;
use App\Policies\DriverPolicy;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Models\Treasury;
use App\Policies\TreasuryPolicy;
use App\Models\Machinery;
use App\Policies\MachineryPolicy;
use App\Models\DispatchOrder;
use App\Policies\DispatchOrderPolicy;
use App\Models\FinancialTransaction;
use App\Policies\FinancialTransactionPolicy;
use App\Models\Message;
use App\Policies\MessagePolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // سياسات إدارة المستخدمين
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,

        // سياسات الكيانات الخاصة بمنظومة حركة الآليات
        Supplier::class => SupplierPolicy::class,
        MachineryOwner::class => MachineryOwnerPolicy::class,
        Driver::class => DriverPolicy::class,
        Project::class => ProjectPolicy::class,
        Treasury::class => TreasuryPolicy::class,
        Machinery::class => MachineryPolicy::class,
        DispatchOrder::class => DispatchOrderPolicy::class,
        FinancialTransaction::class => FinancialTransactionPolicy::class,
        Message::class             => MessagePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // هذا الكود يمنح الـ Super Admin صلاحية كاملة على كل شيء
        // يجب أن يأتي بعد registerPolicies
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
