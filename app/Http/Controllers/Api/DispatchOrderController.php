<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DispatchOrder;
use App\Models\MachineryOwner;
use App\Models\Machinery;
use App\Http\Requests\DispatchOrder\StoreDispatchOrderRequest;
use App\Http\Requests\DispatchOrder\UpdateDispatchOrderRequest;
use App\Http\Resources\Api\DispatchOrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class DispatchOrderController extends Controller
{
    /**
     * عرض قائمة أوامر التشغيل الرئيسية (مع دعم الفلترة والبحث)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DispatchOrder::class);

        $query = DispatchOrder::query();

        // 1. فلترة البحث برقم الأمر المرجعي (order_no)
        if ($request->filled('search')) {
            $query->where('order_no', 'like', '%' . $request->search . '%');
        }

        // 2. فلترة بالمشروع المحدد
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // 3. فلترة بحالة الأمر (active, completed, canceled)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // --- نظام الصلاحيات الخاص بمالك الآلية ---
        if (auth()->user()->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', auth()->id())->first();
            if ($owner) {
                $query->whereHas('trips.machinery', function ($q) use ($owner) {
                    $q->where('owner_id', $owner->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // تحميل العلاقات كاملة لضمان ظهور الشاحنات عند الضغط على "تعديل" في الواجهة
        $orders = $query->with(['supplier', 'project', 'trips.machinery'])
                        ->latest()
                        ->paginate(15);

        return DispatchOrderResource::collection($orders);
    }

    /**
     * إنشاء أمر تشغيل (عقد) مع حشر شاحنات متعددة في نفس الوقت
     */
    public function store(StoreDispatchOrderRequest $request): DispatchOrderResource
    {
        $this->authorize('create', DispatchOrder::class);

        return DB::transaction(function () use ($request) {
            // 1. إنشاء العقد الرئيسي (رأس الفاتورة)
            $dispatchOrder = DispatchOrder::create($request->validated());

            // 2. معالجة الشاحنات المرفقة (Trips)
            if ($request->has('trips') && is_array($request->trips)) {
                foreach ($request->trips as $tripData) {
                    $machinery = Machinery::find($tripData['machinery_id']);

                    if ($machinery) {
                        $dispatchOrder->trips()->create([
                            'machinery_id'         => $machinery->id,
                            'driver_id'            => $machinery->driver_id,
                            'quantity'             => $tripData['quantity'] ?? 1,
                            'transport_cost_type'  => $machinery->cost_type,
                            'transport_unit_price' => $tripData['transport_unit_price'] ?? $machinery->unit_price ?? 0,
                            'status'               => $tripData['status'] ?? 'dispatched',
                        ]);
                    }
                }
            }

            return new DispatchOrderResource($dispatchOrder->load('trips.machinery'));
        });
    }

    /**
     * عرض تفاصيل أمر تشغيل محدد
     */
    public function show(DispatchOrder $dispatchOrder): DispatchOrderResource
    {
        $this->authorize('view', $dispatchOrder);

        // شحن كل العلاقات المطلوبة للواجهة
        $dispatchOrder->load(['supplier', 'project', 'trips.machinery.owner', 'trips.driver']);

        return new DispatchOrderResource($dispatchOrder);
    }

    /**
     * تحديث أمر التشغيل مع مزامنة الشاحنات (إضافة/تعديل/حذف)
     */
    public function update(UpdateDispatchOrderRequest $request, DispatchOrder $dispatchOrder): DispatchOrderResource
    {
        $this->authorize('update', $dispatchOrder);

        return DB::transaction(function () use ($request, $dispatchOrder) {
            // 1. تحديث بيانات العقد الرئيسي
            $dispatchOrder->update($request->validated());

            // 2. مزامنة الشاحنات (Trips)
            if ($request->has('trips')) {
                $incomingTripIds = collect($request->trips)->pluck('id')->filter()->toArray();

                // أ. حذف الشاحنات التي تمت إزالتها من الجدول في الواجهة
                $dispatchOrder->trips()->whereNotIn('id', $incomingTripIds)->delete();

                // ب. تحديث الشاحنات الموجودة أو إضافة جديدة
                foreach ($request->trips as $tripData) {
                    $machinery = Machinery::find($tripData['machinery_id']);

                    if ($machinery) {
                        $dataToSave = [
                            'machinery_id'         => $machinery->id,
                            'driver_id'            => $machinery->driver_id,
                            'quantity'             => $tripData['quantity'] ?? 1,
                            'transport_cost_type'  => $machinery->cost_type,
                            'transport_unit_price' => $tripData['transport_unit_price'] ?? $machinery->unit_price ?? 0,
                            'status'               => $tripData['status'] ?? 'dispatched',
                        ];

                        if (isset($tripData['id']) && !empty($tripData['id'])) {
                            // تحديث حركة موجودة مسبقاً
                            $dispatchOrder->trips()->where('id', $tripData['id'])->update($dataToSave);
                        } else {
                            // إضافة حركة جديدة أضيفت للجدول
                            $dispatchOrder->trips()->create($dataToSave);
                        }
                    }
                }
            }

            return new DispatchOrderResource($dispatchOrder->load('trips.machinery'));
        });
    }

    /**
     * حذف أمر تشغيل
     */
    public function destroy(DispatchOrder $dispatchOrder): JsonResponse
    {
        $this->authorize('delete', $dispatchOrder);

        $dispatchOrder->delete();

        return response()->json([
            'message' => 'تم حذف أمر التشغيل وكافة الحركات التابعة له بنجاح'
        ], 200);
    }
}
