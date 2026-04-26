<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_without_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();

        $tokenA = $user->createToken('iphone-15')->plainTextToken;
        $tokenB = $user->createToken('pixel-7')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => "Bearer {$tokenA}",
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Sessão encerrada com sucesso.']);

        // Token A revoked, Token B still works
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'pixel-7']);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'iphone-15']);

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => "Bearer {$tokenB}",
        ])->assertOk();
    }

    public function test_logout_all_revokes_every_token_of_the_user(): void
    {
        $user = User::factory()->create();
        $token  = $user->createToken('iphone-15')->plainTextToken;
        $user->createToken('pixel-7');
        $user->createToken('ipad-pro');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $response = $this->postJson('/api/v1/auth/logout-all', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Todas as sessões foram encerradas.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_all_does_not_affect_other_users_tokens(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tokenA = $userA->createToken('a-device')->plainTextToken;
        $userB->createToken('b-device');
        $userB->createToken('b-device-2');

        $this->postJson('/api/v1/auth/logout-all', [], [
            'Authorization' => "Bearer {$tokenA}",
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id'   => $userA->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_revoked_token_is_rejected_on_subsequent_requests(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('device')->plainTextToken;

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        // Force the auth guard to re-resolve the user from the (now revoked)
        // token on the next request — without this, the test container caches
        // the previously-authenticated user across requests.
        Auth::forgetGuards();

        $this->getJson('/api/v1/auth/me', [
            'Authorization' => "Bearer {$token}",
        ])->assertStatus(401);
    }
}
