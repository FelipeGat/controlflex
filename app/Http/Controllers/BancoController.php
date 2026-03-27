<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Familiar;
use App\Models\Tenant;
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

        // Verificar limite de contas bancárias do plano
        $tenant = Tenant::with('plano')->find($tenantId);
        if ($tenant && $tenant->limiteBancosAtingido()) {
            return back()->withErrors([
                'nome' => 'Limite de contas bancárias do plano atingido. Faça upgrade do plano para adicionar mais contas.',
            ]);
        }

        $request->validate([
            'nome'               => 'required|string|max:150',
            'tem_conta_corrente' => 'nullable|boolean',
            'tem_poupanca'       => 'nullable|boolean',
            'tem_cartao_credito' => 'nullable|boolean',
            'eh_dinheiro'        => 'nullable|boolean',
            'saldo'              => 'nullable|numeric',
            'saldo_poupanca'     => 'nullable|numeric',
            'cheque_especial'    => 'nullable|numeric|min:0',
            'limite_cartao'      => 'nullable|numeric|min:0',
            'dia_vencimento_cartao' => 'nullable|integer|min:1|max:31',
            'dia_fechamento_cartao' => 'nullable|integer|min:1|max:31',
            'titular_id'         => ['nullable', Rule::exists('familiares', 'id')->where('tenant_id', $tenantId)],
            'codigo_banco'       => 'nullable|string|max:10',
            'agencia'            => 'nullable|string|max:20',
            'conta'              => 'nullable|string|max:30',
            'logo'               => 'nullable|string|max:100',
            'cor'                => 'nullable|string|max:7',
        ]);

        Banco::create([
            'user_id'            => $userId,
            'titular_id'         => $request->titular_id,
            'nome'               => $request->nome,
            'tem_conta_corrente' => $request->boolean('tem_conta_corrente'),
            'tem_poupanca'       => $request->boolean('tem_poupanca'),
            'tem_cartao_credito' => $request->boolean('tem_cartao_credito'),
            'eh_dinheiro'        => $request->boolean('eh_dinheiro'),
            'codigo_banco'       => $request->codigo_banco,
            'agencia'            => $request->agencia,
            'conta'              => $request->conta,
            'saldo'              => $request->saldo ?? 0,
            'saldo_poupanca'     => $request->saldo_poupanca ?? 0,
            'cheque_especial'    => $request->cheque_especial ?? 0,
            'saldo_cheque'       => 0,
            'limite_cartao'      => $request->limite_cartao ?? 0,
            'saldo_cartao'       => 0,
            'dia_vencimento_cartao' => $request->dia_vencimento_cartao,
            'dia_fechamento_cartao' => $request->dia_fechamento_cartao,
            'logo'               => $request->logo,
            'cor'                => $request->cor,
        ]);

        return back()->with('success', 'Conta bancária criada com sucesso!');
    }

    public function update(Request $request, Banco $banco)
    {
        $this->authorize('update', $banco);

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'nome'               => 'required|string|max:150',
            'tem_conta_corrente' => 'nullable|boolean',
            'tem_poupanca'       => 'nullable|boolean',
            'tem_cartao_credito' => 'nullable|boolean',
            'eh_dinheiro'        => 'nullable|boolean',
            'saldo'              => 'nullable|numeric',
            'saldo_poupanca'     => 'nullable|numeric',
            'cheque_especial'    => 'nullable|numeric|min:0',
            'limite_cartao'      => 'nullable|numeric|min:0',
            'dia_vencimento_cartao' => 'nullable|integer|min:1|max:31',
            'dia_fechamento_cartao' => 'nullable|integer|min:1|max:31',
            'titular_id'         => ['nullable', Rule::exists('familiares', 'id')->where('tenant_id', $tenantId)],
            'codigo_banco'       => 'nullable|string|max:10',
            'agencia'            => 'nullable|string|max:20',
            'conta'              => 'nullable|string|max:30',
            'logo'               => 'nullable|string|max:100',
            'cor'                => 'nullable|string|max:7',
        ]);

        $banco->update([
            'titular_id'         => $request->titular_id,
            'nome'               => $request->nome,
            'tem_conta_corrente' => $request->boolean('tem_conta_corrente'),
            'tem_poupanca'       => $request->boolean('tem_poupanca'),
            'tem_cartao_credito' => $request->boolean('tem_cartao_credito'),
            'eh_dinheiro'        => $request->boolean('eh_dinheiro'),
            'codigo_banco'       => $request->codigo_banco,
            'agencia'            => $request->agencia,
            'conta'              => $request->conta,
            'saldo'              => $request->saldo ?? 0,
            'saldo_poupanca'     => $request->saldo_poupanca ?? 0,
            'cheque_especial'    => $request->cheque_especial ?? 0,
            'limite_cartao'      => $request->limite_cartao ?? 0,
            'dia_vencimento_cartao' => $request->dia_vencimento_cartao,
            'dia_fechamento_cartao' => $request->dia_fechamento_cartao,
            'logo'               => $request->logo,
            'cor'                => $request->cor,
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

    public function ajustarSaldoPoupanca(Request $request, Banco $banco)
    {
        $this->authorize('update', $banco);

        $request->validate(['saldo_poupanca' => 'required|numeric']);
        $banco->update(['saldo_poupanca' => $request->saldo_poupanca]);

        return back()->with('success', 'Saldo da poupança ajustado com sucesso!');
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
