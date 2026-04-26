<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['tenant_id', 'user_id', 'nome', 'tipo', 'icone'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function despesas()
    {
        return $this->hasMany(Despesa::class);
    }

    public function receitas()
    {
        return $this->hasMany(Receita::class);
    }
}
