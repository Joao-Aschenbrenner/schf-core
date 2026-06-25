<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierService $supplierService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $this->supplierService->list($request->only([
            'search', 'is_active', 'sort_field', 'sort_direction', 'per_page'
        ]));

        return response()->json($suppliers);
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = $this->supplierService->create($request->validated());

        return response()->json(['data' => $supplier], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json(['data' => $supplier]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier = $this->supplierService->update($supplier, $request->validated());

        return response()->json(['data' => $supplier]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $this->authorize('delete', $supplier);

        $this->supplierService->deactivate($supplier, request('reason', 'Inativação via API'));

        return response()->json(['message' => 'Fornecedor inativado com sucesso.']);
    }

    public function financialSummary(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        return response()->json(['data' => $this->supplierService->getFinancialSummary($supplier)]);
    }
}
