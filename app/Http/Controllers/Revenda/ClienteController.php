<?php

namespace App\Http\Controllers\Revenda;

use App\Http\Controllers\Controller;
use App\Models\Familiar;
use App\Models\Plano;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CategoriasDefaultSeeder;
use Database\Seeders\FornecedoresDefaultSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            ->with('master', 'plano')
            ->withCount('users')
            ->orderByDesc('created_at')
            ->get();

        $planos = Plano::where('ativo', true)->orderBy('preco_mensal')->get();

        return view('revenda.clientes.index', compact('clientes', 'planos'));
    }

    public function store(Request $request)
    {
        $revendaId = Auth::user()->revenda_id;

        $request->validate([
            'nome_cliente'   => 'required|string|max:255',
            'nome_master'    => 'required|string|max:255',
            'email_master'   => 'required|email|unique:users,email',
            'senha_master'   => ['required', Rules\Password::defaults()],
            'plano_id'       => 'required|exists:planos,id',
            'tipo_cobranca'  => 'required|in:mensal,anual',
        ]);

        $diasPlano = $request->tipo_cobranca === 'anual' ? 365 : 30;

        DB::transaction(function () use ($request, $revendaId, $diasPlano) {
            $tenant = Tenant::create([
                'nome'              => $request->nome_cliente,
                'ativo'             => true,
                'status'            => 'ativo',
                'revenda_id'        => $revendaId,
                'plano_id'          => $request->plano_id,
                'tipo_cobranca'     => $request->tipo_cobranca,
                'data_inicio_plano' => Carbon::today(),
                'data_fim_plano'    => Carbon::today()->addDays($diasPlano),
            ]);

            $master = User::create([
                'name'      => $request->nome_master,
                'email'     => $request->email_master,
                'password'  => Hash::make($request->senha_master),
                'tenant_id' => $tenant->id,
                'role'      => 'master',
                'ativo'     => true,
            ]);

            $familiar = Familiar::create([
                'tenant_id'     => $tenant->id,
                'user_id'       => $master->id,
                'nome'          => $request->nome_master,
                'salario'       => 0,
                'limite_cartao' => 0,
                'limite_cheque' => 0,
            ]);

            $master->update(['familiar_id' => $familiar->id]);

            CategoriasDefaultSeeder::seedParaTenant($tenant->id, $master->id);
            FornecedoresDefaultSeeder::seedParaTenant($tenant->id, $master->id);
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
            'nome'          => 'required|string|max:255',
            'status'        => 'required|in:ativo,inativo',
            'plano_id'      => 'nullable|exists:planos,id',
            'tipo_cobranca' => 'nullable|in:mensal,anual',
        ]);

        $tenant->update([
            'nome'          => $request->nome,
            'status'        => $request->status,
            'ativo'         => $request->status === 'ativo',
            'plano_id'      => $request->plano_id ?? $tenant->plano_id,
            'tipo_cobranca' => $request->tipo_cobranca ?? $tenant->tipo_cobranca,
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

    public function renovar(Tenant $tenant)
    {
        $revendaId = Auth::user()->revenda_id;

        if ($tenant->revenda_id !== $revendaId) {
            abort(403);
        }

        $diasPlano = $tenant->tipo_cobranca === 'anual' ? 365 : 30;

        $tenant->update([
            'data_inicio_plano' => Carbon::today(),
            'data_fim_plano'    => Carbon::today()->addDays($diasPlano),
        ]);

        return back()->with('success', 'Licença renovada com sucesso!');
    }
}
