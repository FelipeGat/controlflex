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
        ], [
            'titulo.required'              => 'O título é obrigatório.',
            'titulo.max'                   => 'O título não pode ter mais de 200 caracteres.',
            'mensagem.max'                 => 'A mensagem não pode ter mais de 1000 caracteres.',
            'inicio_programado.date'       => 'A data de início é inválida.',
            'fim_programado.date'          => 'A data de fim é inválida.',
            'fim_programado.after'         => 'A data de fim deve ser no futuro.',
            'fim_programado.after_or_equal'=> 'A data de fim deve ser igual ou posterior à data de início.',
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
