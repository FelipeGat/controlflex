<?php

namespace Database\Factories;

use App\Models\Despesa;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Despesa>
 */
class DespesaFactory extends Factory
{
    protected $model = Despesa::class;

    public function definition(): array
    {
        return [
            'tenant_id'      => Tenant::factory(),
            'user_id'        => User::factory(),
            'valor'          => fake()->randomFloat(2, 5, 5000),
            'data_compra'    => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'data_pagamento' => null,
            'tipo_pagamento' => fake()->randomElement(['dinheiro', 'pix', 'debito']),
            'parcelas'       => 1,
            'recorrente'     => false,
            'origem'         => 'manual',
        ];
    }
}
