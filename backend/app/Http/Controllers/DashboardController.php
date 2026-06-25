<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\HealthPlan;
use App\Models\Payable;
use App\Models\PreLaunch;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();

        $totalBalance = BankAccount::where('is_active', true)->sum('current_balance');

        $pendingPayables = Payable::whereIn('status', ['pending', 'overdue'])->count();

        $overduePayables = Payable::query()
            ->where('status', 'pending')
            ->where('due_date', '<', $today)
            ->count();

        $dueToday = Payable::where('status', 'pending')
            ->where('due_date', $today)
            ->count();

        $activeHealthPlans = HealthPlan::where('is_active', true)->count();
        $totalSuppliers = Supplier::where('is_active', true)->count();
        $totalBankAccounts = BankAccount::where('is_active', true)->count();

        $pendingPreLaunches = PreLaunch::where('status', 'draft')->count();

        return response()->json([
            'data' => [
                'total_balance' => $totalBalance,
                'pending_payables' => $pendingPayables,
                'overdue_payables' => $overduePayables,
                'due_today' => $dueToday,
                'active_health_plans' => $activeHealthPlans,
                'total_suppliers' => $totalSuppliers,
                'total_bank_accounts' => $totalBankAccounts,
                'pending_pre_launches' => $pendingPreLaunches,
            ],
        ]);
    }
}
