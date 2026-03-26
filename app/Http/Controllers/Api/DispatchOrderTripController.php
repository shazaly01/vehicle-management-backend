<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DispatchOrderTrip;
use App\Models\Machinery;
use App\Http\Requests\DispatchOrder\StoreDispatchOrderTripRequest;
use App\Http\Resources\Api\DispatchOrderTripResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DispatchOrderTripController extends Controller
{
   public function index(Request $request): AnonymousResourceCollection
{
    // البدء ببناء الاستعلام مع العلاقات
    // داخل DispatchOrderTripController
$query = DispatchOrderTrip::with([
    'dispatchOrder.supplier', // جلب المورد
    'dispatchOrder.project',  // جلب المشروع
    'machinery',
    'driver'
]);

    // 1. أهم خطوة: إذا كان المستخدم مورد، لا يرى إلا الرحلات التابعة لأوامر التشغيل الخاصة به
    if (auth()->user()->hasRole('Supplier')) {
        // جلب سجل المورد المرتبط بالمستخدم الحالي
        $supplier = \App\Models\Supplier::where('user_id', auth()->id())->first();

        if ($supplier) {
            $query->whereHas('dispatchOrder', function($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            });
        } else {
            // إذا كان المستخدم يحمل دور مورد ولكن ليس له سجل في جدول الموردين، لا نعيد أي بيانات
            $query->whereRaw('1 = 0');
        }
    }

    // 2. فلترة برقم الأمر (إذا تم إرساله)
    if ($request->filled('dispatch_order_id')) {
        $query->where('dispatch_order_id', $request->dispatch_order_id);
    }

    // 3. معالجة حالات الرحلة (دعم القيم المتعددة المفصولة بفاصلة)
    if ($request->filled('status')) {
        $statuses = explode(',', $request->status); // تحويل "dispatched,loaded" إلى مصفوفة
        $query->whereIn('status', $statuses);       // استخدام whereIn للبحث عن أي من الحالات
    }

    $trips = $query->latest()->paginate(15);

    return DispatchOrderTripResource::collection($trips);
}
    /**
     * إنشاء حركة/نقلة جديدة
     */
    public function store(StoreDispatchOrderTripRequest $request): JsonResponse|DispatchOrderTripResource
    {
        $validated = $request->validated();

        // 1. جلب الآلية المختارة لسحب بياناتها
        $machinery = Machinery::findOrFail($validated['machinery_id']);

        // 2. التحقق من وجود سائق مرتبط بالآلية
        if (empty($machinery->driver_id)) {
            return response()->json([
                'message' => 'لا يمكن تسجيل الحركة. الآلية المحددة ليس لها سائق مسجل حالياً.',
                'errors' => ['machinery_id' => ['الآلية بلا سائق.']]
            ], 422);
        }

        // 3. تعبئة البيانات الآلية (السائق ونوع التكلفة) لضمان ثبات السجل
        $validated['driver_id'] = $machinery->driver_id;
        $validated['transport_cost_type'] = $machinery->cost_type;

        // 4. معالجة أوقات التأكيد المبدئية بناءً على الحالة المُرسلة
        $status = $validated['status'] ?? 'dispatched';
        if ($status === 'loaded') {
            $validated['loaded_at'] = now();
        } elseif ($status === 'delivered') {
            $validated['loaded_at'] = now();
            $validated['delivered_at'] = now();
        }

        // 5. إنشاء السجل
        $trip = DispatchOrderTrip::create($validated);

        // تحميل العلاقات لإرجاعها في الاستجابة
        $trip->load(['machinery', 'driver', 'dispatchOrder']);

        return new DispatchOrderTripResource($trip);
    }

    /**
     * عرض تفاصيل حركة محددة
     */
    public function show(DispatchOrderTrip $dispatchOrderTrip): DispatchOrderTripResource
    {
        $dispatchOrderTrip->load(['machinery.owner', 'driver', 'dispatchOrder']);

        return new DispatchOrderTripResource($dispatchOrderTrip);
    }

    /**
     * مسار مخصص لتحديث "حالة" الحركة ودورة الاعتماد (للمورد ومدير المشروع)
     */
    public function updateStatus(Request $request, DispatchOrderTrip $dispatchOrderTrip): DispatchOrderTripResource
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:dispatched,loaded,delivered,canceled']
        ]);

        $newStatus = $validated['status'];
        $dispatchOrderTrip->status = $newStatus;

        // تسجيل وقت التحميل إذا تم التأكيد (صلاحية المورد مستقبلاً)
        if ($newStatus === 'loaded' && is_null($dispatchOrderTrip->loaded_at)) {
            $dispatchOrderTrip->loaded_at = now();
        }
        // تسجيل وقت الاستلام إذا تم التأكيد (صلاحية المشروع مستقبلاً)
        elseif ($newStatus === 'delivered') {
            if (is_null($dispatchOrderTrip->loaded_at)) {
                $dispatchOrderTrip->loaded_at = now(); // تأمين منطقي
            }
            if (is_null($dispatchOrderTrip->delivered_at)) {
                $dispatchOrderTrip->delivered_at = now();
            }
        }

        $dispatchOrderTrip->save();

        return new DispatchOrderTripResource($dispatchOrderTrip);
    }

    /**
     * حذف حركة محددة
     */
    public function destroy(DispatchOrderTrip $dispatchOrderTrip): JsonResponse
    {
        $dispatchOrderTrip->delete();

        return response()->json([
            'message' => 'تم حذف الحركة بنجاح'
        ], 200);
    }
}
