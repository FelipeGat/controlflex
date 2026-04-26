<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stateless tenant guard for API requests.
 *
 * Mirrors EnsureTenantActive but emits JSON errors and revokes the
 * current personal access token instead of invalidating a session.
 */
class EnsureTenantActiveApi
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $reason = $this->inactivityReason($user);

        if ($reason !== null) {
            // Revoke the token used in this request so the device is forced to re-auth
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            return response()->json([
                'message' => $reason,
                'code'    => 'tenant_inactive',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function inactivityReason($user): ?string
    {
        if (! $user->ativo) {
            return 'Sua conta está desativada. Entre em contato com o administrador.';
        }

        if ($user->role === 'super_admin') {
            return null;
        }

        if ($user->role === 'admin_revenda') {
            if (! $user->revenda_id || ! $user->revenda?->isAtivo()) {
                return 'Sua revenda está desativada. Entre em contato com o administrador.';
            }
            return null;
        }

        if (! $user->tenant_id || ! $user->tenant?->ativo) {
            return 'Seu tenant está desativado. Entre em contato com o administrador.';
        }

        return null;
    }
}
