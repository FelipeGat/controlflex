<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'fornecedores';
    protected $fillable = ['tenant_id', 'user_id', 'nome', 'icone', 'grupo', 'contato', 'cnpj', 'telefone', 'observacoes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function despesas()
    {
        return $this->hasMany(Despesa::class, 'onde_comprou');
    }
}
