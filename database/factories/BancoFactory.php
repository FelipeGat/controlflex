<?php

namespace Database\Factories;

use App\Models\Banco;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banco>
 */
class BancoFactory extends Factory
{
    protected $model = Banco::class;

    public function definition(): array
    {
        return [
            'tenant_id'             => Tenant::factory(),
            'user_id'               => User::factory(),
            'nome'                  => fake()->company(),
            'tem_conta_corrente'    => true,
            'tem_poupanca'          => false,
            'tem_cartao_credito'    => false,
            'eh_dinheiro'           => false,
            'saldo'                 => 1000.00,
            'saldo_poupanca'        => 0,
            'cheque_especial'       => 0,
            'saldo_cheque'          => 0,
            'limite_cartao'         => 0,
            'saldo_cartao'          => 0,
        ];
    }

    public function cartao(int $diaFechamento = 5, int $diaVencimento = 15, float $limite = 5000): static
    {
        return $this->state(fn() => [
            'tem_cartao_credito'    => true,
            'limite_cartao'         => $limite,
            'dia_fechamento_cartao' => $diaFechamento,
            'dia_vencimento_cartao' => $diaVencimento,
        ]);
    }
}
