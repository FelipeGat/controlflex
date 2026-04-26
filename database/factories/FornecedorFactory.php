<?php

namespace Database\Factories;

use App\Models\Fornecedor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fornecedor>
 */
class FornecedorFactory extends Factory
{
    protected $model = Fornecedor::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id'   => User::factory(),
            'nome'      => fake()->company(),
            'grupo'     => null,
            'icone'     => '',
        ];
    }
}
