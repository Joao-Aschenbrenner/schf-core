<?php

namespace App\Services;

use App\Models\ContraEntry;
use App\Models\Payable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayableService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Payable::query()->with(['supplier', 'nfe', 'healthPlan', 'bankAccount', 'expenseCategory']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('bar_code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['bank_account_id'])) {
            $query->where('bank_account_id', $filters['bank_account_id']);
        }

        if (isset($filters['due_from'])) {
            $query->where('due_date', '>=', $filters['due_from']);
        }

        if (isset($filters['due_to'])) {
            $query->where('due_date', '<=', $filters['due_to']);
        }

        $sortField = $filters['sort_field'] ?? 'due_date';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Payable
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = $data['status'] ?? 'pending';
            $data['created_by'] = auth()->id() ?? 1;

            $payable = Payable::create($data);

            activity()
                ->performedOn($payable)
                ->withProperties($data)
                ->log('payable_created');

            return $payable;
        });
    }

    public function update(Payable $payable, array $data): Payable
    {
        return DB::transaction(function () use ($payable, $data) {
            $oldValues = $payable->toArray();
            $payable->update($data);

            activity()
                ->performedOn($payable)
                ->withProperties(['old' => $oldValues, 'new' => $data])
                ->log('payable_updated');

            return $payable->fresh();
        });
    }

    public function approve(Payable $payable): Payable
    {
        return DB::transaction(function () use ($payable) {
            $payable->update([
                'status' => 'approved',
                'approved_by' => auth()->id() ?? 1,
                'approved_at' => now(),
            ]);

            activity()
                ->performedOn($payable)
                ->log('payable_approved');

            return $payable->fresh();
        });
    }

    public function pay(Payable $payable, array $paymentData): Payable
    {
        return DB::transaction(function () use ($payable, $paymentData) {
            $payable->update([
                'status' => 'paid',
                'paid_amount' => $paymentData['paid_amount'] ?? $payable->amount,
                'discount' => $paymentData['discount'] ?? 0,
                'interest' => $paymentData['interest'] ?? 0,
                'payment_date' => $paymentData['payment_date'] ?? now()->toDateString(),
                'paid_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? $payable->payment_method,
                'receipt_number' => $paymentData['receipt_number'] ?? null,
            ]);

            activity()
                ->performedOn($payable)
                ->withProperties($paymentData)
                ->log('payable_paid');

            return $payable->fresh();
        });
    }

    public function cancel(Payable $payable, string $reason): ContraEntry
    {
        return DB::transaction(function () use ($payable, $reason) {
            $payable->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => auth()->id() ?? 1,
            ]);

            $contraEntry = ContraEntry::create([
                'model_type' => Payable::class,
                'model_id' => $payable->id,
                'type' => 'reversal',
                'amount' => $payable->paid_amount ?? $payable->amount,
                'original_amount' => $payable->amount,
                'reason' => $reason,
                'created_by' => auth()->id() ?? 1,
            ]);

            activity()
                ->performedOn($payable)
                ->withProperties(['reason' => $reason, 'contra_entry_id' => $contraEntry->id])
                ->log('payable_cancelled');

            return $contraEntry;
        });
    }

    public function markOverdue(): int
    {
        return Payable::query()
            ->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }

    public function getAgingReport(): array
    {
        $buckets = [
            'current' => [0, 30],
            '31_60' => [31, 60],
            '61_90' => [61, 90],
            '91_120' => [91, 120],
            'over_120' => [121, 9999],
        ];

        $result = [];
        foreach ($buckets as $label => [$from, $to]) {
            $query = Payable::query()
                ->whereIn('status', ['pending', 'overdue']);

            if ($to === 9999) {
                $query->where('due_date', '<=', now()->subDays($from)->toDateString());
            } else {
                $query->whereBetween('due_date', [
                    now()->subDays($to)->toDateString(),
                    now()->subDays($from)->toDateString(),
                ]);
            }

            $result[$label] = [
                'count' => $query->count(),
                'total' => (clone $query)->sum('amount'),
            ];
        }

        return $result;
    }
}
