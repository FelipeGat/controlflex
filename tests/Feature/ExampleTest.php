<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Raiz serve a landing page pública quando não autenticado.
     */
    public function test_root_serves_landing_when_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('AlfaHome', false);
    }
}
