<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\Request;

class PlanoController extends Controller
{
    public function index()
    {
        $planos = Plano::orderBy('preco_mensal')->get();
        return view('admin.planos.index', compact('planos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:planos,slug',
            'preco_mensal' => 'required|numeric|min:0',
            'preco_anual'  => 'required|numeric|min:0',
            'max_usuarios' => 'required|integer',
            'max_bancos'   => 'required|integer',
        ]);

        Plano::create($request->only([
            'nome', 'slug', 'descricao', 'preco_mensal', 'preco_anual',
            'max_usuarios', 'max_bancos', 'ativo',
        ]));

        return back()->with('success', 'Plano criado com sucesso!');
    }

    public function update(Request $request, Plano $plano)
    {
        $request->validate([
            'nome'         => 'required|string|max:255',
            'slug'         => 'required|string|max:100|unique:planos,slug,' . $plano->id,
            'preco_mensal' => 'required|numeric|min:0',
            'preco_anual'  => 'required|numeric|min:0',
            'max_usuarios' => 'required|integer',
            'max_bancos'   => 'required|integer',
        ]);

        $plano->update($request->only([
            'nome', 'slug', 'descricao', 'preco_mensal', 'preco_anual',
            'max_usuarios', 'max_bancos', 'ativo',
        ]));

        return back()->with('success', 'Plano atualizado com sucesso!');
    }

    public function destroy(Plano $plano)
    {
        $plano->delete();
        return back()->with('success', 'Plano removido com sucesso!');
    }
}
