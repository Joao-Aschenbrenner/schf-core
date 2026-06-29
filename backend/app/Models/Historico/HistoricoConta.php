<?php

namespace App\Models\Historico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoConta extends Model
{
    use HasFactory;

    protected $table = 'historico_contas';

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\HistoricoContaFactory::new();
    }
}
