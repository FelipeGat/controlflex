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
        'tenant_id', 'user_id', 'quem_comprou', 'onde_comprou', 'categoria_id',
        'forma_pagamento', 'tipo_pagamento',
        'valor', 'data_compra', 'data_pagamento', 'observacoes',
        'recorrente', 'parcelas', 'frequencia', 'grupo_recorrencia_id',
        'origem', 'numero_documento',
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

    // ─── Lógica de criação com recorrência / parcelamento ────────────────────

    /**
     * Cria uma ou múltiplas despesas dentro de uma transação atômica.
     *
     * Regras gerais:
     *   parcelas = 1  → despesa única
     *   parcelas > 1  → cria N lançamentos mensais (ou na frequência escolhida)
     *   parcelas = 0  → recorrência infinita (cria 60 lançamentos)
     *
     * Regras extras para Cartão de Crédito (tipo_pagamento = 'credito'):
     *   - O valor é DIVIDIDO entre as parcelas (ex: R$1.500 / 3 = R$500/parcela)
     *   - A data de cada parcela = data de VENCIMENTO da fatura, não a data da compra
     *   - Se a compra ocorreu ANTES ou NO dia de fechamento → 1ª parcela vence no mês seguinte
     *   - Se a compra ocorreu APÓS o dia de fechamento → compra cai na fatura do mês seguinte,
     *     portanto 1ª parcela vence apenas em 2 meses
     *   - Para funcionar, o banco precisa ter dia_fechamento_cartao e dia_vencimento_cartao
     *     configurados. O controller injeta esses valores em $data['dia_fechamento_cartao']
     *     e $data['dia_vencimento_cartao'].
     *
     * @param array $data   Dados do formulário + extras injetados pelo controller
     * @param int   $userId ID do usuário autenticado
     * @return int          Número de registros criados
     */
    public static function criarComRecorrencia(array $data, int $userId): int
    {
        $parcelas      = isset($data['parcelas']) ? (int) $data['parcelas'] : 1;
        $tipoPagamento = $data['tipo_pagamento'] ?? null;

        // Criação múltipla é ativada quando: parcelas > 1, parcelas = 0 (infinito), ou checkbox recorrente
        $isRecorrente = ! empty($data['recorrente']) || $parcelas === 0 || $parcelas > 1;

        $total = 1;
        if ($isRecorrente) {
            $total = ($parcelas === 0) ? 60 : max(1, $parcelas);
        }

        // ── Valor por parcela ─────────────────────────────────────────────────
        // Cartão de crédito divide o valor total entre as parcelas
        $valorTotal   = (float) $data['valor'];
        $valorParcela = ($tipoPagamento === 'credito' && $total > 1)
            ? round($valorTotal / $total, 2)
            : $valorTotal;

        $grupoId    = ($total > 1) ? Str::uuid()->toString() : null;
        $dataCompra = Carbon::parse($data['data_compra']);

        // ── Data inicial: normal ou data de vencimento do cartão ─────────────
        // Para cartão: não usamos a data da compra como data do lançamento.
        // Usamos a data de VENCIMENTO de cada fatura, calculada a partir do
        // dia de fechamento do cartão.
        $frequencia = $data['frequencia'] ?? 'mensal';
        $dataInicial = $dataCompra->copy();

        $isCartaoComFatura = (
            $tipoPagamento === 'credito'
            && ! empty($data['dia_fechamento_cartao'])
            && ! empty($data['dia_vencimento_cartao'])
        );

        if ($isCartaoComFatura) {
            $dataInicial = self::calcularPrimeiroVencimentoCartao(
                $dataCompra,
                (int) $data['dia_fechamento_cartao'],
                (int) $data['dia_vencimento_cartao']
            );
            $frequencia = 'mensal'; // fatura sempre é mensal
        }

        $obsBase = $data['observacoes'] ?? null;

        DB::transaction(function () use (
            $data, $userId, $total, $grupoId,
            $dataInicial, $dataCompra, $frequencia,
            $isRecorrente, $valorParcela, $tipoPagamento,
            $obsBase, $isCartaoComFatura
        ) {
            for ($i = 0; $i < $total; $i++) {
                $dataAtual = self::calcularData($dataInicial, $frequencia, $i);

                // Observações: numera as parcelas do cartão automaticamente
                $obs = $obsBase;
                if ($tipoPagamento === 'credito' && $total > 1) {
                    $prefixo = 'Parcela ' . ($i + 1) . '/' . $total;
                    $obs     = $obsBase ? "{$prefixo} — {$obsBase}" : $prefixo;
                }

                self::create([
                    'user_id'              => $userId,
                    'quem_comprou'         => $data['quem_comprou'] ?? null,
                    'onde_comprou'         => $data['onde_comprou'] ?? null,
                    'categoria_id'         => $data['categoria_id'] ?? null,
                    'forma_pagamento'      => $data['forma_pagamento'] ?? null,
                    'tipo_pagamento'       => $tipoPagamento,
                    'valor'                => $valorParcela,
                    // Para cartão: data_compra = data de vencimento da fatura
                    // Para outros: data_compra = data real da transação
                    'data_compra'          => $dataAtual->format('Y-m-d'),
                    // Parcelas de cartão não têm data de pagamento no momento da criação
                    'data_pagamento'       => ($isCartaoComFatura)
                        ? null
                        : (! empty($data['data_pagamento']) ? $data['data_pagamento'] : null),
                    'recorrente'           => $isRecorrente,
                    'parcelas'             => $total,
                    'frequencia'           => $frequencia,
                    'observacoes'          => $obs,
                    'grupo_recorrencia_id' => $grupoId,
                    'origem'               => $data['origem'] ?? 'manual',
                    'numero_documento'     => $data['numero_documento'] ?? null,
                ]);
            }
        });

        return $total;
    }

    /**
     * Calcula a data de vencimento da primeira parcela do cartão.
     *
     * Regra brasileira padrão:
     *   compra_dia <= fechamento_dia → fatura do mês atual → vence no mês seguinte
     *   compra_dia >  fechamento_dia → fatura do mês seguinte → vence em 2 meses
     *
     * Exemplo (fechamento dia 20, vencimento dia 5):
     *   Compra dia 15/03 → cai na fatura de março → vence 05/04
     *   Compra dia 25/03 → cai na fatura de abril → vence 05/05
     */
    public static function calcularPrimeiroVencimentoCartao(
        Carbon $dataCompra,
        int $diaFechamento,
        int $diaVencimento
    ): Carbon {
        if ($dataCompra->day <= $diaFechamento) {
            // Compra dentro do ciclo atual → vence no próximo mês
            return $dataCompra->copy()->addMonthNoOverflow()->setDay($diaVencimento);
        }

        // Compra após o fechamento → vai para o próximo ciclo → vence em 2 meses
        return $dataCompra->copy()->addMonthsNoOverflow(2)->setDay($diaVencimento);
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
