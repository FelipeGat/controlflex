<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    protected $fillable = [
        'nome', 'slug', 'descricao', 'preco_mensal', 'preco_anual',
        'max_usuarios', 'max_bancos', 'ativo',
    ];

    protected function casts(): array
    {
        return [
            'preco_mensal'  => 'decimal:2',
            'preco_anual'   => 'decimal:2',
            'max_usuarios'  => 'integer',
            'max_bancos'    => 'integer',
            'ativo'         => 'boolean',
        ];
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function limiteUsuariosAtingido(Tenant $tenant): bool
    {
        if ($this->max_usuarios === -1) {
            return false;
        }

        return $tenant->users()->count() >= $this->max_usuarios;
    }

    public function limiteBancosAtingido(Tenant $tenant): bool
    {
        if ($this->max_bancos === -1) {
            return false;
        }

        return Banco::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count() >= $this->max_bancos;
    }
}
