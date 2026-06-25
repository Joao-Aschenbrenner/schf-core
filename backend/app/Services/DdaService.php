<?php

namespace App\Services;

use App\Models\Dda;
use App\Models\Payable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DdaService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Dda::query()->with(['supplier', 'nfe', 'payable']);

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payer_name', 'like', "%{$search}%")
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

        if (isset($filters['bank_code'])) {
            $query->where('bank_code', $filters['bank_code']);
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

    public function import(array $ddasData): array
    {
        return DB::transaction(function () use ($ddasData) {
            $imported = [];
            foreach ($ddasData as $ddaData) {
                $existing = Dda::where('bar_code', $ddaData['bar_code'])->first();
                if ($existing) {
                    $imported[] = $existing;
                    continue;
                }

                $ddaData['status'] = 'pending';
                $ddaData['imported_at'] = now();

                $dda = Dda::create($ddaData);

                $this->tryAutoMatch($dda);

                activity()
                    ->performedOn($dda)
                    ->withProperties($ddaData)
                    ->log('dda_imported');

                $imported[] = $dda;
            }

            return $imported;
        });
    }

    public function linkToPayable(Dda $dda, Payable $payable): Dda
    {
        return DB::transaction(function () use ($dda, $payable) {
            $dda->update([
                'payable_id' => $payable->id,
                'supplier_id' => $payable->supplier_id,
                'nfe_id' => $payable->nfe_id,
                'status' => 'linked',
            ]);

            activity()
                ->performedOn($dda)
                ->withProperties(['payable_id' => $payable->id])
                ->log('dda_linked_to_payable');

            return $dda->fresh();
        });
    }

    public function reject(Dda $dda, string $reason): Dda
    {
        return DB::transaction(function () use ($dda, $reason) {
            $dda->update(['status' => 'rejected', 'notes' => $reason]);

            activity()
                ->performedOn($dda)
                ->withProperties(['reason' => $reason])
                ->log('dda_rejected');

            return $dda->fresh();
        });
    }

    private function tryAutoMatch(Dda $dda): void
    {
        if (empty($dda->payer_cnpj) && empty($dda->payer_cpf)) {
            return;
        }

        $cnpj = $dda->payer_cnpj;
        $cpf = $dda->payer_cpf;

        $supplier = \App\Models\Supplier::where('cnpj', $cnpj)
            ->orWhere('cpf', $cpf)
            ->first();

        if ($supplier) {
            $dda->update(['supplier_id' => $supplier->id]);

            $payable = Payable::where('supplier_id', $supplier->id)
                ->where('status', 'pending')
                ->where('amount', $dda->amount)
                ->first();

            if ($payable) {
                $dda->update([
                    'payable_id' => $payable->id,
                    'nfe_id' => $payable->nfe_id,
                    'status' => 'linked',
                ]);

                activity()
                    ->performedOn($dda)
                    ->withProperties(['payable_id' => $payable->id, 'auto_matched' => true])
                    ->log('dda_auto_matched');
            }
        }
    }
}
