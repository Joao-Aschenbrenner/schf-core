<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Organization::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        $sortField = $filters['sort_field'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Organization
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['is_primary']) && $data['is_primary']) {
                Organization::where('is_primary', true)->update(['is_primary' => false]);
            }

            $organization = Organization::create($data);

            activity()
                ->performedOn($organization)
                ->withProperties($data)
                ->log('organization_created');

            return $organization;
        });
    }

    public function update(Organization $organization, array $data): Organization
    {
        return DB::transaction(function () use ($organization, $data) {
            $oldValues = $organization->toArray();

            if (isset($data['is_primary']) && $data['is_primary']) {
                Organization::where('is_primary', true)
                    ->where('id', '!=', $organization->id)
                    ->update(['is_primary' => false]);
            }

            $organization->update($data);

            activity()
                ->performedOn($organization)
                ->withProperties([
                    'old' => $oldValues,
                    'new' => $data,
                ])
                ->log('organization_updated');

            return $organization->fresh();
        });
    }

    public function deactivate(Organization $organization, string $reason): Organization
    {
        return DB::transaction(function () use ($organization, $reason) {
            $organization->update(['is_active' => false]);

            activity()
                ->performedOn($organization)
                ->withProperties(['reason' => $reason])
                ->log('organization_deactivated');

            return $organization->fresh();
        });
    }

    public function activate(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization) {
            $organization->update(['is_active' => true]);

            activity()
                ->performedOn($organization)
                ->log('organization_activated');

            return $organization->fresh();
        });
    }

    public function setPrimary(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization) {
            Organization::where('is_primary', true)->update(['is_primary' => false]);
            $organization->update(['is_primary' => true]);

            activity()
                ->performedOn($organization)
                ->log('organization_set_primary');

            return $organization->fresh();
        });
    }
}