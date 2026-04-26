<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_dashboard_with_valid_token_returns_snapshot_structure(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'cached_at',
                'periodo' => ['inicio', 'fim', 'mes'],
                'kpis'    => ['receitas', 'despesas', 'saldo'],
                'bancos',
                'cartoes',
                'lancamentos',
                'totais'  => ['saldo_contas', 'fatura_total', 'limite_total'],
            ]);
    }

    public function test_dashboard_returns_zero_kpis_for_empty_tenant(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();

        $this->assertEquals(0, $response->json('kpis.receitas'));
        $this->assertEquals(0, $response->json('kpis.despesas'));
        $this->assertEquals(0, $response->json('kpis.saldo'));
        $this->assertEquals([], $response->json('bancos'));
        $this->assertEquals([], $response->json('cartoes'));
    }

    public function test_dashboard_validates_date_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?inicio=invalid-date');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inicio']);
    }

    public function test_dashboard_validates_date_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?inicio=2026-04-15&fim=2026-04-10');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fim']);
    }

    public function test_dashboard_blocks_user_with_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create(['ativo' => false]);
        $user   = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role'      => 'master',
            'ativo'     => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(403)
            ->assertJsonPath('code', 'tenant_inactive');
    }

    public function test_dashboard_blocks_inactive_user(): void
    {
        $user = User::factory()->create(['ativo' => false]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(403)
            ->assertJsonPath('code', 'tenant_inactive');
    }
}
