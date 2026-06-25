<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthPlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'balance',
        'committed_balance',
        'start_date',
        'end_date',
        'is_active',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'committed_balance' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function resourcePlans(): HasMany
    {
        return $this->hasMany(ResourcePlan::class);
    }
}
