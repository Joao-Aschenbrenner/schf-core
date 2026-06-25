<?php

namespace App\Models\Operacional;

use App\Models\BankAccount;
use App\Models\Historico\HistoricoNota;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receivable extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'bank_account_id',
        'description',
        'document_number',
        'amount',
        'discount',
        'interest',
        'received_amount',
        'due_date',
        'receipt_date',
        'status',
        'payment_method',
        'notes',
        'legacy_nota_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'interest' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'due_date' => 'date',
            'receipt_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function legacyNota(): BelongsTo
    {
        return $this->belongsTo(HistoricoNota::class, 'legacy_nota_id');
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

    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now()->toDateString());
    }
}
