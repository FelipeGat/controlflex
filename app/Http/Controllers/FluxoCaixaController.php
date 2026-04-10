<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Despesa;
use App\Models\Receita;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FluxoCaixaController extends Controller
{
    public function index(Request $request)
    {
        // ── Período ──────────────────────────────────────────────────────────
        $periodo = $request->get('periodo', 'semana');

        if ($periodo === 'mes') {
            $inicio = now()->startOfMonth()->format('Y-m-d');
            $fim    = now()->endOfMonth()->format('Y-m-d');
        } elseif ($periodo === 'custom') {
            $inicio = $request->get('inicio', now()->startOfWeek()->format('Y-m-d'));
            $fim    = $request->get('fim',    now()->endOfWeek()->format('Y-m-d'));
        } else {
            // semana (default): segunda → domingo
            $inicio = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $fim    = now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $periodo = 'semana';
        }

        // ── Despesas no período ───────────────────────────────────────────────
        $despesas = Despesa::with(['categoria', 'fornecedor', 'banco', 'familiar'])
            ->whereBetween('data_compra', [$inicio, $fim])
            ->orderBy('data_compra')
            ->orderBy('id')
            ->get();

        // ── Receitas no período ───────────────────────────────────────────────
        $receitas = Receita::with(['categoria', 'banco', 'familiar'])
            ->whereBetween('data_prevista_recebimento', [$inicio, $fim])
            ->orderBy('data_prevista_recebimento')
            ->orderBy('id')
            ->get();

        // ── Totais ────────────────────────────────────────────────────────────
        $totalAPagar     = $despesas->whereNull('data_pagamento')->sum('valor');
        $totalAReceber   = $receitas->whereNull('data_recebimento')->sum('valor');
        $totalPago       = $despesas->whereNotNull('data_pagamento')->sum('valor');
        $totalRecebido   = $receitas->whereNotNull('data_recebimento')->sum('valor');
        $saldoProjetado  = $totalAReceber - $totalAPagar;

        return view('fluxo-caixa.index', compact(
            'despesas', 'receitas',
            'totalAPagar', 'totalAReceber', 'totalPago', 'totalRecebido', 'saldoProjetado',
            'inicio', 'fim', 'periodo'
        ));
    }

    // ── Baixar Despesa (marcar como paga) ─────────────────────────────────────
    public function baixarDespesa(Request $request, Despesa $despesa)
    {
        $this->authorize('update', $despesa);

        $request->validate(['data_pagamento' => 'required|date']);

        $despesa->update(['data_pagamento' => $request->data_pagamento]);

        // Debita o saldo do banco (exceto cartão de crédito, que usa limite)
        $this->atualizarSaldoBanco($despesa, 'debito');

        return back()->with('success', 'Despesa baixada com sucesso!');
    }

    // ── Estornar Despesa (desfazer baixa) ─────────────────────────────────────
    public function estornarDespesa(Despesa $despesa)
    {
        $this->authorize('update', $despesa);

        // Estorna o saldo do banco antes de limpar a data
        $this->atualizarSaldoBanco($despesa, 'credito');

        $despesa->update(['data_pagamento' => null]);

        return back()->with('success', 'Baixa da despesa estornada.');
    }

    // ── Baixar Receita (marcar como recebida) ─────────────────────────────────
    public function baixarReceita(Request $request, Receita $receita)
    {
        $this->authorize('update', $receita);

        $request->validate(['data_recebimento' => 'required|date']);

        $receita->update(['data_recebimento' => $request->data_recebimento]);

        // Credita o saldo do banco
        $this->atualizarSaldoBancoReceita($receita, 'credito');

        return back()->with('success', 'Receita baixada com sucesso!');
    }

    // ── Estornar Receita (desfazer baixa) ─────────────────────────────────────
    public function estornarReceita(Receita $receita)
    {
        $this->authorize('update', $receita);

        // Debita o saldo do banco antes de limpar a data
        $this->atualizarSaldoBancoReceita($receita, 'debito');

        $receita->update(['data_recebimento' => null]);

        return back()->with('success', 'Baixa da receita estornada.');
    }

    // ── Atualizar saldo do banco (despesa) ────────────────────────────────────
    private function atualizarSaldoBanco(Despesa $despesa, string $operacao): void
    {
        $banco = Banco::find($despesa->forma_pagamento);
        if (! $banco) return;

        $valor = (float) $despesa->valor;

        // Cartão de crédito não afeta saldo da conta corrente
        if ($despesa->tipo_pagamento === 'credito') {
            return;
        }

        if ($operacao === 'debito') {
            $banco->decrement('saldo', $valor);
        } else {
            $banco->increment('saldo', $valor);
        }
    }

    // ── Atualizar saldo do banco (receita) ────────────────────────────────────
    private function atualizarSaldoBancoReceita(Receita $receita, string $operacao): void
    {
        $banco = Banco::find($receita->forma_recebimento);
        if (! $banco) return;

        $valor = (float) $receita->valor;

        if ($operacao === 'credito') {
            $banco->increment('saldo', $valor);
        } else {
            $banco->decrement('saldo', $valor);
        }
    }
}
