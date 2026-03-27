<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenda extends Model
{
    protected $fillable = ['nome', 'cnpj', 'email', 'telefone', 'status'];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function admin()
    {
        return $this->hasOne(User::class)->where('role', 'admin_revenda');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }
}
