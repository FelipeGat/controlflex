<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ManutencaoProgramada extends Model
{
    protected $table = 'manutencao_programada';

    protected $fillable = [
        'ativo',
        'titulo',
        'mensagem',
        'inicio_programado',
        'fim_programado',
        'criado_por',
    ];

    protected $casts = [
        'ativo'             => 'boolean',
        'inicio_programado' => 'datetime',
        'fim_programado'    => 'datetime',
    ];

    /** Retorna (e cria se necessário) a instância singleton. */
    public static function getInstance(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['ativo' => false, 'titulo' => 'Manutenção Programada']
        );
    }

    /**
     * Manutenção efetivamente ativa: toggle ligado E dentro da janela de tempo.
     * Se não há janela definida, ativa imediatamente ao ligar o toggle.
     */
    public function isAtiva(): bool
    {
        if (! $this->ativo) {
            return false;
        }

        $now = Carbon::now();

        $aposInicio = $this->inicio_programado === null || ! $now->isBefore($this->inicio_programado);
        $antesDoFim = $this->fim_programado === null || $now->isBefore($this->fim_programado);

        return $aposInicio && $antesDoFim;
    }

    /**
     * Manutenção agendada mas ainda não iniciada:
     * toggle ligado, mas ainda antes de inicio_programado.
     */
    public function isAgendada(): bool
    {
        if (! $this->ativo || $this->inicio_programado === null) {
            return false;
        }

        return Carbon::now()->isBefore($this->inicio_programado);
    }

    /** Segundos restantes até o fim (se ativa) ou até o início (se agendada). */
    public function segundosRestantes(): ?int
    {
        $now = Carbon::now();

        if ($this->isAtiva() && $this->fim_programado !== null) {
            return max(0, $now->diffInSeconds($this->fim_programado, false));
        }

        if ($this->isAgendada() && $this->inicio_programado !== null) {
            return max(0, $now->diffInSeconds($this->inicio_programado, false));
        }

        return null;
    }
}
