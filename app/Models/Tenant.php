<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = ['nome', 'plano', 'ativo', 'revenda_id', 'plano_id', 'status'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function master()
    {
        return $this->hasOne(User::class)->where('role', 'master');
    }

    public function revenda()
    {
        return $this->belongsTo(Revenda::class);
    }

    public function planoObj()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo' && $this->ativo;
    }
}
