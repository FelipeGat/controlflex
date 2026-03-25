<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Database\Seeders\FornecedoresDefaultSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FornecedorController extends Controller
{
    public function index()
    {
        $fornecedores = Fornecedor::orderBy('grupo')->orderBy('nome')->get()
            ->groupBy(fn($f) => $f->grupo ?: 'Outros');
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
            'user_id'     => Auth::id(),
            'nome'        => $request->nome,
            'icone'       => $request->icone ?: 'fa-store',
            'grupo'       => $request->grupo ?: null,
            'contato'     => $request->contato,
            'cnpj'        => $request->cnpj,
            'telefone'    => $request->telefone,
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

        $fornecedor->update($request->only(['nome', 'icone', 'grupo', 'contato', 'cnpj', 'telefone', 'observacoes']));

        return back()->with('success', 'Fornecedor atualizado com sucesso!');
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $this->authorize('delete', $fornecedor);
        $fornecedor->delete();
        return back()->with('success', 'Fornecedor excluído com sucesso!');
    }

    public function importarPadrao()
    {
        $user = Auth::user();
        FornecedoresDefaultSeeder::seedParaTenant($user->tenant_id, $user->id);
        return back()->with('success', 'Fornecedores padrão importados com sucesso!');
    }
}
