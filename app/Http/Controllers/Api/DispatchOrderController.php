<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DispatchOrder;
use App\Models\MachineryOwner;
use App\Http\Requests\DispatchOrder\StoreDispatchOrderRequest;
use App\Http\Requests\DispatchOrder\UpdateDispatchOrderRequest;
use App\Http\Resources\Api\DispatchOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DispatchOrderController extends Controller
{
   /**
     * عرض قائمة أذونات الخروج (مع دعم الفلترة والبحث)
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DispatchOrder::class);

        $query = DispatchOrder::query();

        // 1. فلترة البحث برقم الإذن المرجعي (order_no)
        if ($request->filled('search')) {
            $query->where('order_no', 'like', '%' . $request->search . '%');
        }

        // 2. فلترة بالمشروع المحدد
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // 3. فلترة بحالة الإذن (pending, active, etc.)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // --- نظام الصلاحيات الحالي (لا تغيره) ---
        if (auth()->user()->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', auth()->id())->first();
            if ($owner) {
                $query->whereHas('machinery', function ($q) use ($owner) {
                    $q->where('owner_id', $owner->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        // ----------------------------------------

        // تحميل العلاقات وجلب البيانات مع الترقيم
        $orders = $query->with(['machinery.owner', 'driver', 'supplier', 'project'])
                        ->latest()
                        ->paginate(15);

        return DispatchOrderResource::collection($orders);
    }

    /**
     * إنشاء إذن خروج جديد
     */
    public function store(StoreDispatchOrderRequest $request): DispatchOrderResource
    {
        $this->authorize('create', DispatchOrder::class);

        $dispatchOrder = DispatchOrder::create($request->validated());

        // يمكن هنا إضافة أحداث (Events) مستقبلاً، مثلاً لتحديث حالة الآلية إلى "مشغولة"

        return new DispatchOrderResource($dispatchOrder);
    }

    /**
     * عرض تفاصيل إذن خروج محدد
     */
    public function show(DispatchOrder $dispatchOrder): DispatchOrderResource
    {
        $this->authorize('view', $dispatchOrder);

        $dispatchOrder->load(['machinery.owner', 'driver', 'supplier', 'project']);

        return new DispatchOrderResource($dispatchOrder);
    }

    /**
     * تحديث إذن خروج
     */
    public function update(UpdateDispatchOrderRequest $request, DispatchOrder $dispatchOrder): DispatchOrderResource
    {
        $this->authorize('update', $dispatchOrder);

        $dispatchOrder->update($request->validated());

        // يمكن هنا التحقق مما إذا كانت الحالة أصبحت "مكتملة" لتحديث حالة الآلية إلى "متاحة"

        return new DispatchOrderResource($dispatchOrder);
    }

    /**
     * إلغاء أو حذف إذن خروج
     */
    public function destroy(DispatchOrder $dispatchOrder): JsonResponse
    {
        $this->authorize('delete', $dispatchOrder);

        $dispatchOrder->delete();

        return response()->json([
            'message' => 'تم حذف إذن الخروج بنجاح'
        ], 200);
    }
}
