<?php

namespace Tests\Feature\Api\V1;

use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Familiar;
use App\Models\Fornecedor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    public function test_categorias_returns_only_current_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA   = User::factory()->create(['tenant_id' => $tenantA->id]);

        Categoria::factory()->create(['tenant_id' => $tenantA->id, 'nome' => 'Mercado',  'tipo' => 'DESPESA']);
        Categoria::factory()->create(['tenant_id' => $tenantA->id, 'nome' => 'Salário',  'tipo' => 'RECEITA']);
        Categoria::factory()->create(['tenant_id' => $tenantB->id, 'nome' => 'Vazamento', 'tipo' => 'DESPESA']);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/categorias');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('nome')->all();
        $this->assertContains('Mercado',  $names);
        $this->assertContains('Salário',  $names);
        $this->assertNotContains('Vazamento', $names);
    }

    public function test_categorias_filter_by_tipo(): void
    {
        $user = User::factory()->create();

        Categoria::factory()->despesa()->create(['tenant_id' => $user->tenant_id, 'nome' => 'Mercado']);
        Categoria::factory()->receita()->create(['tenant_id' => $user->tenant_id, 'nome' => 'Salário']);

        Sanctum::actingAs($user);

        $resp = $this->getJson('/api/v1/categorias?tipo=DESPESA');
        $resp->assertOk();
        $names = collect($resp->json('data'))->pluck('nome')->all();
        $this->assertContains('Mercado', $names);
        $this->assertNotContains('Salário', $names);
    }

    public function test_familiares_fornecedores_bancos_endpoints_return_only_tenant_data(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $userA   = User::factory()->create(['tenant_id' => $tenantA->id]);

        Familiar::factory()->create(['tenant_id'   => $tenantA->id, 'nome' => 'Felipe']);
        Familiar::factory()->create(['tenant_id'   => $tenantB->id, 'nome' => 'Outro']);
        Fornecedor::factory()->create(['tenant_id' => $tenantA->id, 'nome' => 'Mercado']);
        Fornecedor::factory()->create(['tenant_id' => $tenantB->id, 'nome' => 'Vazado']);
        Banco::factory()->create(['tenant_id'      => $tenantA->id, 'nome' => 'Itau']);
        Banco::factory()->create(['tenant_id'      => $tenantB->id, 'nome' => 'OutroBanco']);

        Sanctum::actingAs($userA);

        $this->assertContains('Felipe',
            collect($this->getJson('/api/v1/familiares')->json('data'))->pluck('nome')->all());
        $this->assertNotContains('Outro',
            collect($this->getJson('/api/v1/familiares')->json('data'))->pluck('nome')->all());

        $this->assertContains('Mercado',
            collect($this->getJson('/api/v1/fornecedores')->json('data'))->pluck('nome')->all());
        $this->assertNotContains('Vazado',
            collect($this->getJson('/api/v1/fornecedores')->json('data'))->pluck('nome')->all());

        $this->assertContains('Itau',
            collect($this->getJson('/api/v1/bancos')->json('data'))->pluck('nome')->all());
        $this->assertNotContains('OutroBanco',
            collect($this->getJson('/api/v1/bancos')->json('data'))->pluck('nome')->all());
    }

    public function test_catalogos_require_authentication(): void
    {
        foreach (['categorias', 'familiares', 'fornecedores', 'bancos'] as $route) {
            $this->getJson("/api/v1/{$route}")->assertStatus(401);
        }
    }
}
