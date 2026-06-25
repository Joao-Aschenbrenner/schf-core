<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payable extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'description', 'document_number', 'supplier_id', 'nfe_id',
        'health_plan_id', 'resource_plan_id', 'expense_category_id', 'bank_account_id',
        'amount', 'discount', 'interest', 'paid_amount',
        'due_date', 'payment_date', 'paid_at',
        'status', 'payment_method', 'bar_code', 'payment_line_code', 'receipt_number',
        'notes', 'cancellation_reason',
        'cancelled_by', 'created_by', 'approved_by', 'approved_at', 'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'interest' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'payment_date' => 'date',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function nfe(): BelongsTo
    {
        return $this->belongsTo(Nfe::class);
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

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'overdue')
            ->orWhere(function (Builder $q) {
                $q->where('status', 'pending')
                  ->where('due_date', '<', now()->toDateString());
            });
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }
}
