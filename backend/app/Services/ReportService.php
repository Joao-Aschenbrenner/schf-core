<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Supplier;
use App\Models\HealthPlan;
use App\Models\ExpenseCategory;
use App\Models\BankAccount;
use App\Models\Nfe;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReportService
{
    public function supplierReport(array $filters = []): array
    {
        $query = Payable::query()
            ->with('supplier')
            ->when(isset($filters['supplier_id']), fn($q) => $q->where('supplier_id', $filters['supplier_id']))
            ->when(isset($filters['date_from']), fn($q) => $q->where('due_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->where('due_date', '<=', $filters['date_to']));

        $payables = $query->get();

        $grouped = $payables->groupBy('supplier_id')->map(function ($items, $supplierId) {
            $supplier = Supplier::find($supplierId);
            return [
                'supplier_id' => $supplierId,
                'supplier_name' => $supplier?->name ?? 'Desconhecido',
                'total_amount' => $items->sum('amount'),
                'total_paid' => $items->where('status', 'paid')->sum('paid_amount'),
                'total_pending' => $items->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'total_overdue' => $items->where('status', 'overdue')->sum('amount'),
                'count' => $items->count(),
            ];
        })->sortByDesc('total_amount')->values();

        return [
            'suppliers' => $grouped,
            'summary' => [
                'total_suppliers' => $grouped->count(),
                'total_amount' => $grouped->sum('total_amount'),
                'total_paid' => $grouped->sum('total_paid'),
                'total_pending' => $grouped->sum('total_pending'),
            ],
        ];
    }

    public function categoryReport(array $filters = []): array
    {
        $query = Payable::query()
            ->with('expenseCategory')
            ->when(isset($filters['date_from']), fn($q) => $q->where('due_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->where('due_date', '<=', $filters['date_to']));

        $payables = $query->get();

        $grouped = $payables->groupBy('expense_category_id')->map(function ($items, $catId) {
            $category = ExpenseCategory::find($catId);
            return [
                'category_id' => $catId,
                'category_name' => $category?->name ?? 'Sem categoria',
                'total_amount' => $items->sum('amount'),
                'total_paid' => $items->where('status', 'paid')->sum('paid_amount'),
                'total_pending' => $items->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'count' => $items->count(),
            ];
        })->sortByDesc('total_amount')->values();

        return [
            'categories' => $grouped,
            'summary' => [
                'total_categories' => $grouped->count(),
                'total_amount' => $grouped->sum('total_amount'),
            ],
        ];
    }

    public function planReport(array $filters = []): array
    {
        $query = Payable::query()
            ->with('healthPlan')
            ->when(isset($filters['health_plan_id']), fn($q) => $q->where('health_plan_id', $filters['health_plan_id']))
            ->when(isset($filters['date_from']), fn($q) => $q->where('due_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($q) => $q->where('due_date', '<=', $filters['date_to']));

        $payables = $query->get();

        $grouped = $payables->groupBy('health_plan_id')->map(function ($items, $planId) {
            $plan = HealthPlan::find($planId);
            return [
                'plan_id' => $planId,
                'plan_name' => $plan?->name ?? 'Sem convênio',
                'total_amount' => $items->sum('amount'),
                'total_paid' => $items->where('status', 'paid')->sum('paid_amount'),
                'total_pending' => $items->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'count' => $items->count(),
            ];
        })->sortByDesc('total_amount')->values();

        return [
            'plans' => $grouped,
            'summary' => [
                'total_plans' => $grouped->count(),
                'total_amount' => $grouped->sum('total_amount'),
            ],
        ];
    }

    public function cashFlowReport(array $filters = []): array
    {
        $dateFrom = Carbon::parse($filters['date_from'] ?? Carbon::now()->startOfMonth());
        $dateTo = Carbon::parse($filters['date_to'] ?? Carbon::now()->endOfMonth());

        $paid = Payable::where('status', 'paid')
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get();

        $pending = Payable::whereIn('status', ['pending', 'overdue'])
            ->whereBetween('due_date', [$dateFrom, $dateTo])
            ->get();

        $totalPaid = $paid->sum('paid_amount');
        $totalPending = $pending->sum('amount');

        return [
            'period' => [
                'from' => $dateFrom->toDateString(),
                'to' => $dateTo->toDateString(),
            ],
            'outflows' => [
                'paid' => $totalPaid,
                'pending' => $totalPending,
                'total' => $totalPaid + $totalPending,
            ],
            'by_method' => $paid->groupBy('payment_method')->map(fn($items) => [
                'count' => $items->count(),
                'total' => $items->sum('paid_amount'),
            ]),
            'daily_flow' => $this->getDailyFlow($dateFrom, $dateTo),
        ];
    }

    public function prestacaoContas(array $filters = []): array
    {
        $healthPlanId = $filters['health_plan_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? Carbon::now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? Carbon::now()->endOfMonth()->toDateString();

        $query = Payable::query()
            ->with(['supplier', 'healthPlan', 'expenseCategory'])
            ->whereBetween('due_date', [$dateFrom, $dateTo]);

        if ($healthPlanId) {
            $query->where('health_plan_id', $healthPlanId);
        }

        $payables = $query->get();

        $healthPlan = $healthPlanId ? HealthPlan::find($healthPlanId) : null;

        return [
            'health_plan' => $healthPlan ? [
                'id' => $healthPlan->id,
                'name' => $healthPlan->name,
                'code' => $healthPlan->code,
            ] : null,
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'entries' => $payables->map(fn($p) => [
                'id' => $p->id,
                'description' => $p->description,
                'supplier' => $p->supplier?->name,
                'category' => $p->expenseCategory?->name,
                'amount' => $p->amount,
                'paid_amount' => $p->paid_amount,
                'due_date' => $p->due_date,
                'payment_date' => $p->payment_date,
                'status' => $p->status,
            ]),
            'summary' => [
                'total_entries' => $payables->count(),
                'total_amount' => $payables->sum('amount'),
                'total_paid' => $payables->where('status', 'paid')->sum('paid_amount'),
                'total_pending' => $payables->whereIn('status', ['pending', 'overdue'])->sum('amount'),
            ],
        ];
    }

    private function getDailyFlow(Carbon $from, Carbon $to): array
    {
        $days = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $dayPaid = Payable::where('status', 'paid')
                ->where('payment_date', $current->toDateString())
                ->sum('paid_amount');

            $days[] = [
                'date' => $current->toDateString(),
                'paid' => $dayPaid,
            ];

            $current->addDay();
        }

        return $days;
    }
}
