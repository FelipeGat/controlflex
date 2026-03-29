<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investimento extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'banco_id', 'nome_ativo', 'tipo_investimento',
        'data_aporte', 'valor_aportado', 'quantidade_cotas',
        'percentual_mensal', 'percentual_anual', 'observacoes',
    ];

    protected $casts = [
        'valor_aportado'   => 'decimal:2',
        'quantidade_cotas' => 'decimal:6',
        'percentual_mensal' => 'decimal:4',
        'percentual_anual'  => 'decimal:4',
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

    public function rendimentos()
    {
        return $this->hasMany(InvestimentoRendimento::class)->orderBy('data');
    }
}
