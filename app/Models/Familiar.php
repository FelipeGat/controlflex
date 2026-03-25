<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    use BelongsToTenant;

    protected $table = 'familiares';
    protected $fillable = ['tenant_id', 'user_id', 'nome', 'foto', 'salario', 'limite_cartao', 'limite_cheque'];

    protected $casts = [
        'salario' => 'decimal:2',
        'limite_cartao' => 'decimal:2',
        'limite_cheque' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function despesas()
    {
        return $this->hasMany(Despesa::class, 'quem_comprou');
    }

    public function receitas()
    {
        return $this->hasMany(Receita::class, 'quem_recebeu');
    }

    public function bancos()
    {
        return $this->hasMany(Banco::class, 'titular_id');
    }
}
