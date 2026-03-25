<?php

namespace App\Http\Controllers;

use App\Models\Receita;
use App\Models\Categoria;
use App\Models\Familiar;
use App\Models\Banco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceitaController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $inicio = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $fim = $request->get('fim', now()->endOfMonth()->format('Y-m-d'));

        $totalValor = Receita::where('user_id', $userId)->whereBetween('data_prevista_recebimento', [$inicio, $fim])->sum('valor');

        $receitas = Receita::with(['familiar', 'categoria', 'banco'])
            ->where('user_id', $userId)
            ->whereBetween('data_prevista_recebimento', [$inicio, $fim])
            ->orderByDesc('data_prevista_recebimento')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $categorias = Categoria::where('user_id', $userId)->where('tipo', 'RECEITA')->orderBy('nome')->get();
        $familiares = Familiar::where('user_id', $userId)->orderBy('nome')->get();
        $bancos = Banco::where('user_id', $userId)->orderBy('nome')->get();

        return view('receitas.index', compact('receitas', 'totalValor', 'categorias', 'familiares', 'bancos', 'inicio', 'fim'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'data_prevista_recebimento' => 'required|date',
            'categoria_id' => 'nullable|exists:categorias,id',
            'quem_recebeu' => 'nullable|exists:familiares,id',
            'forma_recebimento' => 'nullable|exists:bancos,id',
        ]);

        $total = Receita::criarComRecorrencia($request->all(), Auth::id());

        return back()->with('success', "{$total} receita(s) salva(s) com sucesso!");
    }

    public function update(Request $request, Receita $receita)
    {
        $this->authorize('update', $receita);

        $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'data_prevista_recebimento' => 'required|date',
        ]);

        $escopo = $request->get('escopo', 'apenas_esta');

        if ($escopo === 'esta_e_futuras' && $receita->grupo_recorrencia_id) {
            Receita::where('user_id', Auth::id())
                ->where('grupo_recorrencia_id', $receita->grupo_recorrencia_id)
                ->where('data_prevista_recebimento', '>=', $receita->data_prevista_recebimento)
                ->update([
                    'quem_recebeu' => $request->quem_recebeu,
                    'categoria_id' => $request->categoria_id,
                    'forma_recebimento' => $request->forma_recebimento,
                    'valor' => $request->valor,
                    'data_recebimento' => $request->data_recebimento ?: null,
                    'observacoes' => $request->observacoes,
                ]);
        } else {
            $receita->update([
                'quem_recebeu' => $request->quem_recebeu,
                'categoria_id' => $request->categoria_id,
                'forma_recebimento' => $request->forma_recebimento,
                'valor' => $request->valor,
                'data_prevista_recebimento' => $request->data_prevista_recebimento,
                'data_recebimento' => $request->data_recebimento ?: null,
                'observacoes' => $request->observacoes,
            ]);
        }

        return back()->with('success', 'Receita atualizada com sucesso!');
    }

    public function destroy(Request $request, Receita $receita)
    {
        $this->authorize('delete', $receita);

        $escopo = $request->get('escopo', 'apenas_esta');

        if ($escopo === 'esta_e_futuras' && $receita->grupo_recorrencia_id) {
            $count = Receita::where('user_id', Auth::id())
                ->where('grupo_recorrencia_id', $receita->grupo_recorrencia_id)
                ->where('data_prevista_recebimento', '>=', $receita->data_prevista_recebimento)
                ->delete();
            return back()->with('success', "{$count} receita(s) excluída(s)!");
        }

        $receita->delete();
        return back()->with('success', 'Receita excluída com sucesso!');
    }
}
