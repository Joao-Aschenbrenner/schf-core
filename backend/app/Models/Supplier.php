<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cnpj',
        'cpf',
        'trade_name',
        'ie',
        'im',
        'cnae',
        'email',
        'phone',
        'cellphone',
        'contact_name',
        'address_street',
        'address_number',
        'address_complement',
        'address_district',
        'address_city',
        'address_state',
        'address_zip',
        'bank_name',
        'bank_agency',
        'bank_account',
        'bank_type',
        'pix_key',
        'pix_type',
        'notes',
        'is_active',
        'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }
}
