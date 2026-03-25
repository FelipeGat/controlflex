<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ─── Relacionamentos ──────────────────────────────────────────────────────

    public function familiares()
    {
        return $this->hasMany(Familiar::class);
    }

    public function categorias()
    {
        return $this->hasMany(Categoria::class);
    }

    public function fornecedores()
    {
        return $this->hasMany(Fornecedor::class);
    }

    public function bancos()
    {
        return $this->hasMany(Banco::class);
    }

    public function despesas()
    {
        return $this->hasMany(Despesa::class);
    }

    public function receitas()
    {
        return $this->hasMany(Receita::class);
    }

    public function investimentos()
    {
        return $this->hasMany(Investimento::class);
    }
}
