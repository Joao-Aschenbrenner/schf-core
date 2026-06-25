<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePreLaunchRequest;
use App\Models\PreLaunch;
use App\Services\PreLaunchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreLaunchController extends Controller
{
    public function __construct(
        private PreLaunchService $preLaunchService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PreLaunch::class);

        $preLaunches = $this->preLaunchService->list($request->only([
            'search', 'status', 'type', 'supplier_id', 'health_plan_id',
            'expected_from', 'expected_to',
            'sort_field', 'sort_direction', 'per_page'
        ]));

        return response()->json($preLaunches);
    }

    public function store(StorePreLaunchRequest $request): JsonResponse
    {
        $this->authorize('create', PreLaunch::class);

        $preLaunch = $this->preLaunchService->create($request->validated());

        return response()->json(['data' => $preLaunch], 201);
    }

    public function show(PreLaunch $preLaunch): JsonResponse
    {
        $this->authorize('view', $preLaunch);

        return response()->json(['data' => $preLaunch->load('supplier', 'healthPlan', 'expenseCategory', 'bankAccount')]);
    }

    public function update(StorePreLaunchRequest $request, PreLaunch $preLaunch): JsonResponse
    {
        $this->authorize('update', $preLaunch);

        $preLaunch = $this->preLaunchService->update($preLaunch, $request->validated());

        return response()->json(['data' => $preLaunch]);
    }

    public function confirm(PreLaunch $preLaunch, Request $request): JsonResponse
    {
        $this->authorize('update', $preLaunch);

        $request->validate([
            'actual_amount' => 'required|numeric|min:0',
            'actual_date' => 'sometimes|date',
        ]);

        $payable = $this->preLaunchService->confirm(
            $preLaunch,
            $request->actual_amount,
            $request->actual_date
        );

        return response()->json(['data' => $payable], 201);
    }

    public function cancel(PreLaunch $preLaunch, Request $request): JsonResponse
    {
        $this->authorize('update', $preLaunch);

        $request->validate(['reason' => 'required|string|max:500']);

        $preLaunch = $this->preLaunchService->cancel($preLaunch, $request->reason);

        return response()->json(['data' => $preLaunch]);
    }
}
