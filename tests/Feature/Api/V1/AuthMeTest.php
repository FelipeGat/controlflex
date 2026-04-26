<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_without_token_returns_401_json(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_me_with_invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me', [
            'Authorization' => 'Bearer token-falso-9999',
        ]);

        $response->assertStatus(401);
    }

    public function test_me_with_valid_token_returns_user_data(): void
    {
        $user = User::factory()->create([
            'email' => 'felipe@test.com',
            'role'  => 'master',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'felipe@test.com')
            ->assertJsonPath('data.role', 'master')
            ->assertJsonPath('data.is_master', true)
            ->assertJsonStructure([
                'data' => ['id', 'nome', 'email', 'foto_url', 'role', 'tenant_id', 'familiar_id', 'permissoes', 'ativo', 'is_master'],
            ]);
    }

    public function test_me_does_not_leak_password_or_remember_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk();
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }
}
