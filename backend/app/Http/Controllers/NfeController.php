<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNfeRequest;
use App\Http\Requests\UpdateNfeRequest;
use App\Models\Nfe;
use App\Services\NfeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfeController extends Controller
{
    public function __construct(
        private NfeService $nfeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Nfe::class);

        $nfes = $this->nfeService->list($request->only([
            'search', 'status', 'supplier_id', 'health_plan_id',
            'expense_category_id', 'date_from', 'date_to',
            'sort_field', 'sort_direction', 'per_page'
        ]));

        return response()->json($nfes);
    }

    public function store(StoreNfeRequest $request): JsonResponse
    {
        $this->authorize('create', Nfe::class);

        $nfe = $this->nfeService->create($request->validated());

        return response()->json(['data' => $nfe->load('items', 'supplier')], 201);
    }

    public function show(Nfe $nfe): JsonResponse
    {
        $this->authorize('view', $nfe);

        return response()->json(['data' => $nfe->load('items', 'supplier', 'healthPlan', 'payables')]);
    }

    public function update(UpdateNfeRequest $request, Nfe $nfe): JsonResponse
    {
        $this->authorize('update', $nfe);

        $nfe = $this->nfeService->update($nfe, $request->validated());

        return response()->json(['data' => $nfe]);
    }

    public function destroy(Nfe $nfe): JsonResponse
    {
        $this->authorize('delete', $nfe);

        $this->nfeService->cancel($nfe, request('reason', 'Cancelamento via API'));

        return response()->json(['message' => 'NFe cancelada com sucesso.']);
    }

    public function confirm(Nfe $nfe): JsonResponse
    {
        $this->authorize('update', $nfe);

        $nfe = $this->nfeService->confirm($nfe);

        return response()->json(['data' => $nfe]);
    }

    public function generatePayable(Nfe $nfe, Request $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Payable::class);

        $payable = $this->nfeService->generatePayable($nfe, $request->all());

        return response()->json(['data' => $payable], 201);
    }
}
