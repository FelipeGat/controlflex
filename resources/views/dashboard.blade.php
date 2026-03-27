@extends('layouts.main')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Filtros: datas à esquerda, membros à direita — tudo em uma linha --}}
<div class="card mb-5" style="padding: 10px 16px;">
    <div class="d-flex align-center gap-2 flex-wrap" style="min-height:56px;justify-content:center;">

        {{-- Esquerda: navegação por mês + período personalizado --}}
        <div class="d-flex align-center gap-1" style="flex-shrink:0;">
            <a href="{{ $linkMesAnt }}" class="btn btn-secondary btn-sm" style="padding:6px 10px;font-size:15px;line-height:1;" title="Mês anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <a href="{{ route('dashboard', array_filter(['inicio' => now()->startOfMonth()->format('Y-m-d'), 'fim' => now()->endOfMonth()->format('Y-m-d'), 'familiar_id' => $familiarId])) }}"
               class="btn btn-primary" style="min-width:0;text-align:center;font-weight:700;font-size:13px;letter-spacing:.5px;white-space:nowrap;">
                {{ $nomeMes }} {{ $anoMes }}
            </a>
            <a href="{{ $linkMesProx }}" class="btn btn-secondary btn-sm" style="padding:6px 10px;font-size:15px;line-height:1;" title="Próximo mês">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-center gap-2 flex-wrap" style="min-width:0;justify-content:center;">
            @if($familiarId)<input type="hidden" name="familiar_id" value="{{ $familiarId }}">@endif
            <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="max-width:130px;min-width:0;font-size:12px;">
            <span style="color:#94a3b8;">—</span>
            <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="max-width:130px;min-width:0;font-size:12px;">
            <button type="submit" class="btn btn-secondary btn-sm" title="Filtrar período"><i class="fa-solid fa-filter"></i></button>
        </form>

        {{-- Botão Limpar Filtros (aparece sempre que há algum filtro ativo) --}}
        @php
            $mesAtualInicio = now()->startOfMonth()->format('Y-m-d');
            $mesAtualFim    = now()->endOfMonth()->format('Y-m-d');
            $filtroAtivo    = $familiarId || $inicio !== $mesAtualInicio || $fim !== $mesAtualFim;
        @endphp
        @if($filtroAtivo)
        <a href="{{ route('dashboard') }}" class="btn btn-sm" title="Limpar todos os filtros"
           style="background:#fee2e2; color:#ef4444; border:1px solid #fca5a5; white-space:nowrap; font-size:12px; font-weight:600;">
            <i class="fa-solid fa-xmark me-1"></i> Limpar filtros
        </a>
        @endif

        {{-- Divisor --}}
        <div class="hide-mobile" style="width:1px; height:36px; background:#e2e8f0; margin: 0 4px;"></div>

        {{-- Direita: avatares dos membros --}}
        <div class="d-flex align-center gap-2" style="overflow-x:auto;-webkit-overflow-scrolling:touch;flex-shrink:0;justify-content:center;">
            @foreach($familiares as $fam)
            @php
                $isSelected = $familiarId === $fam->id;
                $iniciais = implode('', array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice(explode(' ', $fam->nome), 0, 2)));
                $cores = ['#6366f1','#0ea5e9','#16a34a','#f59e0b','#ef4444','#8b5cf6','#14b8a6'];
                $cor = $cores[$fam->id % count($cores)];
            @endphp
            <a href="{{ $isSelected
                    ? route('dashboard', array_filter(['inicio' => $inicio, 'fim' => $fim]))
                    : route('dashboard', array_filter(['inicio' => $inicio, 'fim' => $fim, 'familiar_id' => $fam->id])) }}"
               class="d-flex flex-column align-center gap-1 text-decoration-none"
               title="{{ $fam->nome }} {{ $isSelected ? '(clique para ver todos)' : '' }}"
               style="text-align:center;">
                <div style="
                    width:40px; height:40px; border-radius:50%; overflow:hidden; flex-shrink:0;
                    border: 3px solid {{ $isSelected ? $cor : 'transparent' }};
                    box-shadow: {{ $isSelected ? '0 0 0 2px '.$cor.'44' : 'none' }};
                    outline: {{ $isSelected ? 'none' : '2px solid #e2e8f0' }};
                    transition: all .2s;
                ">
                    @if($fam->foto)
                        <img src="{{ Storage::url($fam->foto) }}" alt="{{ $fam->nome }}"
                             style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <div style="
                            width:100%; height:100%;
                            background: {{ $cor }};
                            color:#fff; font-weight:700; font-size:13px;
                            display:flex; align-items:center; justify-content:center;
                        ">{{ $iniciais }}</div>
                    @endif
                </div>
                <span style="font-size:10px; color: {{ $isSelected ? $cor : '#64748b' }}; font-weight: {{ $isSelected ? '700' : '400' }}; max-width:50px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; line-height:1.2;">
                    {{ explode(' ', $fam->nome)[0] }}
                </span>
            </a>
            @endforeach
        </div>

    </div>
