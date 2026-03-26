<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User; // أضفنا موديل المستخدم
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\Api\SupplierResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB; // للمعاملات
use Illuminate\Support\Facades\Hash; // لتشفير كلمة المرور

class SupplierController extends Controller
{
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Supplier::class);

        $query = Supplier::query();

        // تفعيل منطق البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'like', '%' . $searchTerm . '%');
            });
        }

        // نظام الصلاحيات: إذا كان المستخدم مورد، يرى نفسه فقط
        if (auth()->user()->hasRole('Supplier')) {
            $query->where('user_id', auth()->id());
        }

        // جلب الموردين مع علاقة المستخدم
        $suppliers = $query->with('user')->latest()->paginate(15);

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $this->authorize('create', Supplier::class);
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated) {
            $userId = null;

            // هل طلب إنشاء حساب دخول للمورد؟
            if ($request->boolean('create_account')) {
                $user = User::create([
                    'full_name' => $validated['name'],
                    'username'  => $validated['username'],
                    'password'  => Hash::make($validated['password'] ?? $validated['phone']),
                ]);

                // إسناد الدور المخصص له
                $user->assignRole('Supplier');
                $userId = $user->id;
            }

            // إنشاء المورد
            $supplier = Supplier::create([
                'user_id'         => $userId,
                'name'            => $validated['name'],
                'phone'           => $validated['phone'],
                'current_balance' => $validated['current_balance'] ?? 0,
            ]);

            return new SupplierResource($supplier->load('user'));
        });
    }

    public function show(Supplier $supplier): SupplierResource
    {
        $this->authorize('view', $supplier);

        $supplier->load(['user', 'dispatchOrders', 'financialTransactions']);

        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $this->authorize('update', $supplier);
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated, $supplier) {

            // 1. تحديث بيانات المستخدم المرتبط (إذا كان موجوداً)
            if ($supplier->user_id) {
                $user = $supplier->user;
                $userUpdateData = [
                    'full_name' => $validated['name'] ?? $user->full_name,
                    'username'  => $validated['username'] ?? $user->username,
                ];

                if (!empty($validated['password'])) {
                    $userUpdateData['password'] = Hash::make($validated['password']);
                }

                $user->update($userUpdateData);
            }
            // 2. حالة الترقية: لم يكن لديه حساب وقررنا إنشاء واحد له الآن
            elseif ($request->boolean('create_account')) {
                $user = User::create([
                    'full_name' => $validated['name'],
                    'username'  => $validated['username'],
                    'password'  => Hash::make($validated['password'] ?? $validated['phone']),
                ]);
                $user->assignRole('Supplier');
                $supplier->user_id = $user->id;
            }

            // 3. تحديث بيانات المورد
            $supplier->fill([
                'name'  => $validated['name'] ?? $supplier->name,
                'phone' => $validated['phone'] ?? $supplier->phone,
            ]);

            $supplier->save();

            return new SupplierResource($supplier->load('user'));
        });
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $supplier->delete(); // الحذف المرن

        return response()->json([
            'message' => 'تم حذف المورد بنجاح'
        ], 200);
    }
}
