<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfeItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'nfe_id', 'code', 'ncm', 'cfop', 'description', 'unit',
        'quantity', 'unit_price', 'total_price', 'discount',
        'icms', 'ipi', 'pis', 'cofins',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'icms' => 'decimal:2',
            'ipi' => 'decimal:2',
            'pis' => 'decimal:2',
            'cofins' => 'decimal:2',
        ];
    }

    public function nfe(): BelongsTo
    {
        return $this->belongsTo(Nfe::class);
    }
}
