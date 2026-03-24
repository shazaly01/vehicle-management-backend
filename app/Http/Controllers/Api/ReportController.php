<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

use App\Models\Supplier;
use App\Models\MachineryOwner;
use App\Models\Project;
use App\Models\DispatchOrder;
use App\Models\FinancialTransaction;

class ReportController extends Controller
{
    /**
     * 1. تقرير ملخص الموردين
     * الوصول مقتصر على الإدارة والمحاسبين فقط
     */
    public function suppliersSummary(Request $request): JsonResponse
    {
        // نستخدم الصلاحية العامة للتقارير التي وضعناها في الـ Seeder
        $this->authorize('report.view');

        $suppliers = Supplier::withSum('dispatchOrders as total_shipped_value', 'shipped_material_value')
            ->get()
            ->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'phone' => $supplier->phone,
                    'current_balance' => (float) $supplier->current_balance,
                    'total_shipped_value' => (float) ($supplier->total_shipped_value ?? 0),
                ];
            });

        return response()->json([
            'summary' => [
                'total_suppliers' => $suppliers->count(),
                'total_balances_due' => $suppliers->sum('current_balance'),
            ],
            'data' => $suppliers
        ]);
    }

    /**
     * 2. كشف حساب مورد محدد
     */
    public function supplierStatement(Supplier $supplier, Request $request): JsonResponse
    {
        // الموردين تقاريرهم حساسة، لا يراها إلا الإدارة
        $this->authorize('report.view');

        $dispatchOrders = DispatchOrder::where('supplier_id', $supplier->id)
            ->select('id', 'order_no', 'created_at', 'shipped_material_note', 'shipped_material_value', 'project_id')
            ->with('project:id,name')
            ->latest()
            ->get();

        $transactions = FinancialTransaction::where('related_entity_type', Supplier::class)
            ->where('related_entity_id', $supplier->id)
            ->select('id', 'transaction_no', 'transaction_type', 'amount', 'description', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'current_balance' => (float) $supplier->current_balance,
            ],
            'statement' => [
                'dispatch_orders' => $dispatchOrders,
                'financial_transactions' => $transactions,
            ]
        ]);
    }

    /**
     * 3. كشف حساب صاحب آلية محدد
     * [مهم] الـ Policy ستتحقق هنا: هل المستخدم هو المالك نفسه أم إداري؟
     */
    public function machineryOwnerStatement(MachineryOwner $machineryOwner, Request $request): JsonResponse
    {
        // 1. فحص الصلاحية عبر الـ Policy التي أنشأناها
        // إذا كان المالك يحاول رؤية حساب غيره، سيرجع 403 Forbidden تلقائياً
        $this->authorize('view', $machineryOwner);

        $machineries = $machineryOwner->machineries()
            ->withSum(['dispatchOrders as total_earnings' => function ($query) {
                $query->where('status', 'completed');
            }], 'total_cost')
            ->get()
            ->map(function ($machinery) {
                return [
                    'id' => $machinery->id,
                    'plate_number_or_name' => $machinery->plate_number_or_name,
                    'total_earnings' => (float) ($machinery->total_earnings ?? 0),
                ];
            });

        $transactions = FinancialTransaction::where('related_entity_type', MachineryOwner::class)
            ->where('related_entity_id', $machineryOwner->id)
            ->select('id', 'transaction_no', 'transaction_type', 'amount', 'description', 'created_at')
            ->latest()
            ->get();

        $overallEarnings = $machineries->sum('total_earnings');

        // تحسين: حساب إجمالي المدفوعات بناءً على الأنواع المالية التي تعتبر "صرف" للمالك
        $totalReceived = $transactions->whereIn('transaction_type', ['سداد مستحقات', 'صرف نقدي'])->sum('amount');

        return response()->json([
            'owner' => [
                'id' => $machineryOwner->id,
                'name' => $machineryOwner->name,
            ],
            'financial_summary' => [
                'total_earnings' => $overallEarnings,
                'total_received' => $totalReceived,
                'net_balance' => $overallEarnings - $totalReceived,
            ],
            'details' => [
                'machineries_earnings' => $machineries,
                'financial_transactions' => $transactions,
            ]
        ]);
    }

    /**
     * 4. تقرير المشاريع
     */
    public function projectsReportByFilter(Request $request): JsonResponse
    {
        // الإدارة فقط من تطلع على تكاليف المشاريع
        $this->authorize('report.view');

        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Project::query();

        if ($status) {
            $query->where('status', $status);
        }

        $projects = $query->withSum(['dispatchOrders as total_machinery_cost' => function ($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
            }], 'total_cost')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'total_machinery_cost' => (float) ($project->total_machinery_cost ?? 0),
                ];
            });

        return response()->json([
            'filters_applied' => compact('status', 'startDate', 'endDate'),
            'total_projects_cost' => $projects->sum('total_machinery_cost'),
            'data' => $projects
        ]);
    }
}
