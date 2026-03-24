<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Treasury;
use App\Http\Requests\Treasury\StoreTreasuryRequest;
use App\Http\Requests\Treasury\UpdateTreasuryRequest;
use App\Http\Resources\Api\TreasuryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TreasuryController extends Controller
{
    /**
     * عرض قائمة الخزائن
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Treasury::class);

        $treasuries = Treasury::latest()->paginate(15);

        return TreasuryResource::collection($treasuries);
    }

    /**
     * إضافة خزينة جديدة
     */
    public function store(StoreTreasuryRequest $request): TreasuryResource
    {
        $this->authorize('create', Treasury::class);

        $treasury = Treasury::create($request->validated());

        return new TreasuryResource($treasury);
    }

    /**
     * عرض بيانات خزينة محددة
     */
    public function show(Treasury $treasury): TreasuryResource
    {
        $this->authorize('view', $treasury);

        return new TreasuryResource($treasury);
    }

    /**
     * تحديث بيانات الخزينة (مثل تعديل الرصيد الافتتاحي أو الاسم)
     */
    public function update(UpdateTreasuryRequest $request, Treasury $treasury): TreasuryResource
    {
        $this->authorize('update', $treasury);

        $treasury->update($request->validated());

        return new TreasuryResource($treasury);
    }

    /**
     * حذف خزينة
     */
    public function destroy(Treasury $treasury): JsonResponse
    {
        $this->authorize('delete', $treasury);

        // تنبيه: في الأنظمة المالية الحقيقية، يفضل منع حذف الخزينة إذا كان لها حركات مالية مرتبطة
        if ($treasury->financialTransactions()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف هذه الخزينة لوجود حركات مالية مرتبطة بها'
            ], 403);
        }

        $treasury->delete();

        return response()->json([
            'message' => 'تم حذف الخزينة بنجاح'
        ], 200);
    }
}
