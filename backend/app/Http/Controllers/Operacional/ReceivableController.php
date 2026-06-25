<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operacional\StoreReceivableRequest;
use App\Http\Requests\Operacional\UpdateReceivableRequest;
use App\Models\Operacional\Receivable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReceivableController extends Controller
{
    public function index()
    {
        $receivables = QueryBuilder::for(Receivable::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('supplier_id'),
                AllowedFilter::exact('bank_account_id'),
                AllowedFilter::scope('overdue'),
                AllowedFilter::scope('due_from'),
                AllowedFilter::scope('due_to'),
            ])
            ->allowedSorts(['due_date', 'amount', 'created_at'])
            ->allowedIncludes(['supplier', 'bankAccount', 'legacyNota', 'createdBy', 'approvedBy'])
            ->paginate();

        return response()->json($receivables);
    }

    public function store(StoreReceivableRequest $request): JsonResponse
    {
        $receivable = Receivable::create($request->validated());

        return response()->json(['data' => $receivable], 201);
    }

    public function show(Receivable $receivable): JsonResponse
    {
        return response()->json([
            'data' => $receivable->load('supplier', 'bankAccount', 'legacyNota', 'createdBy', 'approvedBy'),
        ]);
    }

    public function update(UpdateReceivableRequest $request, Receivable $receivable): JsonResponse
    {
        $receivable->update($request->validated());

        return response()->json(['data' => $receivable->fresh()]);
    }

    public function approve(Receivable $receivable): JsonResponse
    {
        $receivable->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['data' => $receivable->fresh()]);
    }

    public function receive(Request $request, Receivable $receivable): JsonResponse
    {
        $request->validate([
            'received_amount' => 'sometimes|numeric|min:0',
            'receipt_date' => 'sometimes|date',
            'payment_method' => 'sometimes|string|max:50',
        ]);

        $receivable->update([
            'status' => 'received',
            'received_amount' => $request->input('received_amount', $receivable->amount),
            'receipt_date' => $request->input('receipt_date', now()->toDateString()),
            'payment_method' => $request->input('payment_method', $receivable->payment_method),
        ]);

        return response()->json(['data' => $receivable->fresh()]);
    }

    public function destroy(Receivable $receivable): JsonResponse
    {
        $receivable->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Receivable cancelled.']);
    }
}
