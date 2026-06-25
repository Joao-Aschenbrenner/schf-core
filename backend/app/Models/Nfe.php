<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nfe extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'nfe';

    protected $fillable = [
        'nfe_key', 'nfe_number', 'serie', 'emission_date', 'entry_date',
        'supplier_id', 'health_plan_id', 'resource_plan_id', 'expense_category_id',
        'goods_value', 'service_value', 'insurance_value', 'other_value',
        'icms_value', 'ipi_value', 'pis_value', 'cofins_value', 'total_value',
        'description', 'xml_content', 'status', 'is_manual_entry', 'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'emission_date' => 'date',
            'entry_date' => 'date',
            'goods_value' => 'decimal:2',
            'service_value' => 'decimal:2',
            'insurance_value' => 'decimal:2',
            'other_value' => 'decimal:2',
            'icms_value' => 'decimal:2',
            'ipi_value' => 'decimal:2',
            'pis_value' => 'decimal:2',
            'cofins_value' => 'decimal:2',
            'total_value' => 'decimal:2',
            'is_manual_entry' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function healthPlan(): BelongsTo
    {
        return $this->belongsTo(HealthPlan::class);
    }

    public function resourcePlan(): BelongsTo
    {
        return $this->belongsTo(ResourcePlan::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NfeItem::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function dda(): HasMany
    {
        return $this->hasMany(Dda::class);
    }
}
