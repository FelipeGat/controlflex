<?php

namespace App\Http\Middleware;

use App\Models\ManutencaoProgramada;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManutencao
{
    public function handle(Request $request, Closure $next): Response
    {
        $manutencao = ManutencaoProgramada::getInstance();

        if ($manutencao->isAtiva()) {
            // Super admin continua navegando normalmente
            if ($request->user()?->role === 'super_admin') {
                view()->share('manutencao', $manutencao);
                return $next($request);
            }

            // Demais usuários: fazer logout e redirecionar para o login com aviso
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', '🔧 ' . ($manutencao->titulo ?? 'Sistema em manutenção') . '. ' . ($manutencao->mensagem ?? 'Voltaremos em breve!'));
        }

        // Agendada (ainda não iniciou): super admin vê o banner de aviso
        if ($manutencao->isAgendada() && $request->user()?->role === 'super_admin') {
            view()->share('manutencao', $manutencao);
        }

        return $next($request);
    }
}
