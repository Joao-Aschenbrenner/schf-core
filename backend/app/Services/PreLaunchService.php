<?php

namespace App\Services;

use App\Models\PreLaunch;
use App\Models\Payable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PreLaunchService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = PreLaunch::query()->with(['supplier', 'healthPlan', 'expenseCategory', 'bankAccount']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['health_plan_id'])) {
            $query->where('health_plan_id', $filters['health_plan_id']);
        }

        if (isset($filters['expected_from'])) {
            $query->where('expected_date', '>=', $filters['expected_from']);
        }

        if (isset($filters['expected_to'])) {
            $query->where('expected_date', '<=', $filters['expected_to']);
        }

        $sortField = $filters['sort_field'] ?? 'expected_date';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): PreLaunch
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = $data['status'] ?? 'estimated';
            $data['created_by'] = auth()->id() ?? 1;

            $preLaunch = PreLaunch::create($data);

            activity()
                ->performedOn($preLaunch)
                ->withProperties($data)
                ->log('pre_launch_created');

            return $preLaunch;
        });
    }

    public function update(PreLaunch $preLaunch, array $data): PreLaunch
    {
        return DB::transaction(function () use ($preLaunch, $data) {
            $oldValues = $preLaunch->toArray();
            $preLaunch->update($data);

            activity()
                ->performedOn($preLaunch)
                ->withProperties(['old' => $oldValues, 'new' => $data])
                ->log('pre_launch_updated');

            return $preLaunch->fresh();
        });
    }

    public function confirm(PreLaunch $preLaunch, float $actualAmount, ?string $actualDate = null): Payable
    {
        return DB::transaction(function () use ($preLaunch, $actualAmount, $actualDate) {
            $preLaunch->update([
                'status' => 'confirmed',
                'actual_amount' => $actualAmount,
                'actual_date' => $actualDate ?? now()->toDateString(),
            ]);

            $payable = Payable::create([
                'description' => $preLaunch->description,
                'supplier_id' => $preLaunch->supplier_id,
                'health_plan_id' => $preLaunch->health_plan_id,
                'resource_plan_id' => $preLaunch->resource_plan_id,
                'expense_category_id' => $preLaunch->expense_category_id,
                'bank_account_id' => $preLaunch->bank_account_id,
                'amount' => $actualAmount,
                'due_date' => $preLaunch->expected_date,
                'status' => 'pending',
                'created_by' => auth()->id() ?? 1,
            ]);

            $preLaunch->update(['payable_id' => $payable->id]);

            activity()
                ->performedOn($preLaunch)
                ->withProperties(['payable_id' => $payable->id, 'actual_amount' => $actualAmount])
                ->log('pre_launch_confirmed');

            return $payable;
        });
    }

    public function cancel(PreLaunch $preLaunch, string $reason): PreLaunch
    {
        return DB::transaction(function () use ($preLaunch, $reason) {
            $preLaunch->update(['status' => 'cancelled', 'notes' => $reason]);

            activity()
                ->performedOn($preLaunch)
                ->withProperties(['reason' => $reason])
                ->log('pre_launch_cancelled');

            return $preLaunch->fresh();
        });
    }
}
