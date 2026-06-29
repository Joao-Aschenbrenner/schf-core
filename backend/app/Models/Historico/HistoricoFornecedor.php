<?php

namespace App\Models\Historico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoFornecedor extends Model
{
    use HasFactory;

    protected $table = 'historico_fornecedores';

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\HistoricoFornecedorFactory::new();
    }
}
