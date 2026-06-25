<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreCashMovementRequest;
use App\Models\Operacional\CashMovement;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CashMovementController extends Controller
{
    public function index()
    {
        $movements = QueryBuilder::for(CashMovement::class)
            ->allowedFilters([
                AllowedFilter::exact('cash_register_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('category'),
                AllowedFilter::exact('payment_method'),
            ])
            ->allowedSorts(['created_at', 'amount'])
            ->allowedIncludes(['cashRegister', 'supplier', 'payable', 'receivable', 'createdBy'])
            ->paginate();

        return response()->json($movements);
    }

    public function store(StoreCashMovementRequest $request): JsonResponse
    {
        $movement = CashMovement::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        return response()->json(['data' => $movement->load('cashRegister', 'supplier', 'createdBy')], 201);
    }

    public function show(CashMovement $cashMovement): JsonResponse
    {
        return response()->json([
            'data' => $cashMovement->load('cashRegister', 'supplier', 'payable', 'receivable', 'createdBy'),
        ]);
    }
}
