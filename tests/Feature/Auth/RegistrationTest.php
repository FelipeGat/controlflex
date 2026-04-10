<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_new_users_can_register_with_coupon(): void
    {
        // Cria tenant e cupom referenciador
        $tenant = \App\Models\Tenant::create(['nome' => 'Referrer', 'ativo' => true, 'status' => 'ativo']);
        $cupom = \App\Models\CupomIndicacao::create([
            'tenant_id' => $tenant->id,
            'codigo' => 'ALEFE',
        ]);

        $this->post('/register', [
            'name' => 'Novo User',
            'email' => 'novo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'cupom' => 'ALEFE',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('indicacoes', ['cupom_id' => $cupom->id]);
        $cupom->refresh();
        $this->assertEquals(1, $cupom->creditos_disponiveis);
    }
}
