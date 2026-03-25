<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investimento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'banco_id', 'nome_ativo', 'tipo_investimento',
        'data_aporte', 'valor_aportado', 'quantidade_cotas', 'observacoes',
    ];

    protected $casts = [
        'valor_aportado'   => 'decimal:2',
        'quantidade_cotas' => 'decimal:6',
        'data_aporte'      => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }
}
