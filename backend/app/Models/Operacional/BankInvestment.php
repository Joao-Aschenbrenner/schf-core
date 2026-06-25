<?php

namespace App\Models\Operacional;

use App\Models\BankAccount;
use App\Models\Historico\HistoricoConta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'description',
        'investment_type',
        'amount',
        'yield_rate',
        'start_date',
        'maturity_date',
        'status',
        'redeemed_amount',
        'redeemed_at',
        'legacy_conta_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'redeemed_amount' => 'decimal:2',
            'yield_rate' => 'decimal:4',
            'start_date' => 'date',
            'maturity_date' => 'date',
            'redeemed_at' => 'date',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function legacyConta(): BelongsTo
    {
        return $this->belongsTo(HistoricoConta::class, 'legacy_conta_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(BankOperation::class, 'bank_investment_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeRedeemed(Builder $query): Builder
    {
        return $query->where('status', 'redeemed');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }
}
