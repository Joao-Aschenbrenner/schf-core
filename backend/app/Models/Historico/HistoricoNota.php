<?php

namespace App\Models\Historico;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoNota extends Model
{
    use HasFactory;

    protected $table = 'historico_notas';

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\HistoricoNotaFactory::new();
    }
}
