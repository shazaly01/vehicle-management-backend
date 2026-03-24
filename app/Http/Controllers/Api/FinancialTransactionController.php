<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\MachineryOwner;
use App\Http\Requests\FinancialTransaction\StoreFinancialTransactionRequest;
use App\Http\Requests\FinancialTransaction\UpdateFinancialTransactionRequest;
use App\Http\Resources\Api\FinancialTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinancialTransactionController extends Controller
{
  /**
     * عرض قائمة المعاملات المالية (كشوفات الحساب وحركة الخزائن)
     */
    public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FinancialTransaction::class);

        $query = FinancialTransaction::query();

        // --- إضافة الفلاتر القادمة من الواجهة الأمامية ---

        // 1. فلترة البحث برقم السند
        if ($request->filled('search')) {
            $query->where('transaction_no', 'like', '%' . $request->search . '%');
        }

        // 2. فلترة بنوع المعاملة (قبض / صرف)
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        // 3. فلترة بالخزينة المحددة
        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        // ------------------------------------------------

        // فلترة خاصة بـ "صاحب الآلية" ليرى معاملاته الشخصية فقط
        if (auth()->user()->hasRole('Machinery Owner')) {
            $owner = MachineryOwner::where('user_id', auth()->id())->first();
            if ($owner) {
                $query->where('related_entity_type', MachineryOwner::class)
                      ->where('related_entity_id', $owner->id);
            } else {
                // إذا لم يتم ربط المستخدم بملف مالك، نمنع عرض أي بيانات
                $query->whereRaw('1 = 0');
            }
        }

        // تحميل الخزينة والكيان المرتبط
        $transactions = $query->with(['treasury', 'related_entity'])
                              ->latest()
                              ->paginate(15);

        return FinancialTransactionResource::collection($transactions);
    }

    /**
     * إنشاء حركة مالية جديدة (سند قبض، صرف، تسوية)
     */
    public function store(StoreFinancialTransactionRequest $request): FinancialTransactionResource
    {
        $this->authorize('create', FinancialTransaction::class);

        $transaction = FinancialTransaction::create($request->validated());

        // ملاحظة للمستقبل:
        // هنا يمكنك إضافة منطق (أو استخدام Observers) لتحديث رصيد الخزينة (Treasury)
        // أو تحديث الرصيد الحالي للمورد (Supplier current_balance) تلقائياً بناءً على نوع الحركة والمبلغ.

        // نقوم بتحميل العلاقات قبل إرجاع الاستجابة لتظهر البيانات مكتملة
        $transaction->load(['treasury', 'related_entity']);

        return new FinancialTransactionResource($transaction);
    }

    /**
     * عرض تفاصيل حركة مالية محددة
     */
    public function show(FinancialTransaction $financialTransaction): FinancialTransactionResource
    {
        $this->authorize('view', $financialTransaction);

        $financialTransaction->load(['treasury', 'related_entity']);

        return new FinancialTransactionResource($financialTransaction);
    }

    /**
     * تحديث أو تعديل حركة مالية
     */
    public function update(UpdateFinancialTransactionRequest $request, FinancialTransaction $financialTransaction): FinancialTransactionResource
    {
        $this->authorize('update', $financialTransaction);

        $financialTransaction->update($request->validated());

        // ملاحظة: إذا تم تعديل المبلغ أو الخزينة، يجب أن تتأكد من معالجة فروقات الأرصدة لاحقاً.

        $financialTransaction->load(['treasury', 'related_entity']);

        return new FinancialTransactionResource($financialTransaction);
    }

    /**
     * حذف حركة مالية
     */
    public function destroy(FinancialTransaction $financialTransaction): JsonResponse
    {
        $this->authorize('delete', $financialTransaction);

        $financialTransaction->delete();

        return response()->json([
            'message' => 'تم حذف الحركة المالية بنجاح'
        ], 200);
    }
}
