<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Investimento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InvestimentoController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $investimentos = Investimento::with('banco')
            ->orderByDesc('data_aporte')
            ->paginate(20);

        $bancos         = Banco::orderBy('nome')->get();
        $totalInvestido = Investimento::sum('valor_aportado');

        return view('investimentos.index', compact('investimentos', 'bancos', 'totalInvestido'));
    }

    public function store(Request $request)
    {
        $userId   = Auth::id();
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'nome_ativo'        => 'required|string|max:150',
            'tipo_investimento' => 'required|string|max:100',
            'data_aporte'       => 'required|date',
            'valor_aportado'    => 'required|numeric|min:0.01',
            'quantidade_cotas'  => 'nullable|numeric|min:0',
            'banco_id'          => ['nullable', Rule::exists('bancos', 'id')->where('tenant_id', $tenantId)],
        ]);

        Investimento::create([
            'user_id'           => $userId,
            'banco_id'          => $request->banco_id,
            'nome_ativo'        => $request->nome_ativo,
            'tipo_investimento' => $request->tipo_investimento,
            'data_aporte'       => $request->data_aporte,
            'valor_aportado'    => $request->valor_aportado,
            'quantidade_cotas'  => $request->quantidade_cotas ?? 0,
            'observacoes'       => $request->observacoes,
        ]);

        return back()->with('success', 'Investimento registrado com sucesso!');
    }

    public function update(Request $request, Investimento $investimento)
    {
        $this->authorize('update', $investimento);

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'nome_ativo'        => 'required|string|max:150',
            'tipo_investimento' => 'required|string|max:100',
            'data_aporte'       => 'required|date',
            'valor_aportado'    => 'required|numeric|min:0.01',
            'quantidade_cotas'  => 'nullable|numeric|min:0',
            'banco_id'          => ['nullable', Rule::exists('bancos', 'id')->where('tenant_id', $tenantId)],
        ]);

        $investimento->update($request->only([
            'nome_ativo', 'tipo_investimento', 'data_aporte',
            'valor_aportado', 'quantidade_cotas', 'banco_id', 'observacoes',
        ]));

        return back()->with('success', 'Investimento atualizado com sucesso!');
    }

    public function destroy(Investimento $investimento)
    {
        $this->authorize('delete', $investimento);
        $investimento->delete();

        return back()->with('success', 'Investimento excluído com sucesso!');
    }
}
