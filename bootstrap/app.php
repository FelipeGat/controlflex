<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Trust Docker internal network (nginx container) and loopback only.
// Cloudflare real IP is resolved by nginx (set_real_ip_from) before reaching Laravel.
Request::setTrustedProxies(
    ['172.0.0.0/8', '10.0.0.0/8', '127.0.0.1'],
    Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permissao'    => \App\Http\Middleware\CheckPermission::class,
            'tenant.ativo' => \App\Http\Middleware\EnsureTenantActive::class,
            'role'         => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
