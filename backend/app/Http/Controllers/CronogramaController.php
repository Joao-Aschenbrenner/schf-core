<?php

namespace App\Http\Controllers;

use App\Services\CronogramaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronogramaController extends Controller
{
    public function __construct(
        private CronogramaService $cronogramaService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Payable::class);

        $projection = $this->cronogramaService->getProjection($request->only([
            'bank_account_id',
        ]));

        return response()->json(['data' => $projection]);
    }
}
