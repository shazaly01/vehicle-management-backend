<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\Api\SupplierResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
   /**
     * عرض قائمة الموردين (مع دعم البحث بالاسم أو الهاتف)
     */
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

        // جلب الموردين مرتبين من الأحدث مع التقسيم
        $suppliers = $query->latest()->paginate(15);

        return SupplierResource::collection($suppliers);
    }


    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create($request->validated());

        return new SupplierResource($supplier);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        $this->authorize('view', $supplier);

        // تحميل العلاقات إذا أردت عرض الحركات الخاصة به في نفس الطلب
        $supplier->load(['dispatchOrders', 'financialTransactions']);

        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->validated());

        return new SupplierResource($supplier);
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
