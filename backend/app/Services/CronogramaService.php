<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\BankAccount;
use App\Models\PreLaunch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CronogramaService
{
    public function getProjection(array $filters = []): array
    {
        $bankAccountId = $filters['bank_account_id'] ?? null;
        $horizons = [30, 60, 90, 180, 365];

        $currentBalance = $this->getCurrentBalance($bankAccountId);

        $scheduledOutflows = $this->getScheduledOutflows($bankAccountId);
        $projectedInflows = $this->getProjectedInflows($bankAccountId);

        $result = [];
        $runningBalance = $currentBalance;

        foreach ($horizons as $days) {
            $targetDate = Carbon::today()->addDays($days);

            $outflowsInPeriod = $scheduledOutflows
                ->filter(fn($p) => Carbon::parse($p->due_date)->lte($targetDate))
                ->sum('amount');

            $inflowsInPeriod = $projectedInflows
                ->filter(fn($p) => Carbon::parse($p->due_date)->lte($targetDate))
                ->sum('amount');

            $projectedBalance = $currentBalance - $outflowsInPeriod + $inflowsInPeriod;

            $result[] = [
                'horizon_days' => $days,
                'target_date' => $targetDate->toDateString(),
                'current_balance' => $currentBalance,
                'outflows' => $outflowsInPeriod,
                'inflows' => $inflowsInPeriod,
                'projected_balance' => $projectedBalance,
            ];
        }

        $dailyBreakdown = $this->getDailyBreakdown($bankAccountId, 90);

        return [
            'summary' => [
                'current_balance' => $currentBalance,
                'total_pending_outflows' => $scheduledOutflows->sum('amount'),
                'total_projected_inflows' => $projectedInflows->sum('amount'),
            ],
            'projections' => $result,
            'daily_breakdown' => $dailyBreakdown,
        ];
    }

    private function getCurrentBalance(?int $bankAccountId): float
    {
        $query = BankAccount::where('is_active', true);

        if ($bankAccountId) {
            $query->where('id', $bankAccountId);
        }

        return (float) $query->sum('current_balance');
    }

    private function getScheduledOutflows(?int $bankAccountId): \Illuminate\Support\Collection
    {
        $query = Payable::query()
            ->whereIn('status', ['pending', 'scheduled', 'overdue']);

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        return $query->get();
    }

    private function getProjectedInflows(?int $bankAccountId): \Illuminate\Support\Collection
    {
        $query = PreLaunch::query()
            ->where('status', 'confirmed');

        return $query->get()->map(function ($pl) {
            return (object) [
                'due_date' => $pl->due_date,
                'amount' => $pl->amount,
            ];
        });
    }

    private function getDailyBreakdown(?int $bankAccountId, int $days): array
    {
        $today = Carbon::today();
        $outflows = Payable::query()
            ->whereIn('status', ['pending', 'scheduled', 'overdue'])
            ->where('due_date', '<=', $today->copy()->addDays($days))
            ->when($bankAccountId, fn($q) => $q->where('bank_account_id', $bankAccountId))
            ->get()
            ->groupBy(fn($p) => Carbon::parse($p->due_date)->toDateString());

        $breakdown = [];
        $runningBalance = $this->getCurrentBalance($bankAccountId);

        for ($i = 0; $i <= $days; $i++) {
            $date = $today->copy()->addDays($i)->toDateString();
            $dayOutflows = $outflows->get($date, collect())->sum('amount');

            $runningBalance -= $dayOutflows;

            $breakdown[] = [
                'date' => $date,
                'outflows' => $dayOutflows,
                'running_balance' => $runningBalance,
            ];
        }

        return $breakdown;
    }
}
