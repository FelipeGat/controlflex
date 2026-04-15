<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_token_mismatch_on_logout_still_logs_user_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = \Illuminate\Http\Request::create('/logout', 'POST');
        $request->setLaravelSession($this->app['session']->driver());

        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF token mismatch.'));

        $this->assertGuest();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringEndsWith('/login', $response->headers->get('Location'));
    }

    public function test_token_mismatch_on_other_routes_redirects_back_with_error(): void
    {
        $request = \Illuminate\Http\Request::create('/some-form', 'POST');
        $request->headers->set('referer', 'http://localhost/some-form');
        $request->setLaravelSession($this->app['session']->driver());

        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'CSRF token mismatch.'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            'Sua sessão expirou. Recarregue a página e tente novamente.',
            session('error')
        );
    }
}
