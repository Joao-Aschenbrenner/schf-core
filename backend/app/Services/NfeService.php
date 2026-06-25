<?php

namespace App\Services;

use App\Models\Nfe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NfeService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Nfe::query()->with(['supplier', 'healthPlan', 'expenseCategory']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nfe_number', 'like', "%{$search}%")
                  ->orWhere('nfe_key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['health_plan_id'])) {
            $query->where('health_plan_id', $filters['health_plan_id']);
        }

        if (isset($filters['expense_category_id'])) {
            $query->where('expense_category_id', $filters['expense_category_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('emission_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('emission_date', '<=', $filters['date_to']);
        }

        $sortField = $filters['sort_field'] ?? 'emission_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Nfe
    {
        return DB::transaction(function () use ($data) {
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            $nfe = Nfe::create($data);

            foreach ($itemsData as $itemData) {
                $nfe->items()->create($itemData);
            }

            $nfe->load('items');

            activity()
                ->performedOn($nfe)
                ->withProperties($nfe->toArray())
                ->log('nfe_created');

            return $nfe;
        });
    }

    public function update(Nfe $nfe, array $data): Nfe
    {
        return DB::transaction(function () use ($nfe, $data) {
            $oldValues = $nfe->toArray();
            $nfe->update($data);

            activity()
                ->performedOn($nfe)
                ->withProperties(['old' => $oldValues, 'new' => $data])
                ->log('nfe_updated');

            return $nfe->fresh();
        });
    }

    public function cancel(Nfe $nfe, string $reason): Nfe
    {
        return DB::transaction(function () use ($nfe, $reason) {
            $nfe->update(['status' => 'cancelled']);

            activity()
                ->performedOn($nfe)
                ->withProperties(['reason' => $reason])
                ->log('nfe_cancelled');

            return $nfe->fresh();
        });
    }

    public function confirm(Nfe $nfe): Nfe
    {
        return DB::transaction(function () use ($nfe) {
            $nfe->update(['status' => 'confirmed']);

            activity()
                ->performedOn($nfe)
                ->log('nfe_confirmed');

            return $nfe->fresh();
        });
    }

    public function generatePayable(Nfe $nfe, array $payableData): \App\Models\Payable
    {
        return DB::transaction(function () use ($nfe, $payableData) {
            $payable = $nfe->payables()->create(array_merge($payableData, [
                'supplier_id' => $nfe->supplier_id,
                'health_plan_id' => $nfe->health_plan_id,
                'resource_plan_id' => $nfe->resource_plan_id,
                'expense_category_id' => $nfe->expense_category_id,
                'amount' => $payableData['amount'] ?? $nfe->total_value,
                'description' => $payableData['description'] ?? "NFe {$nfe->nfe_number}",
                'status' => 'pending',
                'created_by' => auth()->id() ?? 1,
            ]));

            activity()
                ->performedOn($payable)
                ->withProperties(['nfe_id' => $nfe->id])
                ->log('payable_generated_from_nfe');

            return $payable;
        });
    }
}
