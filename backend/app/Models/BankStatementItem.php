<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'bank_statement_id', 'transaction_date', 'description', 'document_id',
        'type', 'amount', 'balance_after',
        'payable_id', 'pre_launch_id',
        'is_reconciled', 'reconciled_at', 'reconciled_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'is_reconciled' => 'boolean',
            'reconciled_at' => 'datetime',
        ];
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function preLaunch(): BelongsTo
    {
        return $this->belongsTo(PreLaunch::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }
}
