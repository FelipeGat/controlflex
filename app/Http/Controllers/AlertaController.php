<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlertaController extends Controller
{
    public function index()
    {
        $tenantId   = Auth::user()->tenant_id;
        $hoje       = now()->format('Y-m-d');
        $em7dias    = now()->addDays(7)->format('Y-m-d');
        $inicioMes  = now()->startOfMonth()->format('Y-m-d');
        $fimMes     = now()->endOfMonth()->format('Y-m-d');
        $inicioAnt  = now()->subMonth()->startOfMonth()->format('Y-m-d');
        $fimAnt     = now()->subMonth()->endOfMonth()->format('Y-m-d');

        $alertas = collect();

        // ═══════════════════════════════════════════════════════════════════
        // 🔴 URGENTE — Ação imediata necessária
        // ═══════════════════════════════════════════════════════════════════

        // 1. Contas vencidas (sem pagamento, data já passou)
        $vencidas = DB::table('despesas')
            ->leftJoin('categorias', 'despesas.categoria_id', '=', 'categorias.id')
            ->selectRaw("despesas.id, despesas.onde_comprou as descricao, despesas.valor, despesas.data_compra, COALESCE(categorias.nome,'Sem categoria') as categoria")
            ->where('despesas.tenant_id', $tenantId)
            ->whereNull('despesas.deleted_at')
            ->whereNull('despesas.data_pagamento')
            ->where('despesas.data_compra', '<', $hoje)
            ->orderBy('despesas.data_compra')
            ->get();

        if ($vencidas->count() > 0) {
            $totalVencido = $vencidas->sum('valor');
            $maisDias = (int) $vencidas->map(fn($d) => abs(Carbon::parse($d->data_compra)->diffInDays(now())))->max();
            $alertas->push([
                'tipo'      => 'urgente',
                'icone'     => 'fa-circle-exclamation',
                'titulo'    => $vencidas->count() . ' conta(s) vencida(s) sem pagamento',
                'descricao' => 'Total em aberto: <strong>R$ ' . number_format($totalVencido, 2, ',', '.') . '</strong>. A mais antiga venceu há <strong>' . $maisDias . ' dia(s)</strong>.',
                'acao_url'  => route('fluxo-caixa.index'),
                'acao_txt'  => 'Ver e baixar contas',
                'detalhe'   => $vencidas->take(5)->map(fn($d) => Carbon::parse($d->data_compra)->format('d/m') . ' — ' . ($d->descricao ?: 'Despesa') . ' (R$ ' . number_format($d->valor, 2, ',', '.') . ')')->toArray(),
            ]);
        }

        // 2. Bancos com saldo negativo
        $bancosNegativos = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->where('saldo', '<', 0)
            ->get();

        foreach ($bancosNegativos as $banco) {
            $alertas->push([
                'tipo'      => 'urgente',
                'icone'     => 'fa-piggy-bank',
                'titulo'    => 'Saldo negativo: ' . $banco->nome,
                'descricao' => 'A conta <strong>' . $banco->nome . '</strong> está com saldo de <strong class="text-danger">R$ ' . number_format($banco->saldo, 2, ',', '.') . '</strong>. Verifique se há uso do cheque especial.',
                'acao_url'  => route('bancos.index'),
                'acao_txt'  => 'Gerenciar conta',
                'detalhe'   => [],
            ]);
        }

        // 3. Cartões acima de 90% do limite
        $cartoesCriticos = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->where('tem_cartao_credito', 1)
            ->where('limite_cartao', '>', 0)
            ->get()
            ->map(function ($b) use ($tenantId, $inicioMes, $fimMes) {
                $b->gastos = (float) DB::table('despesas')
                    ->where('tenant_id', $tenantId)->whereNull('deleted_at')
                    ->where('forma_pagamento', $b->id)
                    ->whereBetween('data_compra', [$inicioMes, $fimMes])
                    ->sum('valor');
                $b->pct = $b->limite_cartao > 0 ? ($b->gastos / (float) $b->limite_cartao) * 100 : 0;
                return $b;
            })
            ->filter(fn($b) => $b->pct >= 90);

        foreach ($cartoesCriticos as $c) {
            $alertas->push([
                'tipo'      => 'urgente',
                'icone'     => 'fa-credit-card',
                'titulo'    => 'Limite do cartão quase esgotado: ' . $c->nome,
                'descricao' => 'O cartão <strong>' . $c->nome . '</strong> está com <strong>' . number_format($c->pct, 1) . '%</strong> do limite utilizado. Apenas <strong>R$ ' . number_format($c->limite_cartao - $c->gastos, 2, ',', '.') . '</strong> disponível.',
                'acao_url'  => route('despesas.index'),
                'acao_txt'  => 'Ver despesas',
                'detalhe'   => [],
            ]);
        }

        // ═══════════════════════════════════════════════════════════════════
        // 🟡 ATENÇÃO — Monitorar de perto
        // ═══════════════════════════════════════════════════════════════════

        // 4. Contas a vencer nos próximos 7 dias
        $aVencer = DB::table('despesas')
            ->leftJoin('categorias', 'despesas.categoria_id', '=', 'categorias.id')
            ->selectRaw("despesas.id, despesas.onde_comprou as descricao, despesas.valor, despesas.data_compra, COALESCE(categorias.nome,'Sem categoria') as categoria")
            ->where('despesas.tenant_id', $tenantId)
            ->whereNull('despesas.deleted_at')
            ->whereNull('despesas.data_pagamento')
            ->whereBetween('despesas.data_compra', [$hoje, $em7dias])
            ->orderBy('despesas.data_compra')
            ->get();

        if ($aVencer->count() > 0) {
            $totalAVencer = $aVencer->sum('valor');
            $alertas->push([
                'tipo'      => 'atencao',
                'icone'     => 'fa-calendar-days',
                'titulo'    => $aVencer->count() . ' conta(s) vencem nos próximos 7 dias',
                'descricao' => 'Separe <strong>R$ ' . number_format($totalAVencer, 2, ',', '.') . '</strong> para pagamentos que vencem em breve.',
                'acao_url'  => route('fluxo-caixa.index'),
                'acao_txt'  => 'Ver vencimentos',
                'detalhe'   => $aVencer->map(fn($d) => Carbon::parse($d->data_compra)->format('d/m') . ' — ' . $d->descricao . ' (R$ ' . number_format($d->valor, 2, ',', '.') . ')')->toArray(),
            ]);
        }

        // 5. Receitas a receber nos próximos 7 dias
        $aReceber = DB::table('receitas')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereNull('data_recebimento')
            ->whereBetween('data_prevista_recebimento', [$hoje, $em7dias])
            ->select('id', 'valor', 'data_prevista_recebimento', 'observacoes')
            ->get();

        if ($aReceber->count() > 0) {
            $totalAReceber = $aReceber->sum('valor');
            $alertas->push([
                'tipo'      => 'atencao',
                'icone'     => 'fa-hand-holding-dollar',
                'titulo'    => $aReceber->count() . ' receita(s) previstas para os próximos 7 dias',
                'descricao' => 'Você tem <strong>R$ ' . number_format($totalAReceber, 2, ',', '.') . '</strong> a receber em breve. Confirme os recebimentos.',
                'acao_url'  => route('receitas.index'),
                'acao_txt'  => 'Ver receitas',
                'detalhe'   => $aReceber->map(fn($r) => Carbon::parse($r->data_prevista_recebimento)->format('d/m') . ' — ' . ($r->observacoes ?: 'Receita prevista') . ' (R$ ' . number_format($r->valor, 2, ',', '.') . ')')->toArray(),
            ]);
        }

        // 6. Cartões entre 70-89% do limite
        $cartoesAtencao = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->where('tem_cartao_credito', 1)
            ->where('limite_cartao', '>', 0)
            ->get()
            ->map(function ($b) use ($tenantId, $inicioMes, $fimMes) {
                $b->gastos = (float) DB::table('despesas')
                    ->where('tenant_id', $tenantId)->whereNull('deleted_at')
                    ->where('forma_pagamento', $b->id)
                    ->whereBetween('data_compra', [$inicioMes, $fimMes])
                    ->sum('valor');
                $b->pct = $b->limite_cartao > 0 ? ($b->gastos / (float) $b->limite_cartao) * 100 : 0;
                return $b;
            })
            ->filter(fn($b) => $b->pct >= 70 && $b->pct < 90);

        foreach ($cartoesAtencao as $c) {
            $alertas->push([
                'tipo'      => 'atencao',
                'icone'     => 'fa-credit-card',
                'titulo'    => 'Limite do cartão em uso elevado: ' . $c->nome,
                'descricao' => 'O cartão <strong>' . $c->nome . '</strong> está com <strong>' . number_format($c->pct, 1) . '%</strong> utilizado. Ainda disponível: <strong>R$ ' . number_format($c->limite_cartao - $c->gastos, 2, ',', '.') . '</strong>.',
                'acao_url'  => route('despesas.index'),
                'acao_txt'  => 'Ver despesas',
                'detalhe'   => [],
            ]);
        }

        // 7. Saldo bancário baixo (< R$500, mas positivo)
        $bancosLow = DB::table('bancos')
            ->where('tenant_id', $tenantId)
            ->where('saldo', '>=', 0)
            ->where('saldo', '<', 500)
            ->get();

        foreach ($bancosLow as $banco) {
            $alertas->push([
                'tipo'      => 'atencao',
                'icone'     => 'fa-wallet',
                'titulo'    => 'Saldo baixo na conta: ' . $banco->nome,
                'descricao' => 'A conta <strong>' . $banco->nome . '</strong> tem apenas <strong>R$ ' . number_format($banco->saldo, 2, ',', '.') . '</strong> disponível. Fique atento para não ficar no negativo.',
                'acao_url'  => route('bancos.index'),
                'acao_txt'  => 'Ver conta',
                'detalhe'   => [],
            ]);
        }

        // 8. Projeção do mês negativa
        $receitasPrevistas = DB::table('receitas')
            ->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereBetween('data_prevista_recebimento', [$inicioMes, $fimMes])
            ->sum('valor');

        $despesasPrevistas = DB::table('despesas')
            ->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereBetween('data_compra', [$inicioMes, $fimMes])
            ->sum('valor');

        $projecaoMes = (float) $receitasPrevistas - (float) $despesasPrevistas;

        if ($projecaoMes < 0) {
            $alertas->push([
                'tipo'      => 'atencao',
                'icone'     => 'fa-chart-line',
                'titulo'    => 'Projeção do mês negativa',
                'descricao' => 'Suas despesas previstas (<strong>R$ ' . number_format($despesasPrevistas, 2, ',', '.') . '</strong>) superam as receitas previstas (<strong>R$ ' . number_format($receitasPrevistas, 2, ',', '.') . '</strong>). Saldo projetado: <strong class="text-danger">R$ ' . number_format($projecaoMes, 2, ',', '.') . '</strong>.',
                'acao_url'  => route('dashboard'),
                'acao_txt'  => 'Ver dashboard',
                'detalhe'   => [],
            ]);
        }

        // ═══════════════════════════════════════════════════════════════════
        // 🔵 ATENÇÃO — Gastos acima do mês anterior por categoria
        // ═══════════════════════════════════════════════════════════════════

        $catsMesAtual = DB::table('despesas')
            ->join('categorias', 'despesas.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.id as cat_id, categorias.nome as cat_nome, SUM(despesas.valor) as total')
            ->where('despesas.tenant_id', $tenantId)->whereNull('despesas.deleted_at')
            ->whereBetween('despesas.data_compra', [$inicioMes, $fimMes])
            ->groupBy('categorias.id', 'categorias.nome')
            ->get()->keyBy('cat_id');

        $catsMesAnt = DB::table('despesas')
            ->join('categorias', 'despesas.categoria_id', '=', 'categorias.id')
            ->selectRaw('categorias.id as cat_id, categorias.nome as cat_nome, SUM(despesas.valor) as total')
            ->where('despesas.tenant_id', $tenantId)->whereNull('despesas.deleted_at')
            ->whereBetween('despesas.data_compra', [$inicioAnt, $fimAnt])
            ->groupBy('categorias.id', 'categorias.nome')
            ->get()->keyBy('cat_id');

        foreach ($catsMesAtual as $catId => $catAtual) {
            if (isset($catsMesAnt[$catId])) {
                $anterior = (float) $catsMesAnt[$catId]->total;
                $atual    = (float) $catAtual->total;
                if ($anterior > 0) {
                    $variacao = (($atual - $anterior) / $anterior) * 100;
                    if ($variacao >= 30) {
                        $alertas->push([
                            'tipo'      => 'info',
                            'icone'     => 'fa-arrow-trend-up',
                            'titulo'    => 'Aumento de gastos: ' . $catAtual->cat_nome,
                            'descricao' => 'Você gastou <strong>R$ ' . number_format($atual, 2, ',', '.') . '</strong> em <strong>' . $catAtual->cat_nome . '</strong> este mês — <strong>' . number_format($variacao, 1) . '% a mais</strong> do que no mês anterior (R$ ' . number_format($anterior, 2, ',', '.') . ').',
                            'acao_url'  => route('despesas.index'),
                            'acao_txt'  => 'Ver despesas',
                            'detalhe'   => [],
                        ]);
                    }
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // 💚 POSITIVO — Boas notícias
        // ═══════════════════════════════════════════════════════════════════

        // Projeção positiva
        if ($projecaoMes > 0) {
            $alertas->push([
                'tipo'      => 'positivo',
                'icone'     => 'fa-circle-check',
                'titulo'    => 'Mês no positivo',
                'descricao' => 'Parabéns! Suas receitas superam as despesas previstas este mês. Sobra projetada: <strong>R$ ' . number_format($projecaoMes, 2, ',', '.') . '</strong>. Considere investir parte desse valor.',
                'acao_url'  => route('dashboard'),
                'acao_txt'  => 'Ver dashboard',
                'detalhe'   => [],
            ]);
        }

        // Categorias onde gastou menos que o mês anterior
        $economiaCats = [];
        foreach ($catsMesAnt as $catId => $catAnt) {
            $atualVal = isset($catsMesAtual[$catId]) ? (float) $catsMesAtual[$catId]->total : 0;
            $antVal   = (float) $catAnt->total;
            if ($antVal > 100 && $atualVal < $antVal) {
                $economia = $antVal - $atualVal;
                $economiaCats[] = [
                    'nome'      => $catAnt->cat_nome,
                    'economia'  => $economia,
                    'variacao'  => (($antVal - $atualVal) / $antVal) * 100,
                ];
            }
        }
        usort($economiaCats, fn($a, $b) => $b['economia'] <=> $a['economia']);
        $economiaCats = array_slice($economiaCats, 0, 3);

        if (!empty($economiaCats)) {
            $detalhe = array_map(fn($e) => $e['nome'] . ': economizou R$ ' . number_format($e['economia'], 2, ',', '.') . ' (' . number_format($e['variacao'], 1) . '% menos)', $economiaCats);
            $alertas->push([
                'tipo'      => 'positivo',
                'icone'     => 'fa-piggy-bank',
                'titulo'    => 'Você economizou em ' . count($economiaCats) . ' categoria(s) neste mês',
                'descricao' => 'Em relação ao mês anterior, seus gastos diminuíram em algumas categorias. Continue assim!',
                'acao_url'  => route('dashboard'),
                'acao_txt'  => 'Ver no dashboard',
                'detalhe'   => $detalhe,
            ]);
        }

        // ═══════════════════════════════════════════════════════════════════
        // 💡 DICAS — Onde economizar
        // ═══════════════════════════════════════════════════════════════════

        // Top 3 categorias de gastos do mês
        $topCats = $catsMesAtual->sortByDesc('total')->take(3)->values();
        if ($topCats->count() > 0) {
            $totalDespMes = $catsMesAtual->sum('total');
            $alertas->push([
                'tipo'      => 'dica',
                'icone'     => 'fa-magnifying-glass-chart',
                'titulo'    => 'Onde você mais gastou este mês',
                'descricao' => 'Acompanhe suas principais categorias de gasto e avalie onde é possível cortar.',
                'acao_url'  => route('despesas.index'),
                'acao_txt'  => 'Ver detalhes',
                'detalhe'   => $topCats->map(fn($c) => $c->cat_nome . ': R$ ' . number_format($c->total, 2, ',', '.') . ' (' . ($totalDespMes > 0 ? number_format(($c->total / $totalDespMes) * 100, 1) . '%' : '0%') . ' dos gastos)')->toArray(),
            ]);
        }

        // Receitas recebidas vs previstas
        $recRecebidas = DB::table('receitas')
            ->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereNotNull('data_recebimento')
            ->whereBetween('data_recebimento', [$inicioMes, $fimMes])
            ->sum('valor');

        if ($receitasPrevistas > 0) {
            $pctRecebido = ($recRecebidas / $receitasPrevistas) * 100;
            $alertas->push([
                'tipo'      => 'dica',
                'icone'     => 'fa-chart-pie',
                'titulo'    => 'Receitas: ' . number_format($pctRecebido, 0) . '% confirmadas no mês',
                'descricao' => 'Você recebeu <strong>R$ ' . number_format($recRecebidas, 2, ',', '.') . '</strong> dos <strong>R$ ' . number_format($receitasPrevistas, 2, ',', '.') . '</strong> previstos para este mês. ' . ($pctRecebido < 50 ? 'Mais da metade ainda está pendente.' : 'Bom progresso!'),
                'acao_url'  => route('receitas.index'),
                'acao_txt'  => 'Ver receitas',
                'detalhe'   => [],
            ]);
        }

        // Sugestão de reserva de emergência
        $totalSaldoBancos = DB::table('bancos')->where('tenant_id', $tenantId)->sum('saldo');
        $mediaDesp3meses = DB::table('despesas')
            ->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->where('data_compra', '>=', now()->subMonths(3)->format('Y-m-d'))
            ->sum('valor') / 3;

        $reservaIdeal = $mediaDesp3meses * 6;
        if ($totalSaldoBancos < $reservaIdeal && $mediaDesp3meses > 0) {
            $faltaReserva = $reservaIdeal - $totalSaldoBancos;
            $alertas->push([
                'tipo'      => 'dica',
                'icone'     => 'fa-shield-halved',
                'titulo'    => 'Reserva de emergência',
                'descricao' => 'Sua reserva ideal seria de <strong>R$ ' . number_format($reservaIdeal, 2, ',', '.') . '</strong> (6× sua média de gastos mensais). Você tem <strong>R$ ' . number_format(max(0, $totalSaldoBancos), 2, ',', '.') . '</strong>. Faltam <strong>R$ ' . number_format($faltaReserva, 2, ',', '.') . '</strong> para uma reserva sólida.',
                'acao_url'  => route('bancos.index'),
                'acao_txt'  => 'Ver contas',
                'detalhe'   => [],
            ]);
        }

        // ─── Contadores por tipo ─────────────────────────────────────────
        $contadores = [
            'urgente'  => $alertas->where('tipo', 'urgente')->count(),
            'atencao'  => $alertas->where('tipo', 'atencao')->count(),
            'info'     => $alertas->where('tipo', 'info')->count(),
            'positivo' => $alertas->where('tipo', 'positivo')->count(),
            'dica'     => $alertas->where('tipo', 'dica')->count(),
            'total'    => $alertas->count(),
        ];

        // Ordena por prioridade: urgente > atencao > info > dica > positivo
        $ordem = ['urgente' => 1, 'atencao' => 2, 'info' => 3, 'dica' => 4, 'positivo' => 5];
        $alertas = $alertas->sortBy(fn($a) => $ordem[$a['tipo']] ?? 9)->values();

        return view('alertas.index', compact('alertas', 'contadores'));
    }
}
