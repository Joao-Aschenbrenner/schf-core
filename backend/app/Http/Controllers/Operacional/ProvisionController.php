<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreProvisionRequest;
use App\Http\Requests\Operacional\UpdateProvisionRequest;
use App\Models\Operacional\Provision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProvisionController extends Controller
{
    public function index()
    {
        $provisions = QueryBuilder::for(Provision::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('supplier_id'),
                AllowedFilter::exact('bank_account_id'),
                AllowedFilter::exact('provision_type'),
                AllowedFilter::scope('overdue'),
                AllowedFilter::scope('due_from'),
                AllowedFilter::scope('due_to'),
            ])
            ->allowedSorts(['due_date', 'amount', 'created_at'])
            ->allowedIncludes(['supplier', 'bankAccount', 'legacyNota', 'createdBy'])
            ->paginate();

        return response()->json($provisions);
    }

    public function store(StoreProvisionRequest $request): JsonResponse
    {
        $provision = Provision::create($request->validated());

        return response()->json(['data' => $provision], 201);
    }

    public function show(Provision $provision): JsonResponse
    {
        return response()->json([
            'data' => $provision->load('supplier', 'bankAccount', 'legacyNota', 'createdBy'),
        ]);
    }

    public function update(UpdateProvisionRequest $request, Provision $provision): JsonResponse
    {
        $provision->update($request->validated());

        return response()->json(['data' => $provision->fresh()]);
    }

    public function confirm(Provision $provision): JsonResponse
    {
        $provision->update(['status' => 'confirmed']);

        return response()->json(['data' => $provision->fresh()]);
    }

    public function pay(Request $request, Provision $provision): JsonResponse
    {
        $request->validate([
            'paid_amount' => 'sometimes|numeric|min:0',
            'paid_at' => 'sometimes|date',
        ]);

        $provision->update([
            'status' => 'paid',
            'paid_amount' => $request->input('paid_amount', $provision->amount),
            'paid_at' => $request->input('paid_at', now()->toDateString()),
        ]);

        return response()->json(['data' => $provision->fresh()]);
    }

    public function cancel(Provision $provision): JsonResponse
    {
        $provision->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Provision cancelled.']);
    }
}
