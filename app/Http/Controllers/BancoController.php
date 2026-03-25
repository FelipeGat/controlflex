<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Familiar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BancoController extends Controller
{
    public function index()
    {
        $bancos = Banco::with('titular')
            ->orderBy('nome')
            ->get();

        $familiares = Familiar::orderBy('nome')->get();

        return view('bancos.index', compact('bancos', 'familiares'));
    }

    public function store(Request $request)
    {
        $userId   = Auth::id();
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'nome'            => 'required|string|max:150',
            'tipo_conta'      => 'required|in:Conta Corrente,Poupança,Dinheiro,Cartão de Crédito',
            'saldo'           => 'nullable|numeric',
            'cheque_especial' => 'nullable|numeric|min:0',
            'limite_cartao'   => 'nullable|numeric|min:0',
            'titular_id'      => ['nullable', Rule::exists('familiares', 'id')->where('tenant_id', $tenantId)],
            'codigo_banco'    => 'nullable|string|max:10',
            'agencia'         => 'nullable|string|max:20',
            'conta'           => 'nullable|string|max:30',
        ]);

        Banco::create([
            'user_id'         => $userId,
            'titular_id'      => $request->titular_id,
            'nome'            => $request->nome,
            'tipo_conta'      => $request->tipo_conta,
            'codigo_banco'    => $request->codigo_banco,
            'agencia'         => $request->agencia,
            'conta'           => $request->conta,
            'saldo'           => $request->saldo ?? 0,
            'cheque_especial' => $request->cheque_especial ?? 0,
            'saldo_cheque'    => 0,
            'limite_cartao'   => $request->limite_cartao ?? 0,
            'saldo_cartao'    => 0,
        ]);

        return back()->with('success', 'Conta bancária criada com sucesso!');
    }

    public function update(Request $request, Banco $banco)
    {
        $this->authorize('update', $banco);

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'nome'            => 'required|string|max:150',
            'tipo_conta'      => 'required|in:Conta Corrente,Poupança,Dinheiro,Cartão de Crédito',
            'saldo'           => 'nullable|numeric',
            'cheque_especial' => 'nullable|numeric|min:0',
            'limite_cartao'   => 'nullable|numeric|min:0',
            'titular_id'      => ['nullable', Rule::exists('familiares', 'id')->where('tenant_id', $tenantId)],
            'codigo_banco'    => 'nullable|string|max:10',
            'agencia'         => 'nullable|string|max:20',
            'conta'           => 'nullable|string|max:30',
        ]);

        $banco->update([
            'titular_id'      => $request->titular_id,
            'nome'            => $request->nome,
            'tipo_conta'      => $request->tipo_conta,
            'codigo_banco'    => $request->codigo_banco,
            'agencia'         => $request->agencia,
            'conta'           => $request->conta,
            'saldo'           => $request->saldo ?? 0,
            'cheque_especial' => $request->cheque_especial ?? 0,
            'limite_cartao'   => $request->limite_cartao ?? 0,
        ]);

        return back()->with('success', 'Conta atualizada com sucesso!');
    }

    public function ajustarSaldo(Request $request, Banco $banco)
    {
        $this->authorize('update', $banco);

        $request->validate(['saldo' => 'required|numeric']);
        $banco->update(['saldo' => $request->saldo]);

        return back()->with('success', 'Saldo ajustado com sucesso!');
    }

    public function ajustarSaldoCartao(Request $request, Banco $banco)
    {
        $this->authorize('update', $banco);

        $request->validate(['saldo_cartao' => 'required|numeric|min:0']);
        $banco->update(['saldo_cartao' => $request->saldo_cartao]);

        return back()->with('success', 'Saldo do cartão ajustado com sucesso!');
    }

    public function destroy(Banco $banco)
    {
        $this->authorize('delete', $banco);
        $banco->delete();

        return back()->with('success', 'Conta excluída com sucesso!');
    }
}
