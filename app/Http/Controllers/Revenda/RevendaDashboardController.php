<?php

namespace App\Http\Controllers\Revenda;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class RevendaDashboardController extends Controller
{
    public function index()
    {
        $revendaId = Auth::user()->revenda_id;

        $clientes = Tenant::where('revenda_id', $revendaId)
            ->with('plano')
            ->get();

        $totalAtivos   = $clientes->where('status', 'ativo')->count();
        $totalInativos = $clientes->where('status', 'inativo')->count();

        $porPlano = $clientes->groupBy(fn ($c) => $c->plano?->nome ?? 'Sem plano')
            ->map->count()
            ->sortDesc();

        $renovacoes = $clientes
            ->filter(fn ($c) => $c->data_fim_plano !== null)
            ->sortBy('data_fim_plano')
            ->take(20);

        return view('revenda.dashboard', compact(
            'clientes', 'totalAtivos', 'totalInativos', 'porPlano', 'renovacoes'
        ));
    }
}
