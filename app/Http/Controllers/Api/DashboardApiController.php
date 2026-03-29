<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    /**
     * GET /api/dashboard/snapshot
     *
     * Returns a lightweight JSON snapshot of the current month's KPIs.
     * Used by the Service Worker for stale-while-revalidate caching and
     * by offline.html to render a meaningful offline dashboard.
     */
    public function snapshot(Request $request)
    {
        $tenantId   = Auth::user()->tenant_id;
        $inicio     = $request->get('inicio', now()->startOfMonth()->format('Y-m-d'));
        $fim        = $request->get('fim',    now()->endOfMonth()->format('Y-m-d'));
        $familiarId = $request->get('familiar_id') ? (int) $request->get('familiar_id') : null;

        $mesAtual = Carbon::parse($inicio);

        // ── KPIs ──────────────────────────────────────────────────────────
        $totalReceitas = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_prevista_recebimento', [$inicio, $fim])
            ->when($familiarId, fn($q) => $q->where(fn($s) =>
                $s->where('quem_recebeu', $familiarId)->orWhereNull('quem_recebeu')
            ))
            ->sum('valor');

        $totalDespesas = DB::table('despesas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereBetween('data_compra', [$inicio, $fim])
            ->when($familiarId, fn($q) => $q->where(fn($s) =>
                $s->where('quem_comprou', $familiarId)->orWhereNull('quem_comprou')
            ))
            ->sum('valor');

        // ── Saldos bancários ──────────────────────────────────────────────
        $bancos = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->when($familiarId, fn($q) => $q->where('titular_id', $familiarId))
            ->orderBy('nome')
            ->get(['nome', 'saldo_conta', 'cor', 'logo'])
            ->map(fn($b) => [
                'nome'  => $b->nome,
                'saldo' => (float) $b->saldo_conta,
                'cor'   => $b->cor,
            ]);

        // ── Cartões ───────────────────────────────────────────────────────
        $cartoes = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->where('tem_cartao_credito', true)
            ->when($familiarId, fn($q) => $q->where('titular_id', $familiarId))
            ->get(['nome', 'saldo_cartao', 'limite_cartao', 'cor'])
            ->map(fn($c) => [
                'nome'      => $c->nome,
                'fatura'    => (float) $c->saldo_cartao,
                'limite'    => (float) $c->limite_cartao,
                'percentual'=> $c->limite_cartao > 0
                    ? round(($c->saldo_cartao / $c->limite_cartao) * 100, 1)
                    : 0,
            ]);

        // ── Últimos 5 lançamentos ─────────────────────────────────────────
        $lancamentos = DB::table('receitas')
            ->leftJoin('categorias', 'receitas.categoria_id', '=', 'categorias.id')
            ->selectRaw("receitas.id, 'receita' as tipo, receitas.valor, receitas.data_prevista_recebimento as data, COALESCE(categorias.nome,'Sem categoria') as categoria")
            ->where('receitas.tenant_id', $tenantId)
            ->whereNull('receitas.deleted_at')
            ->union(
                DB::table('despesas')
                    ->leftJoin('categorias', 'despesas.categoria_id', '=', 'categorias.id')
                    ->selectRaw("despesas.id, 'despesa' as tipo, despesas.valor, despesas.data_compra as data, COALESCE(categorias.nome,'Sem categoria') as categoria")
                    ->where('despesas.tenant_id', $tenantId)
                    ->whereNull('despesas.deleted_at')
            )
            ->orderByDesc('data')
            ->limit(5)
            ->get();

        return response()->json([
            'cached_at'     => now()->toIso8601String(),
            'periodo'       => [
                'inicio'  => $inicio,
                'fim'     => $fim,
                'mes'     => ucfirst($mesAtual->translatedFormat('F Y')),
            ],
            'kpis'          => [
                'receitas' => (float) $totalReceitas,
                'despesas' => (float) $totalDespesas,
                'saldo'    => (float) ($totalReceitas - $totalDespesas),
            ],
            'bancos'        => $bancos,
            'cartoes'       => $cartoes,
            'lancamentos'   => $lancamentos,
            'totais'        => [
                'saldo_contas'  => (float) $bancos->sum('saldo'),
                'fatura_total'  => (float) $cartoes->sum('fatura'),
                'limite_total'  => (float) $cartoes->sum('limite'),
            ],
        ]);
    }
}
