@extends('layouts.main')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
{{-- Filtro de Período --}}
<form method="GET" action="{{ route('dashboard') }}" style="margin-bottom: 24px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
    <div style="display:flex;align-items:center;gap:8px;">
        <label class="form-label" style="margin:0;">De:</label>
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="width:160px;">
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <label class="form-label" style="margin:0;">Até:</label>
        <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="width:160px;">
    </div>
    <button type="submit" class="btn-primary">
        <i class="fa-solid fa-filter"></i> Filtrar
    </button>
    <a href="{{ route('dashboard') }}" class="btn-secondary">Mês Atual</a>
</form>

{{-- KPI Cards Principais --}}
<div class="grid-3" style="margin-bottom: 24px;">
    <div class="card" style="border-left: 4px solid #10b981;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div class="text-muted">Receitas Previstas</div>
                <div style="font-size:26px;font-weight:800;color:#10b981;margin:4px 0;">
                    R$ {{ number_format($totalReceitas, 2, ',', '.') }}
                </div>
                <div style="font-size:12px;color:#94a3b8;">
                    @if($variacaoReceitas >= 0)
                        <span class="badge badge-success"><i class="fa-solid fa-arrow-up"></i> {{ number_format($variacaoReceitas, 1) }}%</span>
                    @else
                        <span class="badge badge-danger"><i class="fa-solid fa-arrow-down"></i> {{ number_format(abs($variacaoReceitas), 1) }}%</span>
                    @endif
                    vs mês anterior
                </div>
            </div>
            <div class="kpi-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </div>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b;">
            Realizado: <strong style="color:#16a34a;">R$ {{ number_format($receitasRealizadas, 2, ',', '.') }}</strong>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid #ef4444;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div class="text-muted">Despesas Previstas</div>
                <div style="font-size:26px;font-weight:800;color:#ef4444;margin:4px 0;">
                    R$ {{ number_format($totalDespesas, 2, ',', '.') }}
                </div>
                <div style="font-size:12px;color:#94a3b8;">
                    @if($variacaoDespesas <= 0)
                        <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> {{ number_format(abs($variacaoDespesas), 1) }}%</span>
                    @else
                        <span class="badge badge-danger"><i class="fa-solid fa-arrow-up"></i> {{ number_format($variacaoDespesas, 1) }}%</span>
                    @endif
                    vs mês anterior
                </div>
            </div>
            <div class="kpi-icon" style="background:#fee2e2;color:#dc2626;">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
        </div>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b;">
            Realizado: <strong style="color:#dc2626;">R$ {{ number_format($despesasRealizadas, 2, ',', '.') }}</strong>
        </div>
    </div>

    <div class="card" style="border-left: 4px solid {{ $saldo >= 0 ? '#6366f1' : '#ef4444' }};">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div class="text-muted">Saldo do Período</div>
                <div style="font-size:26px;font-weight:800;color:{{ $saldo >= 0 ? '#6366f1' : '#ef4444' }};margin:4px 0;">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </div>
                <div style="font-size:12px;color:#94a3b8;">
                    @if($variacaoSaldo >= 0)
                        <span class="badge badge-success"><i class="fa-solid fa-arrow-up"></i> {{ number_format($variacaoSaldo, 1) }}%</span>
                    @else
                        <span class="badge badge-danger"><i class="fa-solid fa-arrow-down"></i> {{ number_format(abs($variacaoSaldo), 1) }}%</span>
                    @endif
                    vs mês anterior
                </div>
            </div>
            <div class="kpi-icon" style="background:#ede9fe;color:#7c3aed;">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b;">
            Total Investido: <strong style="color:#7c3aed;">R$ {{ number_format($totalInvestido, 2, ',', '.') }}</strong>
        </div>
    </div>
</div>

{{-- Infocards: Último Mês / Próximo Mês --}}
<div class="grid-4" style="margin-bottom: 24px;">
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.04em;">Pago (Últ. Mês)</div>
        <div style="font-size:20px;font-weight:800;color:#ef4444;margin-top:4px;">R$ {{ number_format($pagamentoUltimoMes, 2, ',', '.') }}</div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.04em;">Recebido (Últ. Mês)</div>
        <div style="font-size:20px;font-weight:800;color:#10b981;margin-top:4px;">R$ {{ number_format($recebidoUltimoMes, 2, ',', '.') }}</div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.04em;">À Pagar (Próx. Mês)</div>
        <div style="font-size:20px;font-weight:800;color:#f59e0b;margin-top:4px;">R$ {{ number_format($previsaoDespesasProxMes, 2, ',', '.') }}</div>
    </div>
    <div class="card" style="padding:16px;">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.04em;">À Receber (Próx. Mês)</div>
        <div style="font-size:20px;font-weight:800;color:#6366f1;margin-top:4px;">R$ {{ number_format($previsaoReceitasProxMes, 2, ',', '.') }}</div>
    </div>
</div>

{{-- Gráficos --}}
<div class="grid-2" style="margin-bottom: 24px;">
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:16px;">
            <i class="fa-solid fa-chart-line" style="color:#6366f1;"></i> Fluxo de Caixa {{ $ano }}
        </div>
        <canvas id="annualChart" height="200"></canvas>
    </div>
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:16px;">
            <i class="fa-solid fa-chart-bar" style="color:#f59e0b;"></i> Patrimônio Acumulado {{ $ano }}
        </div>
        <canvas id="investChart" height="200"></canvas>
    </div>
