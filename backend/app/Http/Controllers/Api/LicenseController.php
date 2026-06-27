<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    public function index(): JsonResponse
    {
        $licenses = License::with('organization')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['licenses' => $licenses]);
    }

    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ]);

        $result = $this->licenseService->activate($request->only('key', 'organization_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $result = $this->licenseService->validate($request->input('key'));

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    public function info(): JsonResponse
    {
        $info = $this->licenseService->getLicenseInfo();
        return response()->json($info);
    }

    public function suspend(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->licenseService->suspend($id, $request->input('reason'));
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $result = $this->licenseService->revoke($id, $request->input('reason'));
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function createTrial(Request $request): JsonResponse
    {
        $request->validate([
            'organization_id' => 'required|integer|exists:organizations,id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $result = $this->licenseService->createTrial(
            $request->input('organization_id'),
            $request->input('days', 14)
        );

        return response()->json($result, 201);
    }
}
