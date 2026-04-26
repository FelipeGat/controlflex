<?php

namespace Tests\Feature\Api\V1;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'email'    => 'felipe@test.com',
            'password' => bcrypt('senha-secreta'),
            'role'     => 'master',
            'ativo'    => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'       => 'felipe@test.com',
            'password'    => 'senha-secreta',
            'device_name' => 'pixel-7',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'nome', 'email', 'role', 'tenant_id', 'ativo'],
            ])
            ->assertJsonPath('user.email', 'felipe@test.com')
            ->assertJsonPath('user.role', 'master');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
            'name'           => 'pixel-7',
        ]);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        User::factory()->create([
            'email'    => 'felipe@test.com',
            'password' => bcrypt('senha-correta'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'felipe@test.com',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_with_nonexistent_email_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'naoexiste@test.com',
            'password' => 'qualquer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_inactive_user_returns_422(): void
    {
        User::factory()->create([
            'email'    => 'felipe@test.com',
            'password' => bcrypt('senha'),
            'ativo'    => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'felipe@test.com',
            'password' => 'senha',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_inactive_tenant_returns_422(): void
    {
        $tenant = Tenant::factory()->create(['ativo' => false]);
        User::factory()->create([
            'email'     => 'felipe@test.com',
            'password'  => bcrypt('senha'),
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'felipe@test.com',
            'password' => 'senha',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_uses_default_device_name_when_not_provided(): void
    {
        User::factory()->create([
            'email'    => 'felipe@test.com',
            'password' => bcrypt('senha'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => 'felipe@test.com',
            'password' => 'senha',
        ])->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'mobile-app',
        ]);
    }
}
