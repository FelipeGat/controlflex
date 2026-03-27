<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'nome', 'ativo', 'revenda_id', 'plano_id', 'status',
        'tipo_cobranca', 'data_inicio_plano', 'data_fim_plano',
    ];

    protected function casts(): array
    {
        return [
            'ativo'             => 'boolean',
            'data_inicio_plano' => 'date',
            'data_fim_plano'    => 'date',
        ];
    }

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

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo' && $this->ativo;
    }

    public function diasRestantes(): ?int
    {
        if (!$this->data_fim_plano) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->data_fim_plano, false);
    }

    public function planoVencido(): bool
    {
        return $this->data_fim_plano && $this->data_fim_plano->isPast();
    }

    public function limiteUsuariosAtingido(): bool
    {
        $plano = $this->plano;
        if (!$plano) {
            return false;
        }

        return $plano->limiteUsuariosAtingido($this);
    }

    public function limiteBancosAtingido(): bool
    {
        $plano = $this->plano;
        if (!$plano) {
            return false;
        }

        return $plano->limiteBancosAtingido($this);
    }
}
