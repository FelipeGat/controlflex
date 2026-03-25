<?php
namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class MembroController extends Controller
{
    private function somenteMaster(): void
    {
        if (Auth::user()->role !== 'master') {
            abort(403);
        }
    }

    public function index()
    {
        $this->somenteMaster();
        $membros = User::where('tenant_id', Auth::user()->tenant_id)
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();

        return view('membros.index', compact('membros'));
    }

    public function store(Request $request)
    {
        $this->somenteMaster();

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $permissoes = $this->buildPermissoes($request);

        User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'tenant_id'  => Auth::user()->tenant_id,
            'role'       => 'membro',
            'permissoes' => $permissoes,
            'ativo'      => true,
        ]);

        return back()->with('success', 'Membro criado com sucesso!');
    }

    public function update(Request $request, User $membro)
    {
        $this->somenteMaster();

        if ($membro->tenant_id !== Auth::user()->tenant_id || $membro->role === 'master') {
            abort(403);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $membro->id,
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        $data = [
            'name'       => $request->name,
            'email'      => $request->email,
            'ativo'      => $request->boolean('ativo', true),
            'permissoes' => $this->buildPermissoes($request),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $membro->update($data);

        return back()->with('success', 'Membro atualizado com sucesso!');
    }

    public function destroy(User $membro)
    {
        $this->somenteMaster();

        if ($membro->tenant_id !== Auth::user()->tenant_id || $membro->role === 'master') {
            abort(403);
        }

        $membro->delete();

        return back()->with('success', 'Membro removido com sucesso!');
    }

    private function buildPermissoes(Request $request): array
    {
        $modulos = ['despesas', 'receitas', 'investimentos', 'bancos', 'categorias', 'fornecedores', 'familiares'];
        $acoes   = ['ver', 'criar', 'editar', 'excluir'];

        $permissoes = [];
        foreach ($modulos as $modulo) {
            foreach ($acoes as $acao) {
                $permissoes[$modulo][$acao] = $request->boolean("perm_{$modulo}_{$acao}");
            }
        }

        return $permissoes;
    }
}
