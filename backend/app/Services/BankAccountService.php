<?php

namespace App\Services;

use App\Models\BankAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BankAccountService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = BankAccount::query()->with('healthPlan');

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['health_plan_id'])) {
            $query->where('health_plan_id', $filters['health_plan_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('bank_name')->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): BankAccount
    {
        return DB::transaction(function () use ($data) {
            $account = BankAccount::create($data);

            activity()
                ->performedOn($account)
                ->withProperties($data)
                ->log('bank_account_created');

            return $account;
        });
    }

    public function update(BankAccount $bankAccount, array $data): BankAccount
    {
        return DB::transaction(function () use ($bankAccount, $data) {
            $oldValues = $bankAccount->toArray();
            $bankAccount->update($data);

            activity()
                ->performedOn($bankAccount)
                ->withProperties(['old' => $oldValues, 'new' => $data])
                ->log('bank_account_updated');

            return $bankAccount->fresh();
        });
    }

    public function updateBalance(BankAccount $bankAccount, float $newBalance, string $reason): BankAccount
    {
        return DB::transaction(function () use ($bankAccount, $newBalance, $reason) {
            $oldBalance = $bankAccount->current_balance;
            $bankAccount->update(['current_balance' => $newBalance]);

            activity()
                ->performedOn($bankAccount)
                ->withProperties([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'reason' => $reason,
                ])
                ->log('bank_balance_updated');

            return $bankAccount->fresh();
        });
    }

    public function deactivate(BankAccount $bankAccount): BankAccount
    {
        return DB::transaction(function () use ($bankAccount) {
            $bankAccount->update(['is_active' => false]);

            activity()
                ->performedOn($bankAccount)
                ->log('bank_account_deactivated');

            return $bankAccount->fresh();
        });
    }
}
