<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Despesa;
use App\Models\Familiar;
use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DespesaController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $inicio = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $fim    = $request->get('fim', now()->endOfMonth()->format('Y-m-d'));

        $baseQuery  = Despesa::where('user_id', $userId)->whereBetween('data_compra', [$inicio, $fim]);
        $totalValor = (clone $baseQuery)->sum('valor');

        $despesas = Despesa::with(['familiar', 'fornecedor', 'categoria', 'banco'])
            ->where('user_id', $userId)
            ->whereBetween('data_compra', [$inicio, $fim])
            ->orderByDesc('data_compra')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $categorias   = Categoria::where('user_id', $userId)->where('tipo', 'DESPESA')->orderBy('nome')->get();
        $familiares   = Familiar::where('user_id', $userId)->orderBy('nome')->get();
        $fornecedores = Fornecedor::where('user_id', $userId)->orderBy('nome')->get();
        $bancos       = Banco::where('user_id', $userId)->orderBy('nome')->get();

        return view('despesas.index', compact('despesas', 'totalValor', 'categorias', 'familiares', 'fornecedores', 'bancos', 'inicio', 'fim'));
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        $request->validate([
            'valor'           => 'required|numeric|min:0.01',
            'data_compra'     => 'required|date',
            'data_pagamento'  => 'nullable|date',
            'categoria_id'    => ['nullable', Rule::exists('categorias', 'id')->where('user_id', $userId)],
            'quem_comprou'    => ['nullable', Rule::exists('familiares', 'id')->where('user_id', $userId)],
            'onde_comprou'    => ['nullable', Rule::exists('fornecedores', 'id')->where('user_id', $userId)],
            'forma_pagamento' => ['nullable', Rule::exists('bancos', 'id')->where('user_id', $userId)],
            'parcelas'        => 'nullable|integer|min:0|max:360',
            'frequencia'      => 'nullable|in:diaria,semanal,quinzenal,mensal,trimestral,semestral,anual',
        ]);

        $total = Despesa::criarComRecorrencia($request->all(), $userId);

        return back()->with('success', "{$total} despesa(s) salva(s) com sucesso!");
    }

    public function update(Request $request, Despesa $despesa)
    {
        $this->authorize('update', $despesa);

        $userId = Auth::id();

        $request->validate([
            'valor'           => 'required|numeric|min:0.01',
            'data_compra'     => 'required|date',
            'data_pagamento'  => 'nullable|date',
            'categoria_id'    => ['nullable', Rule::exists('categorias', 'id')->where('user_id', $userId)],
            'quem_comprou'    => ['nullable', Rule::exists('familiares', 'id')->where('user_id', $userId)],
            'onde_comprou'    => ['nullable', Rule::exists('fornecedores', 'id')->where('user_id', $userId)],
            'forma_pagamento' => ['nullable', Rule::exists('bancos', 'id')->where('user_id', $userId)],
            'observacoes'     => 'nullable|string|max:2000',
        ]);

        $escopo = $request->get('escopo', 'apenas_esta');

        if ($escopo === 'esta_e_futuras' && $despesa->grupo_recorrencia_id) {
            Despesa::where('user_id', $userId)
                ->where('grupo_recorrencia_id', $despesa->grupo_recorrencia_id)
                ->where('data_compra', '>=', $despesa->data_compra)
                ->update([
                    'quem_comprou'    => $request->quem_comprou,
                    'onde_comprou'    => $request->onde_comprou,
                    'categoria_id'    => $request->categoria_id,
                    'forma_pagamento' => $request->forma_pagamento,
                    'valor'           => $request->valor,
                    'data_pagamento'  => $request->data_pagamento ?: null,
                    'observacoes'     => $request->observacoes,
                ]);
        } else {
            $despesa->update([
                'quem_comprou'    => $request->quem_comprou,
                'onde_comprou'    => $request->onde_comprou,
                'categoria_id'    => $request->categoria_id,
                'forma_pagamento' => $request->forma_pagamento,
                'valor'           => $request->valor,
                'data_compra'     => $request->data_compra,
                'data_pagamento'  => $request->data_pagamento ?: null,
                'observacoes'     => $request->observacoes,
            ]);
        }

        return back()->with('success', 'Despesa atualizada com sucesso!');
    }

    public function destroy(Request $request, Despesa $despesa)
    {
        $this->authorize('delete', $despesa);

        $userId = Auth::id();
        $escopo = $request->get('escopo', 'apenas_esta');

        if ($escopo === 'esta_e_futuras' && $despesa->grupo_recorrencia_id) {
            $count = Despesa::where('user_id', $userId)
                ->where('grupo_recorrencia_id', $despesa->grupo_recorrencia_id)
                ->where('data_compra', '>=', $despesa->data_compra)
                ->delete();

            return back()->with('success', "{$count} despesa(s) excluída(s)!");
        }

        $despesa->delete();

        return back()->with('success', 'Despesa excluída com sucesso!');
    }
}
