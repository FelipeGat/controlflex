<?php

namespace App\Http\Controllers\Revenda;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\BancosDefaultSeeder;
use Database\Seeders\CategoriasDefaultSeeder;
use Database\Seeders\FornecedoresDefaultSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ClienteController extends Controller
{
    public function index()
    {
        $revendaId = Auth::user()->revenda_id;

        $clientes = Tenant::where('revenda_id', $revendaId)
            ->with('master')
            ->withCount('users')
            ->orderByDesc('created_at')
            ->get();

        return view('revenda.clientes.index', compact('clientes'));
    }

    public function store(Request $request)
    {
        $revendaId = Auth::user()->revenda_id;

        $request->validate([
            'nome_cliente'  => 'required|string|max:255',
            'nome_master'   => 'required|string|max:255',
            'email_master'  => 'required|email|unique:users,email',
            'senha_master'  => ['required', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($request, $revendaId) {
            $tenant = Tenant::create([
                'nome'       => $request->nome_cliente,
                'plano'      => 'basic',
                'ativo'      => true,
                'status'     => 'ativo',
                'revenda_id' => $revendaId,
            ]);

            $master = User::create([
                'name'      => $request->nome_master,
                'email'     => $request->email_master,
                'password'  => Hash::make($request->senha_master),
                'tenant_id' => $tenant->id,
                'role'      => 'master',
                'ativo'     => true,
            ]);

            CategoriasDefaultSeeder::seedParaTenant($tenant->id, $master->id);
            FornecedoresDefaultSeeder::seedParaTenant($tenant->id, $master->id);
            BancosDefaultSeeder::seedParaTenant($tenant->id, $master->id);
        });

        return back()->with('success', 'Cliente criado com sucesso!');
    }

    public function update(Request $request, Tenant $tenant)
    {
        $revendaId = Auth::user()->revenda_id;

        if ($tenant->revenda_id !== $revendaId) {
            abort(403);
        }

        $request->validate([
            'nome'   => 'required|string|max:255',
            'status' => 'required|in:ativo,inativo',
        ]);

        $tenant->update([
            'nome'   => $request->nome,
            'status' => $request->status,
            'ativo'  => $request->status === 'ativo',
        ]);

        return back()->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Tenant $tenant)
    {
        $revendaId = Auth::user()->revenda_id;

        if ($tenant->revenda_id !== $revendaId) {
            abort(403);
        }

        $tenant->delete();

        return back()->with('success', 'Cliente removido com sucesso!');
    }

    public function resetSenha(Request $request, Tenant $tenant)
    {
        $revendaId = Auth::user()->revenda_id;

        if ($tenant->revenda_id !== $revendaId) {
            abort(403);
        }

        $request->validate([
            'nova_senha' => ['required', Rules\Password::defaults()],
        ]);

        $master = $tenant->master;

        if (!$master) {
            return back()->with('error', 'Nenhum usuário master encontrado para este cliente.');
        }

        $master->update(['password' => Hash::make($request->nova_senha)]);

        return back()->with('success', 'Senha do master redefinida com sucesso!');
    }
}
