<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MachineryOwner;
use App\Models\User; // أضفنا موديل المستخدم
use App\Http\Requests\MachineryOwner\StoreMachineryOwnerRequest;
use App\Http\Requests\MachineryOwner\UpdateMachineryOwnerRequest;
use App\Http\Resources\Api\MachineryOwnerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // مهم جداً للمعاملات
use Illuminate\Support\Facades\Hash; // لتشفير كلمة السر

class MachineryOwnerController extends Controller
{
   /**
     * عرض قائمة ملاك الآليات (مع دعم البحث بالاسم أو الهاتف)
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MachineryOwner::class);

        $query = MachineryOwner::query();

        // 1. تفعيل البحث (الاسم أو الهاتف)
        if ($request->filled('search')) {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'like', '%' . $searchTerm . '%');
            });
        }

        // 2. نظام الصلاحيات الحالي (لا تلمسه)
        if (auth()->user()->hasRole('Machinery Owner')) {
            $query->where('user_id', auth()->id());
        }

        // جلب البيانات مع الترقيم وتحميل علاقة المستخدم
        $owners = $query->with('user')->latest()->paginate(15);

        return MachineryOwnerResource::collection($owners);
    }

    public function store(StoreMachineryOwnerRequest $request): MachineryOwnerResource
    {
        $this->authorize('create', MachineryOwner::class);
        $validated = $request->validated();

        // نستخدم Transaction لضمان أنه إذا فشل إنشاء المالك لا يتم إنشاء المستخدم (والعكس)
        return DB::transaction(function () use ($request, $validated) {
            $userId = null;

            // 1. هل طلب المستخدم إنشاء حساب دخول؟
            if ($request->boolean('create_account')) {
                $user = User::create([
                    'full_name' => $validated['name'],
                    'username'  => $validated['username'],
                    'password'  => Hash::make($validated['password'] ?? $validated['phone']),
                ]);

                // إسناد الدور المخصص له (Spatie)
                $user->assignRole('Machinery Owner');
                $userId = $user->id;
            }

            // 2. معالجة رفع المستند
            $documentPath = null;
            if ($request->hasFile('document')) {
                $documentPath = $request->file('document')->store('machinery_owners/documents', 'public');
            }

            // 3. إنشاء سجل صاحب الآلية وربطه بالـ User ID (إن وجد)
            $owner = MachineryOwner::create([
                'user_id'        => $userId,
                'name'           => $validated['name'],
                'phone'          => $validated['phone'],
                'documents_path' => $documentPath,
            ]);

            return new MachineryOwnerResource($owner->load('user'));
        });
    }

    public function update(UpdateMachineryOwnerRequest $request, MachineryOwner $machineryOwner): MachineryOwnerResource
    {
        $this->authorize('update', $machineryOwner);
        $validated = $request->validated();

        return DB::transaction(function () use ($request, $validated, $machineryOwner) {

            // 1. تحديث بيانات المستخدم المرتبط (إذا كان موجوداً)
            if ($machineryOwner->user_id) {
                $user = $machineryOwner->user;
                $userUpdateData = [
                    'full_name' => $validated['name'] ?? $user->full_name,
                    'username'  => $validated['username'] ?? $user->username,
                ];

                if (!empty($validated['password'])) {
                    $userUpdateData['password'] = Hash::make($validated['password']);
                }

                $user->update($userUpdateData);
            }
            // 2. حالة خاصة: إذا لم يكن لديه حساب وقررنا إنشاء واحد الآن (Upgrade)
            elseif ($request->boolean('create_account')) {
                $user = User::create([
                    'full_name' => $validated['name'],
                    'username'  => $validated['username'],
                    'password'  => Hash::make($validated['password'] ?? $validated['phone']),
                ]);
                $user->assignRole('Machinery Owner');
                $machineryOwner->user_id = $user->id;
            }

            // 3. تحديث ملف المستند
            if ($request->hasFile('document')) {
                if ($machineryOwner->documents_path) {
                    Storage::disk('public')->delete($machineryOwner->documents_path);
                }
                $machineryOwner->documents_path = $request->file('document')->store('machinery_owners/documents', 'public');
            }

            // 4. تحديث بقية البيانات
            $machineryOwner->fill([
                'name'  => $validated['name'] ?? $machineryOwner->name,
                'phone' => $validated['phone'] ?? $machineryOwner->phone,
            ]);

            $machineryOwner->save();

            return new MachineryOwnerResource($machineryOwner->load('user'));
        });
    }

    public function destroy(MachineryOwner $machineryOwner): JsonResponse
    {
        $this->authorize('delete', $machineryOwner);

        // ملاحظة: قد ترغب أيضاً في تعطيل حساب المستخدم المرتبط عند حذف المالك
        $machineryOwner->delete();

        return response()->json(['message' => 'تم حذف صاحب الآلية بنجاح'], 200);
    }
}
