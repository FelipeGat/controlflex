<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Super Admin não tem tenant — skip
        if ($user->role === 'super_admin') {
            if (!$user->ativo) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Sua conta está desativada.',
                ]);
            }
            return $next($request);
        }

        // Admin Revenda — verificar revenda ativa
        if ($user->role === 'admin_revenda') {
            if (!$user->ativo || !$user->revenda_id || !$user->revenda?->isAtivo()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors([
                    'email' => 'Sua conta ou revenda está desativada. Entre em contato com o administrador.',
                ]);
            }
            return $next($request);
        }

        // Master/Membro — verificar tenant ativo
        if (!$user->ativo || !$user->tenant_id || !$user->tenant?->ativo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Sua conta está desativada. Entre em contato com o administrador.',
            ]);
        }

        return $next($request);
    }
}