</div>

{{-- KPI Cards Principais --}}
<div class="grid-3 mb-5">
    <div class="card" style="border-top: 3px solid #16a34a;">
        <div class="d-flex justify-between align-center">
            <div>
                <div class="kpi-label">Receitas Previstas</div>
                <div class="kpi-value text-green">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</div>
                <div class="mt-1">
                    @if($variacaoReceitas >= 0)
                        <span class="badge badge-green"><i class="fa-solid fa-arrow-up"></i> {{ number_format($variacaoReceitas, 1) }}%</span>
                    @else
                        <span class="badge badge-red"><i class="fa-solid fa-arrow-down"></i> {{ number_format(abs($variacaoReceitas), 1) }}%</span>
                    @endif
                    <span class="text-subtle" style="font-size:11px;"> vs mês anterior</span>
                </div>
            </div>
            <div class="kpi-icon" style="background:#dcfce7; color:#16a34a;">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </div>
        <div class="kpi-sub">
            Realizado: <strong class="text-green">R$ {{ number_format($receitasRealizadas, 2, ',', '.') }}</strong>
        </div>
    </div>

    <div class="card" style="border-top: 3px solid #dc2626;">
        <div class="d-flex justify-between align-center">
            <div>
                <div class="kpi-label">Despesas Previstas</div>
                <div class="kpi-value text-red">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</div>
                <div class="mt-1">
                    @if($variacaoDespesas <= 0)
                        <span class="badge badge-green"><i class="fa-solid fa-arrow-down"></i> {{ number_format(abs($variacaoDespesas), 1) }}%</span>
                    @else
                        <span class="badge badge-red"><i class="fa-solid fa-arrow-up"></i> {{ number_format($variacaoDespesas, 1) }}%</span>
                    @endif
                    <span class="text-subtle" style="font-size:11px;"> vs mês anterior</span>
                </div>
            </div>
            <div class="kpi-icon" style="background:#fee2e2; color:#dc2626;">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
        </div>
        <div class="kpi-sub">
            Realizado: <strong class="text-red">R$ {{ number_format($despesasRealizadas, 2, ',', '.') }}</strong>
        </div>
    </div>

    <div class="card" style="border-top: 3px solid {{ $saldo >= 0 ? '#4f46e5' : '#dc2626' }};">
        <div class="d-flex justify-between align-center">
            <div>
                <div class="kpi-label">Saldo do Período</div>
                <div class="kpi-value" style="color: {{ $saldo >= 0 ? '#4f46e5' : '#dc2626' }}">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </div>
                <div class="mt-1">
                    @if($variacaoSaldo >= 0)
                        <span class="badge badge-green"><i class="fa-solid fa-arrow-up"></i> {{ number_format($variacaoSaldo, 1) }}%</span>
                    @else
                        <span class="badge badge-red"><i class="fa-solid fa-arrow-down"></i> {{ number_format(abs($variacaoSaldo), 1) }}%</span>
                    @endif
                    <span class="text-subtle" style="font-size:11px;"> vs mês anterior</span>
                </div>
            </div>
            <div class="kpi-icon" style="background:#ede9fe; color:#6d28d9;">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
        <div class="kpi-sub">
            Total Investido: <strong style="color:#6d28d9">R$ {{ number_format($totalInvestido, 2, ',', '.') }}</strong>
        </div>
    </div>
