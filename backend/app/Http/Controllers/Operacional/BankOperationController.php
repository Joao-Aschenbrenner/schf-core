<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreBankOperationRequest;
use App\Models\BankAccount;
use App\Models\Operacional\BankOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BankOperationController extends Controller
{
    public function index()
    {
        $operations = QueryBuilder::for(BankOperation::class)
            ->allowedFilters([
                AllowedFilter::exact('bank_account_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::scope('operation_date_from'),
                AllowedFilter::scope('operation_date_to'),
            ])
            ->allowedSorts(['operation_date', 'amount', 'created_at'])
            ->allowedIncludes(['bankAccount', 'payable', 'receivable', 'bankInvestment', 'createdBy'])
            ->paginate();

        return response()->json($operations);
    }

    public function store(StoreBankOperationRequest $request): JsonResponse
    {
        $operation = BankOperation::create(array_merge(
            $request->validated(),
            ['created_by' => auth()->id()]
        ));

        return response()->json(['data' => $operation->load('bankAccount', 'createdBy')], 201);
    }

    public function show(BankOperation $bankOperation): JsonResponse
    {
        return response()->json([
            'data' => $bankOperation->load('bankAccount', 'payable', 'receivable', 'bankInvestment', 'createdBy'),
        ]);
    }

    public function extrato(Request $request): JsonResponse
    {
        $request->validate([
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'data_from' => 'required|date',
            'data_to' => 'required|date|after_or_equal:data_from',
        ]);

        $bankAccountId = $request->input('bank_account_id');
        $dataFrom = $request->input('data_from');
        $dataTo = $request->input('data_to');

        $bankAccount = BankAccount::findOrFail($bankAccountId);

        $saldoInicial = $bankAccount->current_balance;

        $operacoes = BankOperation::where('bank_account_id', $bankAccountId)
            ->whereBetween('operation_date', [$dataFrom, $dataTo])
            ->orderBy('operation_date')
            ->orderBy('id')
            ->get();

        $totalCreditos = $operacoes->where('type', 'credit')->sum('amount');
        $totalDebitos = $operacoes->where('type', 'debit')->sum('amount');
        $saldoFinal = $saldoInicial + $totalCreditos - $totalDebitos;

        return response()->json([
            'data' => [
                'bank_account' => $bankAccount,
                'saldo_inicial' => $saldoInicial,
                'operacoes' => $operacoes,
                'total_creditos' => round($totalCreditos, 2),
                'total_debitos' => round($totalDebitos, 2),
                'saldo_final' => round($saldoFinal, 2),
                'periodo' => [
                    'data_from' => $dataFrom,
                    'data_to' => $dataTo,
                ],
            ],
        ]);
    }
}
