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
    /** Tipos de pagamento aceitos pelo sistema */
    private const TIPOS_PAGAMENTO = ['dinheiro', 'pix', 'debito', 'credito', 'transferencia'];

    public function index(Request $request)
    {
        $tenantId   = Auth::user()->tenant_id;
        $inicio     = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $fim        = $request->get('fim', now()->endOfMonth()->format('Y-m-d'));
        $familiarId = $request->get('familiar_id') ? (int) $request->get('familiar_id') : null;

        // Filtro por membro: inclui despesas do membro + despesas de "Todos da Casa" (NULL)
        $baseQuery = Despesa::whereBetween('data_compra', [$inicio, $fim])
            ->when($familiarId, fn($q) => $q->where(function ($sub) use ($familiarId) {
                $sub->where('quem_comprou', $familiarId)->orWhereNull('quem_comprou');
            }));

        $totalValor = (clone $baseQuery)->sum('valor');

        $despesas = Despesa::with(['familiar', 'fornecedor', 'categoria', 'banco'])
            ->whereBetween('data_compra', [$inicio, $fim])
            ->when($familiarId, fn($q) => $q->where(function ($sub) use ($familiarId) {
                $sub->where('quem_comprou', $familiarId)->orWhereNull('quem_comprou');
            }))
            ->orderByDesc('data_compra')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $categorias   = Categoria::where('tipo', 'DESPESA')->orderBy('nome')->get();
        $familiares   = Familiar::orderBy('nome')->get();
        $fornecedores = Fornecedor::orderBy('nome')->get();
        $bancos       = Banco::orderBy('nome')->get();

        return view('despesas.index', compact(
            'despesas', 'totalValor', 'categorias', 'familiares',
            'fornecedores', 'bancos', 'inicio', 'fim', 'familiarId'
        ));
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
            'tipo_pagamento'  => ['nullable', Rule::in(self::TIPOS_PAGAMENTO)],
            'parcelas'        => 'nullable|integer|min:0|max:360',
            'frequencia'      => 'nullable|in:diaria,semanal,quinzenal,mensal,trimestral,semestral,anual',
        ]);

        // Validar saldo/limite da conta selecionada
        if ($request->forma_pagamento && $request->tipo_pagamento) {
            $erro = $this->validarDisponibilidade(
                (int) $request->forma_pagamento,
                (float) $request->valor,   // valor total (antes de dividir em parcelas)
                $request->tipo_pagamento,
            );
            if ($erro) {
                return back()->withErrors(['valor' => $erro])->withInput();
            }
        }

        // Para cartão de crédito: injetar dados de fechamento/vencimento do cartão
        // para que o modelo calcule corretamente as datas das faturas
        $data = $request->all();
        if ($request->tipo_pagamento === 'credito' && $request->forma_pagamento) {
            $banco = Banco::find((int) $request->forma_pagamento);
            if ($banco && $banco->dia_fechamento_cartao && $banco->dia_vencimento_cartao) {
                $data['dia_fechamento_cartao'] = $banco->dia_fechamento_cartao;
                $data['dia_vencimento_cartao'] = $banco->dia_vencimento_cartao;
            }
        }

        $total = Despesa::criarComRecorrencia($data, $userId);

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
            'tipo_pagamento'  => ['nullable', Rule::in(self::TIPOS_PAGAMENTO)],
            'observacoes'     => 'nullable|string|max:2000',
        ]);

        // Validar disponibilidade ao alterar valor ou forma/tipo de pagamento
        if ($request->forma_pagamento && $request->tipo_pagamento) {
            $mesmoCartao = ((int) $request->forma_pagamento === (int) $despesa->forma_pagamento);
            $erro = $this->validarDisponibilidade(
                (int) $request->forma_pagamento,
                (float) $request->valor,
                $request->tipo_pagamento,
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
                    'tipo_pagamento'  => $request->tipo_pagamento,
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
                'tipo_pagamento'  => $request->tipo_pagamento,
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
     * Valida se a conta/cartão tem saldo ou limite suficiente para o lançamento.
     *
     * Regras por tipo_pagamento:
     *  - credito          → valida contra limite do cartão de crédito
     *  - pix / debito / transferencia → valida contra saldo + cheque especial
     *  - dinheiro         → valida apenas contra saldo (sem cheque especial)
     *
     * @param int         $bancoId         ID do banco/cartão
     * @param float       $novoValor       Valor total do lançamento (antes de dividir parcelas)
     * @param string      $tipoPagamento   Tipo: dinheiro|pix|debito|credito|transferencia
     * @param int|null    $excluirId       ID de despesa a excluir do cálculo (na edição)
     */
    private function validarDisponibilidade(
        int $bancoId,
        float $novoValor,
        string $tipoPagamento,
        ?int $excluirId = null
    ): ?string {
        $banco = Banco::find($bancoId);
        if (! $banco) {
            return null;
        }

        // Soma de todas as despesas em aberto (não pagas) vinculadas a este banco
        $comprometido = (float) Despesa::where('forma_pagamento', $bancoId)
            ->whereNull('data_pagamento')
            ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
            ->sum('valor');

        // ── Cartão de Crédito ─────────────────────────────────────────────────
        if ($tipoPagamento === 'credito') {
            $limite     = (float) $banco->limite_cartao;

            if ($limite <= 0) {
                return "O banco {$banco->nome} não possui limite de cartão de crédito configurado.";
            }

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

        // ── Dinheiro (carteira física) ────────────────────────────────────────
        if ($tipoPagamento === 'dinheiro') {
            $saldo      = (float) $banco->saldo;
            $disponivel = $saldo - $comprometido;

            if ($novoValor > $disponivel) {
                return sprintf(
                    'Saldo insuficiente em %s. Disponível: R$ %s (Saldo: R$ %s).',
                    $banco->nome,
                    number_format(max(0, $disponivel), 2, ',', '.'),
                    number_format($saldo, 2, ',', '.')
                );
            }

            return null;
        }

        // ── Pix / Débito / Transferência Bancária ────────────────────────────
        // (pix, debito, transferencia) — usa saldo + cheque especial disponível
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
}
