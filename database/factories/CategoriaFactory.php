<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id'   => User::factory(),
            'nome'      => fake()->words(2, true),
            'tipo'      => fake()->randomElement(['DESPESA', 'RECEITA']),
            'icone'     => '',
        ];
    }

    public function despesa(): static
    {
        return $this->state(fn() => ['tipo' => 'DESPESA']);
    }

    public function receita(): static
    {
        return $this->state(fn() => ['tipo' => 'RECEITA']);
    }
}
