<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Machinery;
use App\Models\MachineryOwner;
use App\Http\Requests\Machinery\StoreMachineryRequest;
use App\Http\Requests\Machinery\UpdateMachineryRequest;
use App\Http\Resources\Api\MachineryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MachineryController extends Controller
{
    /**
     * عرض قائمة الآليات
     */
   /**
     * عرض قائمة الآليات (المعدات) مع دعم البحث والفلترة
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Machinery::class);

        $query = Machinery::query();

        // 1. البحث النصي (رقم اللوحة أو الاسم)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('plate_number_or_name', 'like', '%' . $searchTerm . '%');
        }

        // 2. الفلترة بصاحب الآلية (owner_id)
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        // 3. الفلترة بحالة الآلية (available, busy, maintenance, etc.)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // --- نظام الصلاحيات الحالي للملاك ---
        if (auth()->user()->hasRole('Machinery Owner')) {
            $owner = \App\Models\MachineryOwner::where('user_id', auth()->id())->first();
            if ($owner) {
                // المالك يرى فقط معداته، والبحث يتم داخل نطاق معداته فقط
                $query->where('owner_id', $owner->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        // ------------------------------------

        $machineries = $query->with('owner')->latest()->paginate(15);

        return MachineryResource::collection($machineries);
    }

    /**
     * إضافة آلية جديدة
     */
    public function store(StoreMachineryRequest $request): MachineryResource
    {
        $this->authorize('create', Machinery::class);

        $machinery = Machinery::create($request->validated());

        return new MachineryResource($machinery);
    }

    /**
     * عرض بيانات آلية محددة
     */
    public function show(Machinery $machinery): MachineryResource
    {
        $this->authorize('view', $machinery);

        $machinery->load('owner');

        return new MachineryResource($machinery);
    }

    /**
     * تحديث بيانات آلية
     */
    public function update(UpdateMachineryRequest $request, Machinery $machinery): MachineryResource
    {
        $this->authorize('update', $machinery);

        $machinery->update($request->validated());

        return new MachineryResource($machinery);
    }

    /**
     * حذف آلية
     */
    public function destroy(Machinery $machinery): JsonResponse
    {
        $this->authorize('delete', $machinery);

        $machinery->delete();

        return response()->json([
            'message' => 'تم حذف الآلية بنجاح'
        ], 200);
    }
}
