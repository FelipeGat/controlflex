<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'titular_id', 'nome', 'tipo_conta', 'codigo_banco',
        'agencia', 'conta', 'saldo', 'cheque_especial', 'saldo_cheque',
        'limite_cartao', 'saldo_cartao', 'logo', 'cor',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'cheque_especial' => 'decimal:2',
        'saldo_cheque' => 'decimal:2',
        'limite_cartao' => 'decimal:2',
        'saldo_cartao' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function titular()
    {
        return $this->belongsTo(Familiar::class, 'titular_id');
    }

    public function investimentos()
    {
        return $this->hasMany(Investimento::class);
    }

    public function getSaldoDisponivelAttribute(): float
    {
        return (float)$this->saldo + (float)$this->cheque_especial - (float)$this->saldo_cheque;
    }

    public function getLimiteCartaoDisponivelAttribute(): float
    {
        return (float)$this->limite_cartao - (float)$this->saldo_cartao;
    }
}
