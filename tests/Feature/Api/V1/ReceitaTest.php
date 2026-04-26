<?php

namespace Tests\Feature\Api\V1;

use App\Models\Receita;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceitaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'master']);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/receitas')->assertStatus(401);
    }

    public function test_index_returns_only_current_tenant_data(): void
    {
        $other = Tenant::factory()->create();
        Receita::factory()->count(3)->create([
            'tenant_id'                 => $this->user->tenant_id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
        ]);
        Receita::factory()->create([
            'tenant_id'                 => $other->id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        $resp = $this->getJson('/api/v1/receitas');
        $resp->assertOk();
        $this->assertCount(3, $resp->json('data'));
    }

    public function test_index_filters_by_status_recebido(): void
    {
        Receita::factory()->create([
            'tenant_id'                 => $this->user->tenant_id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
            'data_recebimento'          => now()->format('Y-m-d'),
        ]);
        Receita::factory()->create([
            'tenant_id'                 => $this->user->tenant_id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
            'data_recebimento'          => null,
        ]);

        Sanctum::actingAs($this->user);

        $this->assertCount(1, $this->getJson('/api/v1/receitas?status=recebido')->json('data'));
        $this->assertCount(1, $this->getJson('/api/v1/receitas?status=a_receber')->json('data'));
    }

    public function test_store_creates_single_receita(): void
    {
        Sanctum::actingAs($this->user);

        $resp = $this->postJson('/api/v1/receitas', [
            'valor'                     => 1500.00,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
        ]);

        $resp->assertStatus(201)
             ->assertJsonPath('count', 1);
        $this->assertDatabaseCount('receitas', 1);
    }

    public function test_store_creates_recurring_receitas(): void
    {
        Sanctum::actingAs($this->user);

        $resp = $this->postJson('/api/v1/receitas', [
            'valor'                     => 2500,
            'data_prevista_recebimento' => '2026-01-15',
            'parcelas'                  => 6,
            'frequencia'                => 'mensal',
        ]);

        $resp->assertStatus(201)->assertJsonPath('count', 6);
        $this->assertDatabaseCount('receitas', 6);

        // Same valor repeats (no division for receita), grupo same UUID
        $grupo = Receita::value('grupo_recorrencia_id');
        $this->assertNotNull($grupo);
        $this->assertEquals(6, Receita::where('grupo_recorrencia_id', $grupo)->count());
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/receitas', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['valor', 'data_prevista_recebimento']);
    }

    public function test_show_returns_receita(): void
    {
        $receita = Receita::factory()->create([
            'tenant_id'                 => $this->user->tenant_id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/receitas/{$receita->id}")
             ->assertOk()
             ->assertJsonPath('data.id', $receita->id);
    }

    public function test_show_returns_404_for_other_tenant(): void
    {
        $other = Tenant::factory()->create();
        $receita = Receita::factory()->create([
            'tenant_id'                 => $other->id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);
        $this->getJson("/api/v1/receitas/{$receita->id}")->assertStatus(404);
    }

    public function test_update_modifies_receita(): void
    {
        $receita = Receita::factory()->create([
            'tenant_id'                 => $this->user->tenant_id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
            'valor'                     => 100,
        ]);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/receitas/{$receita->id}", [
            'valor'                     => 999.99,
            'data_prevista_recebimento' => $receita->data_prevista_recebimento->format('Y-m-d'),
        ])->assertOk()->assertJsonPath('data.valor', 999.99);

        $this->assertEquals(999.99, (float) $receita->fresh()->valor);
    }

    public function test_destroy_soft_deletes_receita(): void
    {
        $receita = Receita::factory()->create([
            'tenant_id'                 => $this->user->tenant_id,
            'data_prevista_recebimento' => now()->format('Y-m-d'),
        ]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/receitas/{$receita->id}")
             ->assertOk();

        $this->assertSoftDeleted('receitas', ['id' => $receita->id]);
    }
}
