<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'bank_code',
        'bank_name',
        'agency',
        'account',
        'digit',
        'type',
        'holder_name',
        'holder_cnpj',
        'current_balance',
        'health_plan_id',
        'is_active',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'health_plan_id' => 'integer',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
