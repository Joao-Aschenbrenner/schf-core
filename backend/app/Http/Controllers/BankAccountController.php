<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Services\BankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(
        private BankAccountService $bankAccountService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BankAccount::class);

        $accounts = $this->bankAccountService->list($request->only([
            'is_active', 'health_plan_id', 'type', 'per_page'
        ]));

        return response()->json($accounts);
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $this->authorize('create', BankAccount::class);

        $account = $this->bankAccountService->create($request->validated());

        return response()->json(['data' => $account], 201);
    }

    public function show(BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('view', $bankAccount);

        return response()->json(['data' => $bankAccount->load('healthPlan')]);
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('update', $bankAccount);

        $account = $this->bankAccountService->update($bankAccount, $request->validated());

        return response()->json(['data' => $account]);
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $this->authorize('delete', $bankAccount);

        $this->bankAccountService->deactivate($bankAccount);

        return response()->json(['message' => 'Conta bancária inativada com sucesso.']);
    }
}
