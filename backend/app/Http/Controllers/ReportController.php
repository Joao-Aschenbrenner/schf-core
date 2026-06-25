<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    public function supplierReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payable::class);

        $data = $this->reportService->supplierReport($request->only([
            'supplier_id', 'date_from', 'date_to',
        ]));

        return response()->json(['data' => $data]);
    }

    public function categoryReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payable::class);

        $data = $this->reportService->categoryReport($request->only([
            'date_from', 'date_to',
        ]));

        return response()->json(['data' => $data]);
    }

    public function planReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payable::class);

        $data = $this->reportService->planReport($request->only([
            'health_plan_id', 'date_from', 'date_to',
        ]));

        return response()->json(['data' => $data]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payable::class);

        $data = $this->reportService->cashFlowReport($request->only([
            'date_from', 'date_to',
        ]));

        return response()->json(['data' => $data]);
    }

    public function prestacaoContas(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payable::class);

        $data = $this->reportService->prestacaoContas($request->only([
            'health_plan_id', 'date_from', 'date_to',
        ]));

        return response()->json(['data' => $data]);
    }
}
