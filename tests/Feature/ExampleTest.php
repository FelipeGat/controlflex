<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Raiz redireciona para login quando não autenticado
     * (AlfaHome não tem landing page pública).
     */
    public function test_root_redirects_to_login_when_guest(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
