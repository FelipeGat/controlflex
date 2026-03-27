<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $inicio = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $fim    = $request->get('fim', now()->endOfMonth()->format('Y-m-d'));
        $ano    = Carbon::parse($inicio)->year;

        // ─── Navegação por mês ──────────────────────────────────────────────
        $mesAtual      = Carbon::parse($inicio);
        $mesAnterior   = $mesAtual->copy()->subMonth();
        $mesProximo    = $mesAtual->copy()->addMonth();
        $nomeMes       = ucfirst($mesAtual->translatedFormat('F'));
        $anoMes        = $mesAtual->year;
        $linkMesAnt    = route('dashboard', ['inicio' => $mesAnterior->startOfMonth()->format('Y-m-d'), 'fim' => $mesAnterior->endOfMonth()->format('Y-m-d')]);
        $linkMesProx   = route('dashboard', ['inicio' => $mesProximo->startOfMonth()->format('Y-m-d'), 'fim' => $mesProximo->endOfMonth()->format('Y-m-d')]);

        // ─── KPIs do período ──────────────────────────────────────────────────

        $totalReceitas = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_prevista_recebimento', [$inicio, $fim])
            ->sum('valor');

        $totalDespesas = DB::table('despesas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_compra', [$inicio, $fim])
            ->sum('valor');

        $saldo = $totalReceitas - $totalDespesas;

        // ─── KPIs mês anterior ────────────────────────────────────────────────

        $inicioAnterior = Carbon::parse($inicio)->subMonth()->format('Y-m-d');
        $fimAnterior    = Carbon::parse($fim)->subMonth()->format('Y-m-d');

        $receitasAnterior = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_prevista_recebimento', [$inicioAnterior, $fimAnterior])
            ->sum('valor');

        $despesasAnterior = DB::table('despesas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_compra', [$inicioAnterior, $fimAnterior])
            ->sum('valor');

        $saldoAnterior = $receitasAnterior - $despesasAnterior;

        $variacaoReceitas = $receitasAnterior > 0
            ? (($totalReceitas - $receitasAnterior) / abs($receitasAnterior)) * 100
            : ($totalReceitas > 0 ? 100 : 0);

        $variacaoDespesas = $despesasAnterior > 0
            ? (($totalDespesas - $despesasAnterior) / abs($despesasAnterior)) * 100
            : ($totalDespesas > 0 ? 100 : 0);

        $variacaoSaldo = $saldoAnterior != 0
            ? (($saldo - $saldoAnterior) / abs($saldoAnterior)) * 100
            : ($saldo > 0 ? 100 : 0);

        // ─── Realizados do período ────────────────────────────────────────────

        $receitasRealizadas = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNotNull('data_recebimento')
            ->whereBetween('data_recebimento', [$inicio, $fim])
            ->sum('valor');

        $despesasRealizadas = DB::table('despesas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNotNull('data_pagamento')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->sum('valor');

        // ─── Gráfico anual ────────────────────────────────────────────────────

        $mesesLabels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        $receitasMes = array_fill(0, 12, 0);
        $despesasMes = array_fill(0, 12, 0);

        $receitasAnuais = DB::table('receitas')
            ->selectRaw('MONTH(data_prevista_recebimento) as mes, SUM(valor) as total')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereYear('data_prevista_recebimento', $ano)
            ->groupBy('mes')
            ->get();

        foreach ($receitasAnuais as $r) {
            $receitasMes[$r->mes - 1] = (float) $r->total;
        }

        $despesasAnuais = DB::table('despesas')
            ->selectRaw('MONTH(data_compra) as mes, SUM(valor) as total')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereYear('data_compra', $ano)
            ->groupBy('mes')
            ->get();

        foreach ($despesasAnuais as $d) {
            $despesasMes[$d->mes - 1] = (float) $d->total;
        }

        // ─── Despesas por categoria ───────────────────────────────────────────

        $despesasPorCategoria = DB::table('despesas')
            ->join('categorias', 'despesas.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nome, SUM(despesas.valor) as total')
            ->where('despesas.tenant_id', $tenantId)
            ->whereNull('despesas.deleted_at')
            ->whereBetween('despesas.data_compra', [$inicio, $fim])
            ->groupBy('categorias.nome')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->get();

        // ─── Receitas por categoria ───────────────────────────────────────────

        $receitasPorCategoria = DB::table('receitas')
            ->join('categorias', 'receitas.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.nome, SUM(receitas.valor) as total')
            ->where('receitas.tenant_id', $tenantId)
            ->whereNull('receitas.deleted_at')
            ->whereBetween('receitas.data_prevista_recebimento', [$inicio, $fim])
            ->groupBy('categorias.nome')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->get();

        // ─── Despesas por familiar ────────────────────────────────────────────

        $despesasPorFamiliar = DB::table('despesas')
            ->leftJoin('familiares', 'despesas.quem_comprou', '=', 'familiares.id')
            ->selectRaw('COALESCE(familiares.nome, "Não especificado") as nome, SUM(despesas.valor) as total')
            ->where('despesas.tenant_id', $tenantId)
            ->whereNull('despesas.deleted_at')
            ->whereBetween('despesas.data_compra', [$inicio, $fim])
            ->groupBy('familiares.nome')
            ->having('total', '>', 0)
            ->orderByDesc('total')
            ->get();

        // ─── Últimos lançamentos ──────────────────────────────────────────────

        $ultimosLancamentos = DB::table('receitas')
            ->leftJoin('categorias', 'receitas.categoria_id', '=', 'categorias.id')
            ->selectRaw("receitas.id, 'receita' as tipo, receitas.valor, receitas.data_prevista_recebimento as data, COALESCE(categorias.nome, 'Sem categoria') as categoria_nome")
            ->where('receitas.tenant_id', $tenantId)
            ->whereNull('receitas.deleted_at')
            ->union(
                DB::table('despesas')
                    ->leftJoin('categorias', 'despesas.categoria_id', '=', 'categorias.id')
                    ->selectRaw("despesas.id, 'despesa' as tipo, despesas.valor, despesas.data_compra as data, COALESCE(categorias.nome, 'Sem categoria') as categoria_nome")
                    ->where('despesas.tenant_id', $tenantId)
                    ->whereNull('despesas.deleted_at')
            )
            ->orderByDesc('data')
            ->limit(10)
            ->get();

        // ─── Saldos bancários ─────────────────────────────────────────────────

        $bancos = DB::table('bancos')->where('tenant_id', $tenantId)->orderBy('nome')->get();

        // ─── Cartões de crédito ─────────────────────────────────────────────

        $cartoes = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->where('tem_cartao_credito', true)
            ->orderBy('nome')
            ->get()
            ->map(function ($cartao) use ($tenantId, $inicio, $fim) {
                $cartao->gastos_periodo = (float) DB::table('despesas')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->where('forma_pagamento', $cartao->id)
                    ->whereBetween('data_compra', [$inicio, $fim])
                    ->sum('valor');
                $cartao->limite_disponivel = (float) $cartao->limite_cartao - $cartao->gastos_periodo;
                $cartao->percentual_uso = $cartao->limite_cartao > 0
                    ? round(($cartao->gastos_periodo / (float) $cartao->limite_cartao) * 100, 1)
                    : 0;
                return $cartao;
            });

        $totalGastosCartoes = $cartoes->sum('gastos_periodo');
        $totalLimiteCartoes = $cartoes->sum('limite_cartao');
        $totalFaturaCartoes = $cartoes->sum('saldo_cartao');

        // ─── Investimentos ────────────────────────────────────────────────────

        $totalInvestido = DB::table('investimentos')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->sum('valor_aportado');

        $investMes  = array_fill(0, 12, 0);
        $investAnual = DB::table('investimentos')
            ->selectRaw('MONTH(data_aporte) as mes, SUM(valor_aportado) as total')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereYear('data_aporte', $ano)
            ->groupBy('mes')
            ->get();

        foreach ($investAnual as $inv) {
            $investMes[$inv->mes - 1] = (float) $inv->total;
        }

        // Patrimônio acumulado simulado (taxa 1% a.m.)
        $patrimonioAcumulado = [];
        $saldoAnt            = 0;
        $taxaMensal          = 0.01;
        foreach ($investMes as $aporte) {
            $rendimento = $saldoAnt * $taxaMensal;
            $saldoAnt   = $saldoAnt + $aporte + $rendimento;
            $patrimonioAcumulado[] = round($saldoAnt, 2);
        }

        // ─── Infocards último/próximo mês ─────────────────────────────────────

        $primeiroDiaUltimoMes  = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $ultimoDiaUltimoMes    = now()->subMonth()->endOfMonth()->format('Y-m-d');
        $primeiroDiaProximoMes = now()->addMonth()->startOfMonth()->format('Y-m-d');
        $ultimoDiaProximoMes   = now()->addMonth()->endOfMonth()->format('Y-m-d');

        $pagamentoUltimoMes = DB::table('despesas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNotNull('data_pagamento')
            ->whereBetween('data_pagamento', [$primeiroDiaUltimoMes, $ultimoDiaUltimoMes])
            ->sum('valor');

        $recebidoUltimoMes = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNotNull('data_recebimento')
            ->whereBetween('data_recebimento', [$primeiroDiaUltimoMes, $ultimoDiaUltimoMes])
            ->sum('valor');

        $previsaoDespesasProxMes = DB::table('despesas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_compra', [$primeiroDiaProximoMes, $ultimoDiaProximoMes])
            ->sum('valor');

        $previsaoReceitasProxMes = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_prevista_recebimento', [$primeiroDiaProximoMes, $ultimoDiaProximoMes])
            ->sum('valor');

        return view('dashboard', compact(
            'inicio', 'fim', 'ano',
            'nomeMes', 'anoMes', 'linkMesAnt', 'linkMesProx',
            'totalReceitas', 'totalDespesas', 'saldo',
            'variacaoReceitas', 'variacaoDespesas', 'variacaoSaldo',
            'receitasRealizadas', 'despesasRealizadas',
            'mesesLabels', 'receitasMes', 'despesasMes',
            'despesasPorCategoria', 'receitasPorCategoria',
            'despesasPorFamiliar',
            'ultimosLancamentos',
            'bancos',
            'cartoes', 'totalGastosCartoes', 'totalLimiteCartoes', 'totalFaturaCartoes',
            'totalInvestido', 'patrimonioAcumulado',
            'pagamentoUltimoMes', 'recebidoUltimoMes',
            'previsaoDespesasProxMes', 'previsaoReceitasProxMes'
        ));
    }
}
