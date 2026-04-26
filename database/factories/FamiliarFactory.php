<?php

namespace Database\Factories;

use App\Models\Familiar;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Familiar>
 */
class FamiliarFactory extends Factory
{
    protected $model = Familiar::class;

    public function definition(): array
    {
        return [
            'tenant_id'     => Tenant::factory(),
            'user_id'       => User::factory(),
            'nome'          => fake()->firstName(),
            'salario'       => fake()->randomFloat(2, 0, 10000),
            'limite_cartao' => 0,
            'limite_cheque' => 0,
        ];
    }
}
