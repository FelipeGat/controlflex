@extends('layouts.main')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Filtro de Período --}}
<form method="GET" action="{{ route('dashboard') }}" class="d-flex flex-wrap align-center gap-2 mb-5">
    <div class="d-flex align-center gap-2">
        <label class="form-label" style="margin:0;white-space:nowrap;">De</label>
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="width:140px;">
    </div>
    <div class="d-flex align-center gap-2">
        <label class="form-label" style="margin:0;white-space:nowrap;">Até</label>
        <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="width:140px;">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Mês Atual</a>
</form>

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

{{-- Gráficos: Fluxo de caixa + Patrimônio --}}
<div class="grid-2 mb-5">
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-chart-column" style="color:#4f46e5;"></i>
            Fluxo de Caixa {{ $ano }}
        </div>
        <div class="chart-box">
            <canvas id="annualChart"></canvas>
        </div>
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
                    <div style="font-size:11px;" class="text-subtle">{{ $banco->tipo_conta }}</div>
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
