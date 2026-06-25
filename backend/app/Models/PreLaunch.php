<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreLaunch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pre_launches';

    protected $fillable = [
        'description', 'type', 'supplier_id', 'health_plan_id',
        'resource_plan_id', 'expense_category_id', 'bank_account_id',
        'estimated_amount', 'actual_amount', 'expected_date', 'actual_date',
        'status', 'payable_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'expected_date' => 'date',
            'actual_date' => 'date',
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
