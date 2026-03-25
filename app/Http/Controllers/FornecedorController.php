<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FornecedorController extends Controller
{
    public function index()
    {
        $fornecedores = Fornecedor::orderBy('nome')->paginate(20);
        return view('fornecedores.index', compact('fornecedores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:150',
            'contato' => 'nullable|string|max:100',
            'cnpj' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'observacoes' => 'nullable|string',
        ]);

        Fornecedor::create([
            'user_id' => Auth::id(),
            'nome' => $request->nome,
            'contato' => $request->contato,
            'cnpj' => $request->cnpj,
            'telefone' => $request->telefone,
            'observacoes' => $request->observacoes,
        ]);

        return back()->with('success', 'Fornecedor cadastrado com sucesso!');
    }

    public function update(Request $request, Fornecedor $fornecedor)
    {
        $this->authorize('update', $fornecedor);

        $request->validate([
            'nome' => 'required|string|max:150',
            'contato' => 'nullable|string|max:100',
            'cnpj' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'observacoes' => 'nullable|string',
        ]);

        $fornecedor->update($request->only(['nome', 'contato', 'cnpj', 'telefone', 'observacoes']));

        return back()->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $this->authorize('delete', $fornecedor);
        $fornecedor->delete();
        return back()->with('success', 'Fornecedor excluído com sucesso!');
    }
}
