<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourcePlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'health_plan_id',
        'name',
        'description',
        'allocated_amount',
        'used_amount',
        'committed_amount',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'health_plan_id' => 'integer',
            'allocated_amount' => 'decimal:2',
            'used_amount' => 'decimal:2',
            'committed_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class);
    }
}
