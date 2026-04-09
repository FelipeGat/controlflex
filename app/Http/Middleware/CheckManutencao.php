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

            // Demais usuários: redirecionar para a página de manutenção
            return redirect()->route('manutencao');
        }

        // Agendada (ainda não iniciou): super admin vê o banner de aviso
        if ($manutencao->isAgendada() && $request->user()?->role === 'super_admin') {
            view()->share('manutencao', $manutencao);
        }

        return $next($request);
    }
}
