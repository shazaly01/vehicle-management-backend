<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use App\Models\Supplier;
use App\Models\MachineryOwner;
use App\Models\Project;
use App\Models\DispatchOrder;
use App\Models\FinancialTransaction;

class ReportController extends Controller
{
    /**
     * 1. تقرير ملخص الموردين (أصحاب المواد)
     * يعرض إجمالي المسحوبات والرصيد الحالي بالدينار الليبي
     */
    public function suppliersSummary(Request $request): JsonResponse
    {
        $this->authorize('report.view');

        $suppliers = Supplier::get()->map(function ($supplier) {
            // حساب إجمالي قيمة المواد الموردة من أوامر التشغيل
            // القيمة = الكمية المستهدفة * سعر وحدة المادة
            $totalMaterialValue = DispatchOrder::where('supplier_id', $supplier->id)
                ->select(DB::raw('SUM(target_quantity * material_unit_price) as total'))
                ->first()->total ?? 0;

            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'current_balance' => round((float) $supplier->current_balance, 3), // 3 خانات للدينار
                'total_supplied_value' => round((float) $totalMaterialValue, 3),
                'currency' => 'د.ل'
            ];
        });

        return response()->json([
            'summary' => [
                'total_suppliers' => $suppliers->count(),
                'total_balances_due' => round($suppliers->sum('current_balance'), 3),
            ],
            'data' => $suppliers
        ]);
    }

    public function supplierStatement(Supplier $supplier, Request $request): JsonResponse
{
    $this->authorize('report.view');

    // 1. سجل توريد المواد (المستحقات له)
    $materialSupplies = DispatchOrder::where('supplier_id', $supplier->id)
        ->with('project:id,name')
        ->latest()
        ->get()
        ->map(function($order) {
            return [
                'date' => $order->created_at->format('Y-m-d'),
                'order_no' => $order->order_no,
                'project_name' => $order->project?->name,
                'material_type' => $order->operation_type,
                'quantity' => $order->target_quantity,
                'unit_price' => round($order->material_unit_price, 3),
                'total_price' => round($order->target_quantity * $order->material_unit_price, 3),
            ];
        });

    // 2. سجل الدفعات المالية (المسحوبات منه)
    $financialTransactions = FinancialTransaction::where('related_entity_type', Supplier::class)
        ->where('related_entity_id', $supplier->id)
        ->with('treasury:id,name')
        ->latest()
        ->get()
        ->map(function($txn) {
            return [
                'date' => $txn->date ?? $txn->created_at->format('Y-m-d'),
                'transaction_no' => $txn->transaction_no,
                'type' => $txn->transaction_type, // receipt or payment
                'treasury' => $txn->treasury?->name,
                'amount' => round($txn->amount, 3),
                'description' => $txn->description,
            ];
        });

    $totalSupplied = $materialSupplies->sum('total_price');
    $totalPaid = $financialTransactions->where('type', 'payment')->sum('amount');
    $totalReceived = $financialTransactions->where('type', 'receipt')->sum('amount');

    return response()->json([
        'supplier' => $supplier->name,
        'summary' => [
            'total_supplied_value' => round($totalSupplied, 3),
            'total_paid_to_supplier' => round($totalPaid, 3),
            'current_balance' => round($totalSupplied - $totalPaid + $totalReceived, 3),
            'currency' => 'د.ل'
        ],
        'details' => [
            'material_supplies' => $materialSupplies,
            'financial_transactions' => $financialTransactions,
        ]
    ]);
}

    /**
     * 3. كشف حساب صاحب آلية (نظام الحشر المحدث)
     * يحسب الأرباح من جدول الحركات (Trips) والسدادات من المالية
     */
    public function machineryOwnerStatement(MachineryOwner $machineryOwner, Request $request): JsonResponse
    {
        $this->authorize('view', $machineryOwner);

        // حساب أرباح المالك من كل حركة شاحنة على حدة (الحشر)
        // الربح = كمية النقلة * سعر وحدة النقل المتفق عليه للمالك
        $earnings = DB::table('dispatch_order_trips')
            ->join('machineries', 'dispatch_order_trips.machinery_id', '=', 'machineries.id')
            ->join('dispatch_orders', 'dispatch_order_trips.dispatch_order_id', '=', 'dispatch_orders.id')
            ->where('machineries.owner_id', $machineryOwner->id)
            ->select(
                'dispatch_orders.order_no',
                'dispatch_orders.operation_type',
                'dispatch_order_trips.quantity',
                'dispatch_order_trips.transport_unit_price',
                'dispatch_order_trips.created_at',
                'machineries.plate_number_or_name'
            )
            ->get()
            ->map(function($trip) {
                return [
                    'date' => $trip->created_at,
                    'machinery' => $trip->plate_number_or_name,
                    'description' => "نقل عمل: " . $trip->operation_type,
                    'earnings' => round($trip->quantity * $trip->transport_unit_price, 3)
                ];
            });

        // جلب المسحوبات والسلف (سندات الصرف)
        $payments = FinancialTransaction::where('related_entity_type', MachineryOwner::class)
            ->where('related_entity_id', $machineryOwner->id)
            ->get()
            ->map(function($txn) {
                return [
                    'date' => $txn->date ?? $txn->created_at,
                    'description' => $txn->description,
                    'amount' => round($txn->amount, 3),
                    'type' => $txn->transaction_type
                ];
            });

        $totalEarnings = $earnings->sum('earnings');
        $totalPaid = $payments->where('type', 'payment')->sum('amount');
        $totalReceived = $payments->where('type', 'receipt')->sum('amount'); // في حال أرجع سلفة

        return response()->json([
            'owner_name' => $machineryOwner->name,
            'summary' => [
                'total_earnings' => round($totalEarnings, 3),
                'total_paid' => round($totalPaid, 3),
                'net_balance' => round($totalEarnings - $totalPaid + $totalReceived, 3),
                'currency' => 'د.ل'
            ],
            'details' => [
                'work_history' => $earnings,
                'financial_history' => $payments
            ]
        ]);
    }

    /**
     * 4. تقرير تكاليف المشاريع
     */
    public function projectsReportByFilter(Request $request): JsonResponse
    {
        $this->authorize('report.view');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $projects = Project::get()->map(function ($project) use ($startDate, $endDate) {

            // حساب تكلفة الآليات من جدول الحركات (Trips) المرتبطة بالمشروع
            $query = DB::table('dispatch_order_trips')
                ->join('dispatch_orders', 'dispatch_order_trips.dispatch_order_id', '=', 'dispatch_orders.id')
                ->where('dispatch_orders.project_id', $project->id);

            if ($startDate && $endDate) {
                $query->whereBetween('dispatch_order_trips.created_at', [$startDate, $endDate]);
            }

            $totalCost = $query->select(DB::raw('SUM(quantity * transport_unit_price) as total'))->first()->total ?? 0;

            return [
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'total_machinery_cost' => round((float) $totalCost, 3),
                'currency' => 'د.ل'
            ];
        });

        return response()->json([
            'total_all_projects_cost' => round($projects->sum('total_machinery_cost'), 3),
            'data' => $projects
        ]);
    }
}