</div>

{{-- Info cards --}}
<div class="grid-4 mb-5">
    <div class="card">
        <div class="kpi-label">Pago — Último Mês</div>
        <div class="kpi-value text-red mt-1">R$ {{ number_format($pagamentoUltimoMes, 2, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="kpi-label">Recebido — Último Mês</div>
        <div class="kpi-value text-green mt-1">R$ {{ number_format($recebidoUltimoMes, 2, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="kpi-label">À Pagar — Próx. Mês</div>
        <div class="kpi-value text-amber mt-1">R$ {{ number_format($previsaoDespesasProxMes, 2, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="kpi-label">À Receber — Próx. Mês</div>
        <div class="kpi-value mt-1" style="color:#4f46e5">R$ {{ number_format($previsaoReceitasProxMes, 2, ',', '.') }}</div>
    </div>
</div>

{{-- Gráfico: Fluxo de caixa (100%) --}}
<div class="mb-5">
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-chart-column" style="color:#4f46e5;"></i>
            Fluxo de Caixa {{ $ano }}
        </div>
        <div class="chart-box">
            <canvas id="annualChart"></canvas>
        </div>
    </div>
</div>

{{-- Gastos com Cartões + Patrimônio Acumulado --}}
<div class="grid-2 mb-5">
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-credit-card" style="color:#7c3aed;"></i>
            Gastos com Cartões
        </div>

        {{-- Resumo geral --}}
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;padding:10px 12px;background:#f8fafc;border-radius:8px;margin-bottom:12px;gap:8px;">
            <div style="text-align:center;flex:1;min-width:80px;">
                <div style="font-size:11px;color:#64748b;">Fatura Total</div>
                <div class="fw-600 text-red" style="font-size:14px;">R$ {{ number_format($totalFaturaCartoes, 2, ',', '.') }}</div>
            </div>
            <div style="width:1px;height:30px;background:#e2e8f0;"></div>
            <div style="text-align:center;flex:1;min-width:80px;">
                <div style="font-size:11px;color:#64748b;">Gastos no Período</div>
                <div class="fw-600" style="font-size:14px;color:#d97706;">R$ {{ number_format($totalGastosCartoes, 2, ',', '.') }}</div>
            </div>
            <div style="width:1px;height:30px;background:#e2e8f0;"></div>
            <div style="text-align:center;flex:1;min-width:80px;">
                <div style="font-size:11px;color:#64748b;">Limite Total</div>
                <div class="fw-600" style="font-size:14px;color:#4f46e5;">R$ {{ number_format($totalLimiteCartoes, 2, ',', '.') }}</div>
            </div>
        </div>

        {{-- Lista de cartões --}}
        @forelse($cartoes as $cartao)
            <div style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #f1f5f9;' : '' }}">
                <div class="d-flex justify-between align-center mb-1">
                    <div class="d-flex align-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:{{ $cartao->cor ?? '#7c3aed' }}20;">
                            <i class="fa-solid fa-credit-card" style="font-size:13px;color:{{ $cartao->cor ?? '#7c3aed' }};"></i>
                        </div>
                        <div>
                            <div class="fw-600" style="font-size:13px;">{{ $cartao->nome }}</div>
                            <div style="font-size:11px;color:#94a3b8;">Limite: R$ {{ number_format($cartao->limite_cartao, 2, ',', '.') }}</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="fw-600 text-red" style="font-size:13px;">R$ {{ number_format($cartao->saldo_cartao, 2, ',', '.') }}</div>
                        <div style="font-size:11px;color:#64748b;">Período: R$ {{ number_format($cartao->gastos_periodo, 2, ',', '.') }}</div>
                    </div>
                </div>
                {{-- Barra de uso do limite --}}
                <div style="background:#f1f5f9;border-radius:4px;height:6px;overflow:hidden;margin-top:6px;">
                    <div style="height:100%;border-radius:4px;width:{{ min($cartao->percentual_uso, 100) }}%;background:{{ $cartao->percentual_uso > 80 ? '#dc2626' : ($cartao->percentual_uso > 50 ? '#d97706' : '#16a34a') }};"></div>
                </div>
                <div class="d-flex justify-between" style="margin-top:3px;">
                    <span style="font-size:10px;color:#94a3b8;">{{ $cartao->percentual_uso }}% usado</span>
                    <span style="font-size:10px;color:#16a34a;">Disponível: R$ {{ number_format($cartao->limite_disponivel, 2, ',', '.') }}</span>
                </div>
                @if($cartao->dia_fechamento_cartao)
                    @php $melhorDia = $cartao->dia_fechamento_cartao >= 28 ? 1 : $cartao->dia_fechamento_cartao + 1; @endphp
                    <div style="font-size:10px;margin-top:4px;padding:3px 6px;background:#ede9fe;border-radius:3px;color:#7c3aed;text-align:center;">
                        <i class="fa-solid fa-lightbulb" style="font-size:9px;"></i> Melhor compra: dia <strong>{{ $melhorDia }}</strong>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state"><i class="fa-solid fa-credit-card"></i><p>Nenhum cartão cadastrado</p></div>
        @endforelse
    </div>

    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-chart-line" style="color:#d97706;"></i>
            Patrimônio Acumulado {{ $ano }}
        </div>
        <div class="chart-box">
            <canvas id="investChart"></canvas>
        </div>
    </div>
</div>

{{-- Gráficos: Categorias --}}
<div class="grid-2 mb-5">
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-chart-pie" style="color:#dc2626;"></i>
            Despesas por Categoria
        </div>
        @if(count($despesasPorCategoria) > 0)
            <div class="chart-box-sm"><canvas id="expCatChart"></canvas></div>
        @else
            <div class="empty-state"><i class="fa-regular fa-circle-xmark"></i><p>Sem despesas no período</p></div>
        @endif
    </div>
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-chart-pie" style="color:#16a34a;"></i>
            Receitas por Categoria
        </div>
        @if(count($receitasPorCategoria) > 0)
            <div class="chart-box-sm"><canvas id="incCatChart"></canvas></div>
        @else
            <div class="empty-state"><i class="fa-regular fa-circle-xmark"></i><p>Sem receitas no período</p></div>
        @endif
    </div>
</div>

{{-- Contas + Últimos Lançamentos --}}
<div class="grid-2">
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-building-columns"></i> Contas Bancárias
        </div>
        @forelse($bancos as $banco)
            <div class="d-flex justify-between align-center" style="padding:9px 0; border-bottom:1px solid #f8fafc;">
                <div>
                    <div class="fw-600" style="font-size:13px;">{{ $banco->nome }}</div>
                    <div style="font-size:11px;" class="text-subtle">{{ implode(', ', array_filter([$banco->eh_dinheiro ? 'Dinheiro' : '', $banco->tem_conta_corrente ? 'Conta Corrente' : '', $banco->tem_poupanca ? 'Poupança' : '', $banco->tem_cartao_credito ? 'Cartão de Crédito' : ''])) ?: 'Nenhum' }}</div>
                </div>
                <div class="text-right">
                    <div class="fw-600 {{ $banco->saldo >= 0 ? 'text-green' : 'text-red' }}" style="font-size:14px;">
                        R$ {{ number_format($banco->saldo, 2, ',', '.') }}
                    </div>
                    @if($banco->limite_cartao > 0)
                        <div style="font-size:11px;" class="text-subtle">
                            Cartão: R$ {{ number_format($banco->saldo_cartao, 2, ',', '.') }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-state"><i class="fa-solid fa-building-columns"></i><p>Nenhuma conta cadastrada</p></div>
        @endforelse
        <div class="mt-3">
            <a href="{{ route('bancos.index') }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">
                Ver todas
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-clock-rotate-left"></i> Últimos Lançamentos
        </div>
        @forelse($ultimosLancamentos as $lancamento)
            <div class="d-flex justify-between align-center" style="padding:8px 0; border-bottom:1px solid #f8fafc;">
                <div class="d-flex align-center gap-2">
                    <div style="width:30px;height:30px;border-radius:6px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:{{ $lancamento->tipo === 'receita' ? '#dcfce7' : '#fee2e2' }};">
                        <i class="fa-solid {{ $lancamento->tipo === 'receita' ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="font-size:11px;color:{{ $lancamento->tipo === 'receita' ? '#16a34a' : '#dc2626' }};"></i>
                    </div>
                    <div>
                        <div class="fw-600" style="font-size:13px;">{{ $lancamento->categoria_nome }}</div>
                        <div style="font-size:11px;" class="text-subtle">{{ \Carbon\Carbon::parse($lancamento->data)->format('d/m/Y') }}</div>
                    </div>
                </div>
                <div class="fw-600 {{ $lancamento->tipo === 'receita' ? 'text-green' : 'text-red' }}" style="font-size:13px;">
                    {{ $lancamento->tipo === 'receita' ? '+' : '−' }} R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                </div>
            </div>
        @empty
            <div class="empty-state"><i class="fa-regular fa-folder-open"></i><p>Nenhum lançamento encontrado</p></div>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
const _meses = {!! json_encode($mesesLabels) !!};
const _colors = ['#4f46e5','#16a34a','#d97706','#dc2626','#7c3aed','#db2777','#0891b2','#ea580c','#0d9488','#65a30d','#9333ea','#2563eb'];
const _chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12, padding: 10 } } }
};

new Chart(document.getElementById('annualChart'), {
    type: 'bar',
    data: {
        labels: _meses,
        datasets: [
            { label: 'Receitas', data: {!! json_encode($receitasMes) !!}, backgroundColor: 'rgba(22,163,74,.7)', borderRadius: 4, borderSkipped: false },
            { label: 'Despesas', data: {!! json_encode($despesasMes) !!}, backgroundColor: 'rgba(220,38,38,.65)', borderRadius: 4, borderSkipped: false }
        ]
    },
    options: { ..._chartDefaults, scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 10 } }, grid: { display: false } } } }
});

new Chart(document.getElementById('investChart'), {
    type: 'line',
    data: {
        labels: _meses,
        datasets: [{ label: 'Patrimônio (R$)', data: {!! json_encode($patrimonioAcumulado) !!}, borderColor: '#d97706', backgroundColor: 'rgba(217,119,6,.08)', fill: true, tension: .35, pointRadius: 3 }]
    },
    options: { ..._chartDefaults, scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f1f5f9' } }, x: { ticks: { font: { size: 10 } }, grid: { display: false } } } }
});

@if(count($despesasPorCategoria) > 0)
new Chart(document.getElementById('expCatChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($despesasPorCategoria->pluck('nome')) !!},
        datasets: [{ data: {!! json_encode($despesasPorCategoria->pluck('total')) !!}, backgroundColor: _colors, borderWidth: 1 }]
    },
    options: { ..._chartDefaults, cutout: '60%' }
});
@endif

@if(count($receitasPorCategoria) > 0)
new Chart(document.getElementById('incCatChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($receitasPorCategoria->pluck('nome')) !!},
        datasets: [{ data: {!! json_encode($receitasPorCategoria->pluck('total')) !!}, backgroundColor: _colors, borderWidth: 1 }]
    },
    options: { ..._chartDefaults, cutout: '60%' }
});
@endif
</script>
@endpush
