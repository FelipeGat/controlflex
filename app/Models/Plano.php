<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    protected $fillable = [
        'nome', 'slug', 'descricao', 'preco_mensal', 'preco_anual',
        'max_clientes', 'max_usuarios', 'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco_mensal' => 'decimal:2',
            'preco_anual'  => 'decimal:2',
            'ativo'        => 'boolean',
        ];
    }

    public function revendas()
    {
        return $this->hasMany(Revenda::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}
