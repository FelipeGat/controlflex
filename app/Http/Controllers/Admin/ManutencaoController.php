<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManutencaoProgramada;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ManutencaoController extends Controller
{
    public function index(): View
    {
        $manutencao = ManutencaoProgramada::getInstance();
        return view('admin.manutencao.index', compact('manutencao'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'ativo'             => 'boolean',
            'titulo'            => 'required|string|max:200',
            'mensagem'          => 'nullable|string|max:1000',
            'inicio_programado' => 'nullable|date',
            'fim_programado'    => 'nullable|date|after:now|after_or_equal:inicio_programado',
        ]);

        $manutencao = ManutencaoProgramada::getInstance();
        $manutencao->update([
            'ativo'             => $request->boolean('ativo'),
            'titulo'            => $request->titulo,
            'mensagem'          => $request->mensagem,
            'inicio_programado' => $request->inicio_programado ?: null,
            'fim_programado'    => $request->fim_programado ?: null,
            'criado_por'        => Auth::user()->email,
        ]);

        $status = $manutencao->fresh()->isAtiva() ? 'ativada' : ($request->boolean('ativo') ? 'agendada' : 'desativada');

        return back()->with('success', "Manutenção {$status} com sucesso.");
    }
}
