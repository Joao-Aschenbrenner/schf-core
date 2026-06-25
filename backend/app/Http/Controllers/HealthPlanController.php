<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthPlanRequest;
use App\Http\Requests\UpdateHealthPlanRequest;
use App\Http\Requests\StoreResourcePlanRequest;
use App\Models\HealthPlan;
use App\Models\ResourcePlan;
use App\Services\HealthPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthPlanController extends Controller
{
    public function __construct(
        private HealthPlanService $healthPlanService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HealthPlan::class);

        $plans = $this->healthPlanService->list($request->only([
            'search', 'type', 'is_active', 'per_page'
        ]));

        return response()->json($plans);
    }

    public function store(StoreHealthPlanRequest $request): JsonResponse
    {
        $this->authorize('create', HealthPlan::class);

        $plan = $this->healthPlanService->create($request->validated());

        return response()->json(['data' => $plan], 201);
    }

    public function show(HealthPlan $healthPlan): JsonResponse
    {
        $this->authorize('view', $healthPlan);

        return response()->json(['data' => $healthPlan->load('resourcePlans')]);
    }

    public function update(UpdateHealthPlanRequest $request, HealthPlan $healthPlan): JsonResponse
    {
        $this->authorize('update', $healthPlan);

        $plan = $this->healthPlanService->update($healthPlan, $request->validated());

        return response()->json(['data' => $plan]);
    }

    public function destroy(HealthPlan $healthPlan): JsonResponse
    {
        $this->authorize('delete', $healthPlan);

        $this->healthPlanService->deactivate($healthPlan, request('reason', 'Inativação via API'));

        return response()->json(['message' => 'Convênio inativado com sucesso.']);
    }

    public function addResourcePlan(StoreResourcePlanRequest $request, HealthPlan $healthPlan): JsonResponse
    {
        $this->authorize('update', $healthPlan);

        $resourcePlan = $this->healthPlanService->addResourcePlan($healthPlan, $request->validated());

        return response()->json(['data' => $resourcePlan], 201);
    }

    public function balance(HealthPlan $healthPlan): JsonResponse
    {
        $this->authorize('view', $healthPlan);

        return response()->json(['data' => $this->healthPlanService->checkBalance($healthPlan)]);
    }
}
