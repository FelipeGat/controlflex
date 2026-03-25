<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Receita extends Model
{
    protected $fillable = [
        'user_id', 'quem_recebeu', 'categoria_id', 'forma_recebimento',
        'valor', 'data_prevista_recebimento', 'data_recebimento', 'observacoes',
        'recorrente', 'parcelas', 'frequencia', 'grupo_recorrencia_id',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_prevista_recebimento' => 'date',
        'data_recebimento' => 'date',
        'recorrente' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function familiar()
    {
        return $this->belongsTo(Familiar::class, 'quem_recebeu');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class, 'forma_recebimento');
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
        $dataInicial = Carbon::parse($data['data_prevista_recebimento']);

        for ($i = 0; $i < $total; $i++) {
            $dataAtual = (clone $dataInicial)->modify("+{$i} {$intervalo}");
            self::create([
                'user_id' => $userId,
                'quem_recebeu' => $data['quem_recebeu'] ?? null,
                'categoria_id' => $data['categoria_id'] ?? null,
                'forma_recebimento' => $data['forma_recebimento'] ?? null,
                'valor' => $data['valor'],
                'data_prevista_recebimento' => $dataAtual->format('Y-m-d'),
                'data_recebimento' => !empty($data['data_recebimento']) ? $data['data_recebimento'] : null,
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
