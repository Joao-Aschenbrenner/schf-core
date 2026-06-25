<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuditTrailService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = AuditTrail::query()->with('user');

        if (isset($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($filters['per_page'] ?? 25);
    }

    public function log(string $modelType, int $modelId, string $action, ?array $oldValues = null, ?array $newValues = null, ?string $reason = null): AuditTrail
    {
        return AuditTrail::create([
            'model_type' => $modelType,
            'model_id' => $modelId,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
        ]);
    }

    public function getModelTimeline(string $modelType, int $modelId): Collection
    {
        return AuditTrail::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }
}
