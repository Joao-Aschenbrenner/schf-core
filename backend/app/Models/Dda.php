<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dda extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'dda';

    protected $fillable = [
        'bank_code', 'bank_name', 'document_number', 'title_number',
        'bar_code', 'payment_line_code',
        'payer_name', 'payer_cnpj', 'payer_cpf',
        'amount', 'due_date',
        'supplier_id', 'nfe_id', 'payable_id',
        'status', 'notes', 'raw_data', 'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'raw_data' => 'array',
            'imported_at' => 'datetime',
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

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
