<?php

namespace App\Models\Operacional;

use App\Models\BankAccount;
use App\Models\Payable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankOperation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'bank_account_id',
        'type',
        'amount',
        'description',
        'document',
        'operation_date',
        'reference_id',
        'reference_type',
        'payable_id',
        'receivable_id',
        'bank_investment_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'operation_date' => 'date',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function bankInvestment(): BelongsTo
    {
        return $this->belongsTo(BankInvestment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }

    public function scopeInvestments(Builder $query): Builder
    {
        return $query->where('type', 'investment');
    }

    public function scopeTransfers(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }
}