</div>

<div class="grid-2" style="margin-bottom: 24px;">
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:16px;">
            <i class="fa-solid fa-chart-pie" style="color:#ef4444;"></i> Despesas por Categoria
        </div>
        @if(count($despesasPorCategoria) > 0)
            <canvas id="expCatChart" height="220"></canvas>
        @else
            <div style="text-align:center;padding:40px;color:#94a3b8;">Sem despesas no período</div>
        @endif
    </div>
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:16px;">
            <i class="fa-solid fa-chart-pie" style="color:#10b981;"></i> Receitas por Categoria
        </div>
        @if(count($receitasPorCategoria) > 0)
            <canvas id="incCatChart" height="220"></canvas>
        @else
            <div style="text-align:center;padding:40px;color:#94a3b8;">Sem receitas no período</div>
        @endif
    </div>
</div>

{{-- Saldos e Últimos Lançamentos --}}
<div class="grid-2" style="margin-bottom: 24px;">
    {{-- Saldos Bancários --}}
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:16px;">
            <i class="fa-solid fa-building-columns" style="color:#6366f1;"></i> Contas Bancárias
        </div>
        @forelse($bancos as $banco)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f8fafc;">
                <div>
                    <div style="font-weight:600;font-size:14px;color:#1e293b;">{{ $banco->nome }}</div>
                    <div style="font-size:12px;color:#94a3b8;">{{ $banco->tipo_conta }}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:700;font-size:15px;color:{{ $banco->saldo >= 0 ? '#16a34a' : '#dc2626' }};">
                        R$ {{ number_format($banco->saldo, 2, ',', '.') }}
                    </div>
                    @if($banco->limite_cartao > 0)
                        <div style="font-size:11px;color:#94a3b8;">Cartão: R$ {{ number_format($banco->saldo_cartao, 2, ',', '.') }} / {{ number_format($banco->limite_cartao, 2, ',', '.') }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:24px;color:#94a3b8;">
                <i class="fa-solid fa-building-columns" style="font-size:32px;margin-bottom:8px;display:block;"></i>
                Nenhuma conta cadastrada
            </div>
        @endforelse
        <div style="margin-top:12px;">
            <a href="{{ route('bancos.index') }}" class="btn-secondary" style="font-size:12px;width:100%;justify-content:center;">Ver todas</a>
        </div>
    </div>

    {{-- Últimos Lançamentos --}}
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#1e293b;margin-bottom:16px;">
            <i class="fa-solid fa-list" style="color:#6366f1;"></i> Últimos Lançamentos
        </div>
        @forelse($ultimosLancamentos as $lancamento)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f8fafc;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:8px;background:{{ $lancamento->tipo === 'receita' ? '#dcfce7' : '#fee2e2' }};display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid {{ $lancamento->tipo === 'receita' ? 'fa-arrow-down' : 'fa-arrow-up' }}" style="color:{{ $lancamento->tipo === 'receita' ? '#16a34a' : '#dc2626' }};font-size:12px;"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $lancamento->categoria_nome }}</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ \Carbon\Carbon::parse($lancamento->data)->format('d/m/Y') }}</div>
                    </div>
                </div>
                <div style="font-weight:700;font-size:14px;color:{{ $lancamento->tipo === 'receita' ? '#16a34a' : '#dc2626' }};">
                    {{ $lancamento->tipo === 'receita' ? '+' : '-' }} R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:24px;color:#94a3b8;">Nenhum lançamento encontrado</div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
const meses = {!! json_encode($mesesLabels) !!};
const chartColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#06b6d4','#84cc16','#a855f7','#3b82f6'];

// Fluxo de caixa anual
new Chart(document.getElementById('annualChart'), {
    type: 'bar',
    data: {
        labels: meses,
        datasets: [
            { label: 'Receitas', data: {!! json_encode($receitasMes) !!}, backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 6 },
            { label: 'Despesas', data: {!! json_encode($despesasMes) !!}, backgroundColor: 'rgba(239,68,68,.7)', borderRadius: 6 }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

// Patrimônio
new Chart(document.getElementById('investChart'), {
    type: 'line',
    data: {
        labels: meses,
        datasets: [{ label: 'Patrimônio Acumulado', data: {!! json_encode($patrimonioAcumulado) !!}, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.1)', fill: true, tension: .4 }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

@if(count($despesasPorCategoria) > 0)
new Chart(document.getElementById('expCatChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($despesasPorCategoria->pluck('nome')) !!},
        datasets: [{ data: {!! json_encode($despesasPorCategoria->pluck('total')) !!}, backgroundColor: chartColors }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
@endif

@if(count($receitasPorCategoria) > 0)
new Chart(document.getElementById('incCatChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($receitasPorCategoria->pluck('nome')) !!},
        datasets: [{ data: {!! json_encode($receitasPorCategoria->pluck('total')) !!}, backgroundColor: chartColors }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
@endif
</script>
@endpush
