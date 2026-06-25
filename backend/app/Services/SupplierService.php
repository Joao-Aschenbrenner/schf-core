<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Supplier::query()->withCount('payables');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%")
                  ->orWhere('cpf', 'like', "%{$search}%")
                  ->orWhere('trade_name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortField = $filters['sort_field'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::create($data);

            activity()
                ->performedOn($supplier)
                ->withProperties($data)
                ->log('supplier_created');

            return $supplier;
        });
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $oldValues = $supplier->toArray();
            $supplier->update($data);

            activity()
                ->performedOn($supplier)
                ->withProperties([
                    'old' => $oldValues,
                    'new' => $data,
                ])
                ->log('supplier_updated');

            return $supplier->fresh();
        });
    }

    public function deactivate(Supplier $supplier, string $reason): Supplier
    {
        return DB::transaction(function () use ($supplier, $reason) {
            $supplier->update(['is_active' => false]);

            activity()
                ->performedOn($supplier)
                ->withProperties(['reason' => $reason])
                ->log('supplier_deactivated');

            return $supplier->fresh();
        });
    }

    public function activate(Supplier $supplier): Supplier
    {
        return DB::transaction(function () use ($supplier) {
            $supplier->update(['is_active' => true]);

            activity()
                ->performedOn($supplier)
                ->log('supplier_activated');

            return $supplier->fresh();
        });
    }

    public function getFinancialSummary(Supplier $supplier): array
    {
        $totalPaid = $supplier->payables()->where('status', 'paid')->sum('amount');
        $totalPending = $supplier->payables()->where('status', 'pending')->sum('amount');
        $lastPayment = $supplier->payables()->where('status', 'paid')->latest('paid_at')->first();
        $maxPayment = $supplier->payables()->where('status', 'paid')->max('amount');

        return [
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'total_all' => $totalPaid + $totalPending,
            'last_payment_date' => $lastPayment?->paid_at,
            'max_payment_amount' => $maxPayment,
            'payments_count' => $supplier->payables()->where('status', 'paid')->count(),
        ];
    }
}
