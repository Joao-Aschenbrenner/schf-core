<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreCashRegisterRequest;
use App\Http\Requests\Operacional\CloseCashRegisterRequest;
use App\Models\Operacional\CashRegister;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CashRegisterController extends Controller
{
    public function index()
    {
        $registers = QueryBuilder::for(CashRegister::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('operator'),
                AllowedFilter::scope('register_date_from'),
                AllowedFilter::scope('register_date_to'),
            ])
            ->allowedSorts(['register_date', 'created_at'])
            ->allowedIncludes(['movements', 'closedBy'])
            ->paginate();

        return response()->json($registers);
    }

    public function store(StoreCashRegisterRequest $request): JsonResponse
    {
        $cashRegister = CashRegister::create(array_merge(
            $request->validated(),
            [
                'status' => 'open',
                'total_credits' => 0,
                'total_debits' => 0,
            ]
        ));

        return response()->json(['data' => $cashRegister], 201);
    }

    public function show(CashRegister $cashRegister): JsonResponse
    {
        return response()->json([
            'data' => $cashRegister->load('movements', 'closedBy'),
        ]);
    }

    public function close(CloseCashRegisterRequest $request, CashRegister $cashRegister): JsonResponse
    {
        $cashRegister->load('movements');
        $totalCredits = $cashRegister->movements->sum('amount');

        $cashRegister->update([
            'status' => 'closed',
            'closing_balance' => $request->input('closing_balance', $cashRegister->opening_balance + $cashRegister->total_credits - $cashRegister->total_debits),
            'closed_by' => auth()->id(),
            'closed_at' => now(),
            'notes' => $request->input('notes', $cashRegister->notes),
        ]);

        return response()->json(['data' => $cashRegister->fresh()->load('movements', 'closedBy')]);
    }
}
