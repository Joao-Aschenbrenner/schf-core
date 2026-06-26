<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationService $service
    ) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['search', 'is_active', 'sort_field', 'sort_direction', 'per_page']);
        $organizations = $this->service->list($filters);

        return response()->json($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->service->create($request->validated());

        return response()->json($organization, 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        return response()->json($organization);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $organization = $this->service->update($organization, $request->validated());

        return response()->json($organization);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->json(['message' => 'Organization deleted']);
    }

    public function deactivate(Organization $organization): JsonResponse
    {
        $this->authorize('deactivate', $organization);

        $organization = $this->service->deactivate($organization, request('reason'));

        return response()->json($organization);
    }

    public function activate(Organization $organization): JsonResponse
    {
        $this->authorize('activate', $organization);

        $organization = $this->service->activate($organization);

        return response()->json($organization);
    }

    public function setPrimary(Organization $organization): JsonResponse
    {
        $this->authorize('setPrimary', $organization);

        $organization = $this->service->setPrimary($organization);

        return response()->json($organization);
    }
}