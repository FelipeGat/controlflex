<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Despesa extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'quem_comprou', 'onde_comprou', 'categoria_id', 'forma_pagamento',
        'valor', 'data_compra', 'data_pagamento', 'observacoes',
        'recorrente', 'parcelas', 'frequencia', 'grupo_recorrencia_id',
    ];

    protected $casts = [
        'valor'          => 'decimal:2',
        'data_compra'    => 'date',
        'data_pagamento' => 'date',
        'recorrente'     => 'boolean',
    ];

    // ─── Relacionamentos ──────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function familiar()
    {
        return $this->belongsTo(Familiar::class, 'quem_comprou');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'onde_comprou');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'forma_pagamento');
    }

    // ─── Atributos computados ─────────────────────────────────────────────────

    /**
     * Status financeiro da despesa:
     *   'pago'    → data_pagamento preenchida
     *   'vencido' → sem pagamento e data_compra já passou
     *   'a_pagar' → sem pagamento, data_compra hoje ou futura
     */
    public function getStatusAttribute(): string
    {
        if ($this->data_pagamento) {
            return 'pago';
        }

        if ($this->data_compra->isPast() && ! $this->data_compra->isToday()) {
            return 'vencido';
        }

        return 'a_pagar';
    }

    // ─── Lógica de recorrência ────────────────────────────────────────────────

    /**
     * Cria uma ou múltiplas despesas dentro de uma transação atômica.
     * Se qualquer parcela falhar, nenhuma é persistida.
     */
    public static function criarComRecorrencia(array $data, int $userId): int
    {
        $isRecorrente = ! empty($data['recorrente']);
        $parcelas     = isset($data['parcelas']) ? (int) $data['parcelas'] : 1;
        $frequencia   = $data['frequencia'] ?? 'mensal';

        $total = 1;
        if ($isRecorrente) {
            $total = ($parcelas === 0) ? 60 : max(1, $parcelas);
        }

        $grupoId     = ($total > 1) ? Str::uuid()->toString() : null;
        $dataInicial = Carbon::parse($data['data_compra']);

        DB::transaction(function () use ($data, $userId, $total, $grupoId, $dataInicial, $frequencia, $isRecorrente) {
            for ($i = 0; $i < $total; $i++) {
                $dataAtual = self::calcularData($dataInicial, $frequencia, $i);

                self::create([
                    'user_id'              => $userId,
                    'quem_comprou'         => $data['quem_comprou'] ?? null,
                    'onde_comprou'         => $data['onde_comprou'] ?? null,
                    'categoria_id'         => $data['categoria_id'] ?? null,
                    'forma_pagamento'      => $data['forma_pagamento'] ?? null,
                    'valor'                => $data['valor'],
                    'data_compra'          => $dataAtual->format('Y-m-d'),
                    'data_pagamento'       => ! empty($data['data_pagamento']) ? $data['data_pagamento'] : null,
                    'recorrente'           => $isRecorrente,
                    'parcelas'             => $total,
                    'frequencia'           => $frequencia,
                    'observacoes'          => $data['observacoes'] ?? null,
                    'grupo_recorrencia_id' => $grupoId,
                ]);
            }
        });

        return $total;
    }

    /**
     * Calcula a data de uma parcela usando Carbon nativo (evita ambiguidade do modify()).
     */
    private static function calcularData(Carbon $base, string $frequencia, int $offset): Carbon
    {
        $data = $base->copy();

        return match ($frequencia) {
            'diaria'     => $data->addDays($offset),
            'semanal'    => $data->addWeeks($offset),
            'quinzenal'  => $data->addWeeks($offset * 2),
            'mensal'     => $data->addMonths($offset),
            'trimestral' => $data->addMonths($offset * 3),
            'semestral'  => $data->addMonths($offset * 6),
            'anual'      => $data->addYears($offset),
            default      => $data->addMonths($offset),
        };
    }
}
