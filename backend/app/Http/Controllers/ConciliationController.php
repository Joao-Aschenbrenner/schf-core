<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConciliateRequest;
use App\Models\BankStatement;
use App\Models\BankStatementItem;
use App\Services\ConciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConciliationController extends Controller
{
    public function __construct(
        private ConciliationService $conciliationService
    ) {}

    public function indexStatements(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BankStatement::class);

        $statements = $this->conciliationService->listStatements($request->only([
            'bank_account_id', 'status', 'date_from', 'date_to',
            'sort_field', 'sort_direction', 'per_page'
        ]));

        return response()->json($statements);
    }

    public function showStatement(BankStatement $statement): JsonResponse
    {
        $this->authorize('view', $statement);

        return response()->json(['data' => $statement->load('bankAccount', 'items')]);
    }

    public function importOfx(Request $request): JsonResponse
    {
        $this->authorize('create', BankStatement::class);

        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'ofx_content' => 'required|string',
        ]);

        $statement = $this->conciliationService->importOfx(
            $request->bank_account_id,
            $request->ofx_content
        );

        return response()->json(['data' => $statement], 201);
    }

    public function conciliate(ConciliateRequest $request): JsonResponse
    {
        $this->authorize('update', BankStatementItem::class);

        $item = BankStatementItem::findOrFail($request->statement_item_id);
        $payable = \App\Models\Payable::findOrFail($request->payable_id);

        $item = $this->conciliationService->conciliateItem($item, $payable);

        return response()->json(['data' => $item]);
    }

    public function unmatch(BankStatementItem $item): JsonResponse
    {
        $this->authorize('update', $item);

        $item = $this->conciliationService->unmatchItem($item);

        return response()->json(['data' => $item]);
    }

    public function autoMatch(BankStatement $statement): JsonResponse
    {
        $this->authorize('update', $statement);

        $matched = $this->conciliationService->autoMatch($statement);

        return response()->json(['data' => ['matched_count' => $matched]]);
    }
}
