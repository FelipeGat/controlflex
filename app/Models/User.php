<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'foto', 'tenant_id', 'revenda_id', 'role', 'permissoes', 'ativo'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'permissoes'        => 'array',
            'ativo'             => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function revenda()
    {
        return $this->belongsTo(Revenda::class);
    }

    // ─── Role checks ────────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdminRevenda(): bool
    {
        return $this->role === 'admin_revenda';
    }

    public function isMaster(): bool
    {
        return $this->role === 'master';
    }

    public function temPermissao(string $modulo, string $acao): bool
    {
        if (in_array($this->role, ['super_admin', 'admin_revenda', 'master'])) return true;
        $perms = $this->permissoes ?? [];
        return ($perms[$modulo][$acao] ?? false) === true;
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'super_admin'   => 'admin.dashboard',
            'admin_revenda' => 'revenda.clientes.index',
            default         => 'dashboard',
        };
    }

    // ─── Relacionamentos com dados do tenant ────────────────────────────────────
    public function familiares()   { return $this->hasMany(Familiar::class); }
    public function categorias()   { return $this->hasMany(Categoria::class); }
    public function fornecedores() { return $this->hasMany(Fornecedor::class); }
    public function bancos()       { return $this->hasMany(Banco::class); }
    public function despesas()     { return $this->hasMany(Despesa::class); }
    public function receitas()     { return $this->hasMany(Receita::class); }
    public function investimentos(){ return $this->hasMany(Investimento::class); }
}
