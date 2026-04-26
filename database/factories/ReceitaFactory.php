<?php

namespace Database\Factories;

use App\Models\Receita;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receita>
 */
class ReceitaFactory extends Factory
{
    protected $model = Receita::class;

    public function definition(): array
    {
        return [
            'tenant_id'                 => Tenant::factory(),
            'user_id'                   => User::factory(),
            'valor'                     => fake()->randomFloat(2, 50, 5000),
            'data_prevista_recebimento' => fake()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'data_recebimento'          => null,
            'tipo_pagamento'            => 'pix',
            'parcelas'                  => 1,
            'recorrente'                => false,
        ];
    }
}
