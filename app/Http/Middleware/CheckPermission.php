<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $modulo, string $acao): mixed
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // Master has full access
        if ($user->role === 'master') {
            return $next($request);
        }

        // Check member permission
        if (!$user->temPermissao($modulo, $acao)) {
            abort(403, 'Você não tem permissão para realizar esta ação.');
        }

        return $next($request);
    }
}
