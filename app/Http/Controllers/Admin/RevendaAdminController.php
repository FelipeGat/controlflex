<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Revenda;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RevendaAdminController extends Controller
{
    public function index()
    {
        $revendas = Revenda::with('admin')
            ->withCount('tenants')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.revendas.index', compact('revendas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'     => 'required|string|max:255',
            'cnpj'     => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
        ]);

        Revenda::create($request->only(['nome', 'cnpj', 'email', 'telefone', 'status']));

        return back()->with('success', 'Revenda criada com sucesso!');
    }

    public function update(Request $request, Revenda $revenda)
    {
        $request->validate([
            'nome'     => 'required|string|max:255',
            'cnpj'     => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'status'   => 'required|in:ativo,inativo',
        ]);

        $revenda->update($request->only(['nome', 'cnpj', 'email', 'telefone', 'status']));

        return back()->with('success', 'Revenda atualizada com sucesso!');
    }

    public function destroy(Revenda $revenda)
    {
        $revenda->delete();
        return back()->with('success', 'Revenda removida com sucesso!');
    }

    public function provisionar(Request $request)
    {
        $request->validate([
            'nome_revenda'  => 'required|string|max:255',
            'cnpj'          => 'nullable|string|max:20',
            'email_revenda' => 'nullable|email|max:255',
            'telefone'      => 'nullable|string|max:20',
            'nome_admin'    => 'required|string|max:255',
            'email_admin'   => 'required|email|unique:users,email',
            'senha_admin'   => ['required', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($request) {
            $revenda = Revenda::create([
                'nome'     => $request->nome_revenda,
                'cnpj'     => $request->cnpj,
                'email'    => $request->email_revenda,
                'telefone' => $request->telefone,
                'status'   => 'ativo',
            ]);

            User::create([
                'name'       => $request->nome_admin,
                'email'      => $request->email_admin,
                'password'   => Hash::make($request->senha_admin),
                'revenda_id' => $revenda->id,
                'role'       => 'admin_revenda',
                'ativo'      => true,
            ]);
        });

        return back()->with('success', 'Revenda provisionada com sucesso!');
    }

    public function resetSenha(Request $request, Revenda $revenda)
    {
        $request->validate([
            'nova_senha' => ['required', Rules\Password::defaults()],
        ]);

        $admin = $revenda->admin;

        if (!$admin) {
            return back()->with('error', 'Nenhum administrador encontrado para esta revenda.');
        }

        $admin->update(['password' => Hash::make($request->nova_senha)]);

        return back()->with('success', 'Senha do administrador redefinida com sucesso!');
    }
}
