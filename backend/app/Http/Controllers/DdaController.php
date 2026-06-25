<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDdaRequest;
use App\Models\Dda;
use App\Models\Payable;
use App\Services\DdaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DdaController extends Controller
{
    public function __construct(
        private DdaService $ddaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Dda::class);

        $ddas = $this->ddaService->list($request->only([
            'search', 'status', 'supplier_id', 'bank_code',
            'due_from', 'due_to',
            'sort_field', 'sort_direction', 'per_page'
        ]));

        return response()->json($ddas);
    }

    public function store(StoreDdaRequest $request): JsonResponse
    {
        $this->authorize('create', Dda::class);

        $ddas = $this->ddaService->import([$request->validated()]);

        return response()->json(['data' => $ddas[0]], 201);
    }

    public function show(Dda $dda): JsonResponse
    {
        $this->authorize('view', $dda);

        return response()->json(['data' => $dda->load('supplier', 'nfe', 'payable')]);
    }

    public function linkToPayable(Dda $dda, Request $request): JsonResponse
    {
        $this->authorize('update', $dda);

        $request->validate(['payable_id' => 'required|exists:payables,id']);

        $payable = Payable::findOrFail($request->payable_id);
        $dda = $this->ddaService->linkToPayable($dda, $payable);

        return response()->json(['data' => $dda]);
    }

    public function reject(Dda $dda, Request $request): JsonResponse
    {
        $this->authorize('update', $dda);

        $request->validate(['reason' => 'required|string|max:500']);

        $dda = $this->ddaService->reject($dda, $request->reason);

        return response()->json(['data' => $dda]);
    }

    public function bulkImport(Request $request): JsonResponse
    {
        $this->authorize('create', Dda::class);

        $request->validate(['ddas' => 'required|array|min:1']);

        $ddas = $this->ddaService->import($request->ddas);

        return response()->json(['data' => $ddas, 'count' => count($ddas)], 201);
    }
}
