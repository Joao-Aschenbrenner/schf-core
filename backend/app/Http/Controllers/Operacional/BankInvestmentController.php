<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreBankInvestmentRequest;
use App\Http\Requests\Operacional\UpdateBankInvestmentRequest;
use App\Http\Requests\Operacional\RedeemBankInvestmentRequest;
use App\Models\Operacional\BankInvestment;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BankInvestmentController extends Controller
{
    public function index()
    {
        $investments = QueryBuilder::for(BankInvestment::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('bank_account_id'),
                AllowedFilter::exact('investment_type'),
            ])
            ->allowedSorts(['start_date', 'maturity_date', 'amount', 'created_at'])
            ->allowedIncludes(['bankAccount', 'legacyConta', 'operations'])
            ->paginate();

        return response()->json($investments);
    }

    public function store(StoreBankInvestmentRequest $request): JsonResponse
    {
        $investment = BankInvestment::create($request->validated());

        return response()->json(['data' => $investment], 201);
    }

    public function show(BankInvestment $bankInvestment): JsonResponse
    {
        return response()->json([
            'data' => $bankInvestment->load('bankAccount', 'legacyConta', 'operations'),
        ]);
    }

    public function update(UpdateBankInvestmentRequest $request, BankInvestment $bankInvestment): JsonResponse
    {
        $bankInvestment->update($request->validated());

        return response()->json(['data' => $bankInvestment->fresh()]);
    }

    public function destroy(BankInvestment $bankInvestment): JsonResponse
    {
        $bankInvestment->delete();

        return response()->json(['message' => 'Investment deleted.']);
    }

    public function redeem(RedeemBankInvestmentRequest $request, BankInvestment $bankInvestment): JsonResponse
    {
        $bankInvestment->update([
            'status' => 'redeemed',
            'redeemed_amount' => $request->input('redeemed_amount', $bankInvestment->amount),
            'redeemed_at' => $request->input('redeemed_at', now()->toDateString()),
        ]);

        return response()->json(['data' => $bankInvestment->fresh()]);
    }
}
