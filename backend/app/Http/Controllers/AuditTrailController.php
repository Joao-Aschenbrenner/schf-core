<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function __construct(
        private AuditTrailService $auditTrailService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditTrail::class);

        $trails = $this->auditTrailService->list($request->only([
            'model_type', 'model_id', 'user_id', 'action',
            'date_from', 'date_to', 'per_page'
        ]));

        return response()->json($trails);
    }

    public function modelTimeline(string $modelType, int $modelId): JsonResponse
    {
        $this->authorize('viewAny', AuditTrail::class);

        $timeline = $this->auditTrailService->getModelTimeline($modelType, $modelId);

        return response()->json(['data' => $timeline]);
    }
}
