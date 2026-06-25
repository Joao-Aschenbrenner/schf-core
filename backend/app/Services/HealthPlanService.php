<?php

namespace App\Services;

use App\Models\HealthPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HealthPlanService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = HealthPlan::query()->with('resourcePlans');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): HealthPlan
    {
        return DB::transaction(function () use ($data) {
            $plan = HealthPlan::create($data);

            activity()
                ->performedOn($plan)
                ->withProperties($data)
                ->log('health_plan_created');

            return $plan;
        });
    }

    public function update(HealthPlan $healthPlan, array $data): HealthPlan
    {
        return DB::transaction(function () use ($healthPlan, $data) {
            $oldValues = $healthPlan->toArray();
            $healthPlan->update($data);

            activity()
                ->performedOn($healthPlan)
                ->withProperties(['old' => $oldValues, 'new' => $data])
                ->log('health_plan_updated');

            return $healthPlan->fresh();
        });
    }

    public function deactivate(HealthPlan $healthPlan, string $reason): HealthPlan
    {
        return DB::transaction(function () use ($healthPlan, $reason) {
            $healthPlan->update(['is_active' => false]);

            activity()
                ->performedOn($healthPlan)
                ->withProperties(['reason' => $reason])
                ->log('health_plan_deactivated');

            return $healthPlan->fresh();
        });
    }

    public function addResourcePlan(HealthPlan $healthPlan, array $data): \App\Models\ResourcePlan
    {
        return DB::transaction(function () use ($healthPlan, $data) {
            $resourcePlan = $healthPlan->resourcePlans()->create($data);

            activity()
                ->performedOn($resourcePlan)
                ->withProperties($data)
                ->log('resource_plan_created');

            return $resourcePlan;
        });
    }

    public function checkBalance(HealthPlan $healthPlan): array
    {
        $allocated = $healthPlan->resourcePlans()->sum('allocated_amount');
        $used = $healthPlan->resourcePlans()->sum('used_amount');
        $committed = $healthPlan->resourcePlans()->sum('committed_amount');

        return [
            'allocated' => $allocated,
            'used' => $used,
            'committed' => $committed,
            'available' => $allocated - $used - $committed,
            'usage_percent' => $allocated > 0 ? round(($used / $allocated) * 100, 2) : 0,
        ];
    }
}
