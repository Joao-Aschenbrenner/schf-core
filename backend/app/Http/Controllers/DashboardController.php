<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Backup;
use App\Models\BankAccount;
use App\Models\HealthPlan;
use App\Models\License;
use App\Models\Payable;
use App\Models\PreLaunch;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    public function operational(): JsonResponse
    {
        $today = now()->toDateString();

        $totalBalance = BankAccount::where('is_active', true)->sum('current_balance');

        $pendingPayables = Payable::whereIn('status', ['pending'])->count();
        $overduePayables = Payable::query()
            ->where('status', 'pending')
            ->where('due_date', '<', $today)
            ->count();
        $dueToday = Payable::where('status', 'pending')
            ->where('due_date', $today)
            ->count();
        $paidThisMonth = Payable::where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->count();
        $totalPaidThisMonth = Payable::where('status', 'paid')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('paid_amount');

        $payablesByStatus = Payable::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $monthlyPayables = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthLabel = $date->format('M/Y');
            $monthTotal = Payable::whereMonth('due_date', $date->month)
                ->whereYear('due_date', $date->year)
                ->sum('amount');
            $monthPaid = Payable::where('status', 'paid')
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('paid_amount');
            $monthOverdue = Payable::where('status', 'pending')
                ->whereMonth('due_date', $date->month)
                ->whereYear('due_date', $date->year)
                ->where('due_date', '<', $today)
                ->count();
            $monthlyPayables->push([
                'month' => $monthLabel,
                'total' => round((float) $monthTotal, 2),
                'paid' => round((float) $monthPaid, 2),
                'overdue' => $monthOverdue,
            ]);
        }

        $totalSuppliers = Supplier::where('is_active', true)->count();
        $activeHealthPlans = HealthPlan::where('is_active', true)->count();
        $totalBankAccounts = BankAccount::where('is_active', true)->count();
        $pendingPreLaunches = PreLaunch::where('status', 'draft')->count();

        $recentActivity = AuditTrail::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'action' => $a->action,
                'model_type' => $a->model_type,
                'model_id' => $a->model_id,
                'user_name' => $a->user?->name ?? 'Sistema',
                'created_at' => $a->created_at->toIso8601String(),
            ]);

        $lastBackup = Backup::where('status', 'completed')
            ->orderByDesc('completed_at')
            ->first();
        $totalBackups = Backup::where('status', 'completed')->count();

        $license = License::where('status', 'active')
            ->orderByDesc('activated_at')
            ->first();

        $healthChecks = $this->getSystemHealth();

        return response()->json([
            'data' => [
                'summary' => [
                    'total_balance' => round((float) $totalBalance, 2),
                    'pending_payables' => $pendingPayables,
                    'overdue_payables' => $overduePayables,
                    'due_today' => $dueToday,
                    'paid_this_month' => $paidThisMonth,
                    'total_paid_this_month' => round((float) $totalPaidThisMonth, 2),
                    'pending_pre_launches' => $pendingPreLaunches,
                    'total_suppliers' => $totalSuppliers,
                    'active_health_plans' => $activeHealthPlans,
                    'total_bank_accounts' => $totalBankAccounts,
                ],
                'payables_by_status' => $payablesByStatus,
                'monthly_payables' => $monthlyPayables->toArray(),
                'recent_activity' => $recentActivity,
                'backup' => [
                    'last_backup_at' => $lastBackup?->completed_at?->toIso8601String(),
                    'total_backups' => $totalBackups,
                    'last_backup_size' => $lastBackup?->file_size,
                    'last_backup_name' => $lastBackup?->name,
                ],
                'license' => $license ? [
                    'key' => substr($license->key, 0, 12) . '...',
                    'type' => $license->type,
                    'status' => $license->status,
                    'expires_at' => $license->expires_at?->toIso8601String(),
                    'customer_name' => $license->customer_name,
                ] : null,
                'system_health' => $healthChecks,
            ],
        ]);
    }

    private function getSystemHealth(): array
    {
        $checks = [];

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $checks['mysql'] = ['status' => 'ok', 'latency_ms' => round((microtime(true) - $start) * 1000, 2)];
        } catch (\Exception $e) {
            $checks['mysql'] = ['status' => 'error', 'message' => 'MySQL inacessível'];
        }

        try {
            $start = microtime(true);
            DB::table('audit_trails')->count();
            $checks['audit'] = ['status' => 'ok', 'latency_ms' => round((microtime(true) - $start) * 1000, 2)];
        } catch (\Exception $e) {
            $checks['audit'] = ['status' => 'warning', 'message' => 'Tabela de auditoria indisponível'];
        }

        $storagePath = storage_path();
        $storageUsed = 0;
        foreach (glob($storagePath . '/**/*') as $file) {
            if (is_file($file)) {
                $storageUsed += filesize($file);
            }
        }
        $checks['storage'] = [
            'status' => 'ok',
            'used_bytes' => $storageUsed,
            'used_formatted' => $this->formatBytes($storageUsed),
        ];

        $checks['queue'] = ['status' => 'ok', 'info' => 'configured'];

        return $checks;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
