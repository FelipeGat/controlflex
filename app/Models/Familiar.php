<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Familiar extends Model
{
    use HasFactory, BelongsToTenant;

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

    public function membro()
    {
        return $this->hasOne(User::class)->where('role', 'membro');
    }

    public function userVinculado()
    {
        return $this->hasOne(User::class);
    }

    public function isMaster(): bool
    {
        return $this->userVinculado?->role === 'master';
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
