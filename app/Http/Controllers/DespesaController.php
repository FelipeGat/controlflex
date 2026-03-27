<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Categoria;
use App\Models\Despesa;
use App\Models\Familiar;
use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DespesaController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $inicio = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $fim    = $request->get('fim', now()->endOfMonth()->format('Y-m-d'));

        $baseQuery  = Despesa::whereBetween('data_compra', [$inicio, $fim]);
        $totalValor = (clone $baseQuery)->sum('valor');

        $despesas = Despesa::with(['familiar', 'fornecedor', 'categoria', 'banco'])
            ->whereBetween('data_compra', [$inicio, $fim])
            ->orderByDesc('data_compra')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $categorias   = Categoria::where('tipo', 'DESPESA')->orderBy('nome')->get();
        $familiares   = Familiar::orderBy('nome')->get();
        $fornecedores = Fornecedor::orderBy('nome')->get();
        $bancos       = Banco::orderBy('nome')->get();

        return view('despesas.index', compact('despesas', 'totalValor', 'categorias', 'familiares', 'fornecedores', 'bancos', 'inicio', 'fim'));
    }

    public function store(Request $request)
    {
        $userId   = Auth::id();
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'valor'           => 'required|numeric|min:0.01',
            'data_compra'     => 'required|date',
            'data_pagamento'  => 'nullable|date',
            'categoria_id'    => ['nullable', Rule::exists('categorias', 'id')->where('tenant_id', $tenantId)],
            'quem_comprou'    => ['nullable', Rule::exists('familiares', 'id')->where('tenant_id', $tenantId)],
            'onde_comprou'    => ['nullable', Rule::exists('fornecedores', 'id')->where('tenant_id', $tenantId)],
            'forma_pagamento' => ['nullable', Rule::exists('bancos', 'id')->where('tenant_id', $tenantId)],
            'parcelas'        => 'nullable|integer|min:0|max:360',
            'frequencia'      => 'nullable|in:diaria,semanal,quinzenal,mensal,trimestral,semestral,anual',
        ]);

        if ($request->forma_pagamento) {
            $erro = $this->validarDisponibilidade((int) $request->forma_pagamento, (float) $request->valor);
            if ($erro) {
                return back()->withErrors(['valor' => $erro])->withInput();
            }
        }

        $total = Despesa::criarComRecorrencia($request->all(), $userId);

        return back()->with('success', "{$total} despesa(s) salva(s) com sucesso!");
    }

    public function update(Request $request, Despesa $despesa)
    {
        $this->authorize('update', $despesa);

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'valor'           => 'required|numeric|min:0.01',
            'data_compra'     => 'required|date',
            'data_pagamento'  => 'nullable|date',
            'categoria_id'    => ['nullable', Rule::exists('categorias', 'id')->where('tenant_id', $tenantId)],
            'quem_comprou'    => ['nullable', Rule::exists('familiares', 'id')->where('tenant_id', $tenantId)],
            'onde_comprou'    => ['nullable', Rule::exists('fornecedores', 'id')->where('tenant_id', $tenantId)],
            'forma_pagamento' => ['nullable', Rule::exists('bancos', 'id')->where('tenant_id', $tenantId)],
            'observacoes'     => 'nullable|string|max:2000',
        ]);

        // Validar disponibilidade ao alterar valor ou forma de pagamento
        if ($request->forma_pagamento) {
            // Se o banco não mudou, exclui a própria despesa do cálculo para evitar dupla contagem
            $mesmoCartao = ((int) $request->forma_pagamento === (int) $despesa->forma_pagamento);
            $erro = $this->validarDisponibilidade(
                (int) $request->forma_pagamento,
                (float) $request->valor,
                $mesmoCartao ? $despesa->id : null
            );
            if ($erro) {
                return back()->withErrors(['valor' => $erro])->withInput();
            }
        }

        $escopo = $request->get('escopo', 'apenas_esta');

        if ($escopo === 'esta_e_futuras' && $despesa->grupo_recorrencia_id) {
            Despesa::where('tenant_id', $tenantId)
                ->where('grupo_recorrencia_id', $despesa->grupo_recorrencia_id)
                ->where('data_compra', '>=', $despesa->data_compra)
                ->update([
                    'quem_comprou'    => $request->quem_comprou,
                    'onde_comprou'    => $request->onde_comprou,
                    'categoria_id'    => $request->categoria_id,
                    'forma_pagamento' => $request->forma_pagamento,
                    'valor'           => $request->valor,
                    'data_pagamento'  => $request->data_pagamento ?: null,
                    'observacoes'     => $request->observacoes,
                ]);
        } else {
            $despesa->update([
                'quem_comprou'    => $request->quem_comprou,
                'onde_comprou'    => $request->onde_comprou,
                'categoria_id'    => $request->categoria_id,
                'forma_pagamento' => $request->forma_pagamento,
                'valor'           => $request->valor,
                'data_compra'     => $request->data_compra,
                'data_pagamento'  => $request->data_pagamento ?: null,
                'observacoes'     => $request->observacoes,
            ]);
        }

        return back()->with('success', 'Despesa atualizada com sucesso!');
    }

    public function destroy(Request $request, Despesa $despesa)
    {
        $this->authorize('delete', $despesa);

        $tenantId = Auth::user()->tenant_id;
        $escopo   = $request->get('escopo', 'apenas_esta');

        if ($escopo === 'esta_e_futuras' && $despesa->grupo_recorrencia_id) {
            $count = Despesa::where('tenant_id', $tenantId)
                ->where('grupo_recorrencia_id', $despesa->grupo_recorrencia_id)
                ->where('data_compra', '>=', $despesa->data_compra)
                ->delete();

            return back()->with('success', "{$count} despesa(s) excluída(s)!");
        }

        $despesa->delete();

        return back()->with('success', 'Despesa excluída com sucesso!');
    }

    /**
     * Valida se o banco/cartão tem saldo ou limite suficiente para o novo lançamento.
     *
     * Para cartão de crédito: usa a soma das despesas em aberto (não pagas) como
     * saldo comprometido, garantindo que nunca ultrapasse o limite configurado.
     *
     * Para conta corrente / carteira: usa o saldo atual menos as despesas em aberto
     * mais o limite de cheque especial disponível.
     *
     * @param int       $bancoId         ID do banco/cartão
     * @param float     $novoValor       Valor do novo lançamento
     * @param int|null  $excluirDespesaId ID de despesa a excluir do cálculo (para edições)
     * @return string|null               Mensagem de erro ou null se aprovado
     */
    private function validarDisponibilidade(int $bancoId, float $novoValor, ?int $excluirDespesaId = null): ?string
    {
        $banco = Banco::find($bancoId);
        if (! $banco) {
            return null;
        }

        // Soma de todas as despesas em aberto (não pagas) vinculadas a este banco
        $comprometido = (float) Despesa::where('forma_pagamento', $bancoId)
            ->whereNull('data_pagamento')
            ->when($excluirDespesaId, fn ($q) => $q->where('id', '!=', $excluirDespesaId))
            ->sum('valor');

        // ── Cartão de crédito ────────────────────────────────────────────────
        if ($banco->tem_cartao_credito) {
            $limite     = (float) $banco->limite_cartao;
            $disponivel = $limite - $comprometido;

            if ($novoValor > $disponivel) {
                return sprintf(
                    'Limite insuficiente no cartão %s. Disponível: R$ %s (Limite: R$ %s | Comprometido: R$ %s).',
                    $banco->nome,
                    number_format(max(0, $disponivel), 2, ',', '.'),
                    number_format($limite, 2, ',', '.'),
                    number_format($comprometido, 2, ',', '.')
                );
            }

            return null;
        }

        // ── Conta corrente / Carteira (dinheiro) ─────────────────────────────
        if ($banco->tem_conta_corrente || $banco->eh_dinheiro) {
            $saldo            = (float) $banco->saldo;
            $chequeDisponivel = max(0, (float) $banco->cheque_especial - (float) $banco->saldo_cheque);
            $disponivel       = $saldo - $comprometido + $chequeDisponivel;

            if ($novoValor > $disponivel) {
                $msg = sprintf(
                    'Saldo insuficiente em %s. Disponível: R$ %s (Saldo: R$ %s',
                    $banco->nome,
                    number_format(max(0, $disponivel), 2, ',', '.'),
                    number_format($saldo, 2, ',', '.')
                );

                if ($chequeDisponivel > 0) {
                    $msg .= sprintf(' + Cheque Especial: R$ %s', number_format($chequeDisponivel, 2, ',', '.'));
                }

                $msg .= ').';

                return $msg;
            }

            return null;
        }

        return null;
    }
}
