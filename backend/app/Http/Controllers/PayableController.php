<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayableRequest;
use App\Http\Requests\UpdatePayableRequest;
use App\Models\Payable;
use App\Services\PayableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function __construct(
        private PayableService $payableService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payable::class);

        $payables = $this->payableService->list($request->only([
            'search', 'status', 'supplier_id', 'bank_account_id',
            'due_from', 'due_to',
            'sort_field', 'sort_direction', 'per_page'
        ]));

        return response()->json($payables);
    }

    public function store(StorePayableRequest $request): JsonResponse
    {
        $this->authorize('create', Payable::class);

        $payable = $this->payableService->create($request->validated());

        return response()->json(['data' => $payable], 201);
    }

    public function show(Payable $payable): JsonResponse
    {
        $this->authorize('view', $payable);

        return response()->json(['data' => $payable->load('supplier', 'nfe', 'healthPlan', 'bankAccount')]);
    }

    public function update(UpdatePayableRequest $request, Payable $payable): JsonResponse
    {
        $this->authorize('update', $payable);

        $payable = $this->payableService->update($payable, $request->validated());

        return response()->json(['data' => $payable]);
    }

    public function destroy(Payable $payable): JsonResponse
    {
        $this->authorize('delete', $payable);

        $reason = request('reason', 'Cancelamento via API');
        $this->payableService->cancel($payable, $reason);

        return response()->json(['message' => 'Título cancelado. Contra-entrada gerada.']);
    }

    public function approve(Payable $payable): JsonResponse
    {
        $this->authorize('approve', $payable);

        $payable = $this->payableService->approve($payable);

        return response()->json(['data' => $payable]);
    }

    public function pay(Payable $payable, Request $request): JsonResponse
    {
        $this->authorize('pay', $payable);

        $request->validate([
            'paid_amount' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'interest' => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'payment_method' => 'sometimes|string',
            'receipt_number' => 'sometimes|string',
        ]);

        $payable = $this->payableService->pay($payable, $request->all());

        return response()->json(['data' => $payable]);
    }

    public function agingReport(): JsonResponse
    {
        $this->authorize('viewAny', Payable::class);

        return response()->json(['data' => $this->payableService->getAgingReport()]);
    }
}
