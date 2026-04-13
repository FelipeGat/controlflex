<?php

namespace Tests\Feature;

use App\Models\Despesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DespesaCartaoCreditoTest extends TestCase
{
    use RefreshDatabase;

    public function test_criar_com_recorrencia_cartao_credito_parcelado_nao_quebra(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $total = Despesa::criarComRecorrencia([
            'valor'                 => 300.00,
            'data_compra'           => '2026-04-10',
            'tipo_pagamento'        => 'credito',
            'parcelas'              => 3,
            'dia_fechamento_cartao' => 20,
            'dia_vencimento_cartao' => 5,
        ], $user->id);

        $this->assertSame(3, $total);

        $despesas = Despesa::where('tipo_pagamento', 'credito')->get();
        $this->assertCount(3, $despesas);
        $this->assertEqualsWithDelta(100.00, (float) $despesas->first()->valor, 0.01);
        $this->assertStringContainsString('Parcela 1/3', $despesas->sortBy('data_compra')->first()->observacoes);
    }
}
