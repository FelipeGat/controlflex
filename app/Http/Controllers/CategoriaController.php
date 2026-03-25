<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::where('user_id', Auth::id())->orderBy('tipo')->orderBy('nome')->get();
        return view('categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:RECEITA,DESPESA',
            'icone' => 'nullable|string|max:50',
        ]);

        Categoria::create([
            'user_id' => Auth::id(),
            'nome' => $request->nome,
            'tipo' => $request->tipo,
            'icone' => $request->icone ?: 'fa-tag',
        ]);

        return back()->with('success', 'Categoria criada com sucesso!');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $this->authorize('update', $categoria);

        $request->validate([
            'nome' => 'required|string|max:100',
            'tipo' => 'required|in:RECEITA,DESPESA',
            'icone' => 'nullable|string|max:50',
        ]);

        $categoria->update([
            'nome' => $request->nome,
            'tipo' => $request->tipo,
            'icone' => $request->icone ?: 'fa-tag',
        ]);

        return back()->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        $this->authorize('delete', $categoria);
        $categoria->delete();
        return back()->with('success', 'Categoria excluída com sucesso!');
    }
}
