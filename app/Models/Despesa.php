<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Despesa extends Model
{
    protected $fillable = [
        'user_id', 'quem_comprou', 'onde_comprou', 'categoria_id', 'forma_pagamento',
        'valor', 'data_compra', 'data_pagamento', 'observacoes',
        'recorrente', 'parcelas', 'frequencia', 'grupo_recorrencia_id',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_compra' => 'date',
        'data_pagamento' => 'date',
        'recorrente' => 'boolean',
    ];

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

    public static function criarComRecorrencia(array $data, int $userId): int
    {
        $isRecorrente = !empty($data['recorrente']);
        $parcelas = isset($data['parcelas']) ? (int)$data['parcelas'] : 1;
        $frequencia = $data['frequencia'] ?? 'mensal';

        $intervalos = [
            'diaria' => '1 day', 'semanal' => '1 week', 'quinzenal' => '2 weeks',
            'mensal' => '1 month', 'trimestral' => '3 months',
            'semestral' => '6 months', 'anual' => '1 year',
        ];
        $intervalo = $intervalos[$frequencia] ?? '1 month';

        $total = 1;
        if ($isRecorrente) {
            $total = ($parcelas === 0) ? 60 : $parcelas;
        }

        $grupoId = ($total > 1) ? Str::uuid()->toString() : null;
        $dataInicial = Carbon::parse($data['data_compra']);

        for ($i = 0; $i < $total; $i++) {
            $dataAtual = (clone $dataInicial)->modify("+{$i} {$intervalo}");
            self::create([
                'user_id' => $userId,
                'quem_comprou' => $data['quem_comprou'] ?? null,
                'onde_comprou' => $data['onde_comprou'] ?? null,
                'categoria_id' => $data['categoria_id'] ?? null,
                'forma_pagamento' => $data['forma_pagamento'] ?? null,
                'valor' => $data['valor'],
                'data_compra' => $dataAtual->format('Y-m-d'),
                'data_pagamento' => !empty($data['data_pagamento']) ? $data['data_pagamento'] : null,
                'recorrente' => $isRecorrente,
                'parcelas' => $total,
                'frequencia' => $frequencia,
                'observacoes' => $data['observacoes'] ?? null,
                'grupo_recorrencia_id' => $grupoId,
            ]);
        }

        return $total;
    }
}
