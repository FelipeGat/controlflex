<?php

namespace Tests\Feature\Api\V1;

use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Despesa;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DespesaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'master']);
    }

    // ─── INDEX ────────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/despesas')->assertStatus(401);
    }

    public function test_index_returns_only_current_tenant_data(): void
    {
        $otherTenant = Tenant::factory()->create();
        Despesa::factory()->count(2)->create([
            'tenant_id' => $this->user->tenant_id,
            'data_compra' => now()->format('Y-m-d'),
        ]);
        Despesa::factory()->create([
            'tenant_id' => $otherTenant->id,
            'data_compra' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        $resp = $this->getJson('/api/v1/despesas');

        $resp->assertOk();
        $this->assertCount(2, $resp->json('data'));
    }

    public function test_index_filters_by_status(): void
    {
        Despesa::factory()->create([
            'tenant_id'      => $this->user->tenant_id,
            'data_compra'    => now()->format('Y-m-d'),
            'data_pagamento' => now()->format('Y-m-d'),
        ]);
        Despesa::factory()->create([
            'tenant_id'      => $this->user->tenant_id,
            'data_compra'    => now()->format('Y-m-d'),
            'data_pagamento' => null,
        ]);

        Sanctum::actingAs($this->user);

        $pago    = $this->getJson('/api/v1/despesas?status=pago');
        $aPagar  = $this->getJson('/api/v1/despesas?status=a_pagar');

        $this->assertCount(1, $pago->json('data'));
        $this->assertCount(1, $aPagar->json('data'));
    }

    public function test_index_includes_total_valor_in_meta(): void
    {
        Despesa::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'data_compra' => now()->format('Y-m-d'),
            'valor' => 100,
        ]);
        Despesa::factory()->create([
            'tenant_id' => $this->user->tenant_id,
            'data_compra' => now()->format('Y-m-d'),
            'valor' => 250.50,
        ]);

        Sanctum::actingAs($this->user);

        $resp = $this->getJson('/api/v1/despesas');
        $resp->assertOk();
        $this->assertEquals(350.50, $resp->json('meta.total_valor'));
    }

    // ─── STORE ────────────────────────────────────────────────────────────

    public function test_store_creates_single_despesa(): void
    {
        Sanctum::actingAs($this->user);

        $resp = $this->postJson('/api/v1/despesas', [
            'valor'       => 99.90,
            'data_compra' => now()->format('Y-m-d'),
        ]);

        $resp->assertStatus(201)
             ->assertJsonPath('count', 1)
             ->assertJsonCount(1, 'data');
        $this->assertDatabaseCount('despesas', 1);
    }

    public function test_store_creates_multiple_parcelas_credit_card(): void
    {
        $banco = Banco::factory()->cartao(diaFechamento: 5, diaVencimento: 15)->create([
            'tenant_id' => $this->user->tenant_id,
        ]);

        Sanctum::actingAs($this->user);

        $resp = $this->postJson('/api/v1/despesas', [
            'valor'           => 300,
            'data_compra'     => '2026-04-10',
            'forma_pagamento' => $banco->id,
            'tipo_pagamento'  => 'credito',
            'parcelas'        => 3,
        ]);

        $resp->assertStatus(201)->assertJsonPath('count', 3);
        $this->assertDatabaseCount('despesas', 3);

        // R$300 / 3 parcelas = R$100 cada
        $valores = Despesa::pluck('valor')->map(fn($v) => (float) $v)->all();
        $this->assertEquals([100.0, 100.0, 100.0], $valores);
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/despesas', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['valor', 'data_compra']);
    }

    public function test_store_validates_tipo_pagamento(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/despesas', [
            'valor'          => 10,
            'data_compra'    => now()->format('Y-m-d'),
            'tipo_pagamento' => 'inventado',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['tipo_pagamento']);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────

    public function test_show_returns_despesa_with_relations(): void
    {
        $cat = Categoria::factory()->despesa()->create(['tenant_id' => $this->user->tenant_id]);
        $despesa = Despesa::factory()->create([
            'tenant_id'    => $this->user->tenant_id,
            'categoria_id' => $cat->id,
            'data_compra'  => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        $resp = $this->getJson("/api/v1/despesas/{$despesa->id}");

        $resp->assertOk()
             ->assertJsonPath('data.id', $despesa->id)
             ->assertJsonPath('data.categoria.id', $cat->id);
    }

    public function test_show_returns_404_for_other_tenant_despesa(): void
    {
        $other = Tenant::factory()->create();
        $despesa = Despesa::factory()->create([
            'tenant_id'   => $other->id,
            'data_compra' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        // Despesa nao retornada pelo global scope -> 404 do route binding
        $this->getJson("/api/v1/despesas/{$despesa->id}")->assertStatus(404);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────

    public function test_update_modifies_only_target_despesa(): void
    {
        $despesa = Despesa::factory()->create([
            'tenant_id'   => $this->user->tenant_id,
            'data_compra' => now()->format('Y-m-d'),
            'valor'       => 100,
        ]);

        Sanctum::actingAs($this->user);

        $resp = $this->putJson("/api/v1/despesas/{$despesa->id}", [
            'valor'       => 250,
            'data_compra' => $despesa->data_compra->format('Y-m-d'),
        ]);

        $resp->assertOk();
        $this->assertEquals(250, $resp->json('data.valor'));
        $this->assertEquals(250, (float) $despesa->fresh()->valor);
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_despesa(): void
    {
        $despesa = Despesa::factory()->create([
            'tenant_id'   => $this->user->tenant_id,
            'data_compra' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/despesas/{$despesa->id}")
             ->assertOk()
             ->assertJsonPath('count', 1);

        $this->assertSoftDeleted('despesas', ['id' => $despesa->id]);
    }

    public function test_destroy_blocked_when_user_lacks_permissoes(): void
    {
        $despesa = Despesa::factory()->create([
            'tenant_id'   => $this->user->tenant_id,
            'data_compra' => now()->format('Y-m-d'),
        ]);

        $membroSemPerm = User::factory()->create([
            'tenant_id'  => $this->user->tenant_id,
            'role'       => 'membro',
            'permissoes' => [],
        ]);

        Sanctum::actingAs($membroSemPerm);

        $this->deleteJson("/api/v1/despesas/{$despesa->id}")->assertStatus(403);
        $this->assertDatabaseHas('despesas', ['id' => $despesa->id, 'deleted_at' => null]);
    }
}
