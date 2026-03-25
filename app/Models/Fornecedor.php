<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use BelongsToTenant;

    protected $table = 'fornecedores';
    protected $fillable = ['tenant_id', 'user_id', 'nome', 'contato', 'cnpj', 'telefone', 'observacoes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function despesas()
    {
        return $this->hasMany(Despesa::class, 'onde_comprou');
    }
}
