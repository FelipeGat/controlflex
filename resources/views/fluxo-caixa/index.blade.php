@extends('layouts.main')
@section('title', 'Contas a Pagar / Receber')
@section('page-title', 'Contas a Pagar / Receber')

@section('content')

{{-- ─── Filtro de período ─────────────────────────────────────────────────── --}}
<style>
.fc-filtro { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.fc-atalhos { display:flex; gap:6px; }
.fc-atalho-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:8px; font-size:12px; font-weight:700;
    text-decoration:none; border:1px solid #e2e8f0;
    transition:all .15s; white-space:nowrap;
}
.fc-atalho-btn.ativo { background:var(--color-primary); color:#fff; border-color:var(--color-primary); }
.fc-atalho-btn:not(.ativo) { background:#fff; color:#64748b; }
.fc-atalho-btn:not(.ativo):hover { background:#f8fafc; }
.fc-custom { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.fc-periodo-label {
    display:flex; align-items:center; gap:6px;
    font-size:11px; color:#94a3b8; padding-top:8px;
    border-top:1px solid #f1f5f9; margin-top:10px;
}
@media (max-width:640px) {
    .fc-filtro { flex-direction:column; align-items:stretch; }
    .fc-atalhos { justify-content:center; }
    .fc-custom { justify-content:center; }
    .fc-custom input.form-control { max-width:calc(50% - 24px) !important; }
}
.fc-kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:12px; }
@media (max-width:640px) { .fc-kpi-grid { grid-template-columns:1fr; } }
</style>

<div class="card filtros-bar">
    <div class="fc-filtro">

        {{-- Atalhos de período --}}
        <div class="fc-atalhos">
            <a href="{{ route('fluxo-caixa.index', ['periodo' => 'semana']) }}"
               class="fc-atalho-btn {{ $periodo === 'semana' ? 'ativo' : '' }}">
                <i class="fa-solid fa-calendar-week"></i> Esta Semana
            </a>
            <a href="{{ route('fluxo-caixa.index', ['periodo' => 'mes']) }}"
               class="fc-atalho-btn {{ $periodo === 'mes' ? 'ativo' : '' }}">
                <i class="fa-solid fa-calendar"></i> Este Mês
            </a>
        </div>

        {{-- Período personalizado --}}
        <form method="GET" action="{{ route('fluxo-caixa.index') }}" id="form-periodo">
            <input type="hidden" name="periodo" value="custom">
            <div class="fc-custom">
                <input type="date" name="inicio" value="{{ $inicio }}" class="form-control"
                       style="max-width:138px;font-size:12px;">
                <span style="color:#94a3b8;font-size:13px;">→</span>
                <input type="date" name="fim" value="{{ $fim }}" class="form-control"
                       style="max-width:138px;font-size:12px;">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-check"></i> Aplicar
                </button>
            </div>
        </form>

    </div>

    {{-- Período ativo exibido --}}
    <div class="fc-periodo-label">
        <i class="fa-regular fa-calendar" style="color:#cbd5e1;"></i>
        {{ \Carbon\Carbon::parse($inicio)->locale('pt_BR')->isoFormat('D [de] MMMM') }}
        <span style="color:#cbd5e1;">→</span>
        {{ \Carbon\Carbon::parse($fim)->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY') }}
        @if(in_array($periodo, ['semana','mes']))
        <span style="background:{{ $periodo === 'semana' ? '#ede9fe' : '#dbeafe' }};color:{{ $periodo === 'semana' ? '#7c3aed' : '#1d4ed8' }};font-size:10px;font-weight:700;padding:1px 8px;border-radius:20px;margin-left:4px;">
            {{ $periodo === 'semana' ? 'Esta Semana' : 'Este Mês' }}
        </span>
        @endif
    </div>
</div>

{{-- ─── KPIs ──────────────────────────────────────────────────────────────── --}}
<div class="fc-kpi-grid">

    <div class="card" style="border-top:3px solid #16a34a;">
        <div class="kpi-label"><i class="fa-solid fa-arrow-trend-up" style="color:#16a34a;"></i> A Receber</div>
        <div class="kpi-value text-green">R$ {{ number_format($totalAReceber,2,',','.') }}</div>
        @if($totalRecebido > 0)
        <div class="kpi-sub" style="color:#16a34a;">
            <i class="fa-solid fa-check"></i> R$ {{ number_format($totalRecebido,2,',','.') }} já recebido
        </div>
        @endif
    </div>

    <div class="card" style="border-top:3px solid #dc2626;">
        <div class="kpi-label"><i class="fa-solid fa-arrow-trend-down" style="color:#dc2626;"></i> A Pagar</div>
        <div class="kpi-value text-red">R$ {{ number_format($totalAPagar,2,',','.') }}</div>
        @if($totalPago > 0)
        <div class="kpi-sub" style="color:#16a34a;">
            <i class="fa-solid fa-check"></i> R$ {{ number_format($totalPago,2,',','.') }} já pago
        </div>
        @endif
    </div>

    <div class="card" style="border-top:3px solid {{ $saldoProjetado >= 0 ? '#4f46e5' : '#d97706' }};">
        <div class="kpi-label"><i class="fa-solid fa-scale-balanced" style="color:var(--color-primary);"></i> Saldo Projetado</div>
        <div class="kpi-value {{ $saldoProjetado >= 0 ? '' : 'text-red' }}" style="{{ $saldoProjetado >= 0 ? 'color:var(--color-primary)' : '' }}">
            R$ {{ number_format(abs($saldoProjetado),2,',','.') }}
        </div>
        <div class="kpi-sub">{{ $saldoProjetado >= 0 ? 'Superávit no período' : 'Déficit no período' }}</div>
    </div>

</div>

@php
    $totalDespesas = $despesas->count();
    $pagas = $despesas->whereNotNull('data_pagamento')->count();
    $totalReceitas = $receitas->count();
    $recebidas = $receitas->whereNotNull('data_recebimento')->count();
@endphp
<div class="card" style="border-top:3px solid #94a3b8;margin-bottom:20px;">
    <div class="kpi-label"><i class="fa-solid fa-list-check"></i> Progresso</div>
    <div style="margin-top:8px;display:flex;gap:24px;flex-wrap:wrap;">
        <div style="flex:1;min-width:180px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px;">
                <span class="text-muted">Pago</span>
                <span class="fw-600">{{ $pagas }}/{{ $totalDespesas }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-bar-fill" style="width:{{ $totalDespesas > 0 ? round($pagas/$totalDespesas*100) : 0 }}%;background:#dc2626;"></div>
            </div>
        </div>
        <div style="flex:1;min-width:180px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:3px;">
                <span class="text-muted">Recebido</span>
                <span class="fw-600">{{ $recebidas }}/{{ $totalReceitas }}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-bar-fill" style="width:{{ $totalReceitas > 0 ? round($recebidas/$totalReceitas*100) : 0 }}%;background:#16a34a;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Tabelas lado a lado ────────────────────────────────────────────────── --}}
<div class="grid-2">

    {{-- ── A RECEBER ──────────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-title" style="color:#16a34a;">
            <i class="fa-solid fa-arrow-trend-up" style="color:#16a34a;"></i>
            A Receber
            <span class="badge badge-green" style="margin-left:auto;">{{ $receitas->count() }}</span>
        </div>

        @if($receitas->isEmpty())
            <div class="empty-state" style="padding:24px 0;">
                <i class="fa-solid fa-inbox" style="font-size:28px;"></i>
                <p>Nenhuma receita no período.</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Descrição</th>
                        <th>Data</th>
                        <th style="text-align:right;">Valor</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receitas as $r)
                    @php $recebida = !is_null($r->data_recebimento); @endphp
                    <tr style="{{ $recebida ? 'opacity:.55;' : '' }}">
                        <td>
                            @if($recebida)
                                <span style="display:inline-flex;width:26px;height:26px;border-radius:50%;background:#dcfce7;align-items:center;justify-content:center;">
                                    <i class="fa-solid fa-check" style="color:#16a34a;font-size:11px;"></i>
                                </span>
                            @elseif($r->status === 'vencido')
                                <span style="display:inline-flex;width:26px;height:26px;border-radius:50%;background:#fee2e2;align-items:center;justify-content:center;" title="Vencido">
                                    <i class="fa-solid fa-exclamation" style="color:#dc2626;font-size:11px;"></i>
                                </span>
                            @else
                                <span style="display:inline-flex;width:26px;height:26px;border-radius:50%;background:#f0fdf4;align-items:center;justify-content:center;">
                                    <i class="fa-solid fa-clock" style="color:#16a34a;font-size:10px;"></i>
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-600" style="font-size:12px;{{ $recebida ? 'text-decoration:line-through;' : '' }}">
                                {{ $r->observacoes ?? $r->categoria?->nome ?? '—' }}
                            </div>
                            @if($r->familiar)
                            <div style="font-size:10px;color:var(--color-text-subtle);">
                                <i class="fa-solid fa-user"></i> {{ $r->familiar->nome }}
                            </div>
                            @endif
                            @if($recebida)
                            <div style="font-size:10px;color:#16a34a;">
                                Recebido em {{ $r->data_recebimento->format('d/m/Y') }}
                            </div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;font-size:12px;">
                            {{ $r->data_prevista_recebimento->format('d/m') }}
                            @if($r->status === 'vencido' && !$recebida)
                            <div style="font-size:10px;color:#dc2626;">Vencido</div>
                            @endif
                        </td>
                        <td style="text-align:right;font-weight:700;color:#16a34a;white-space:nowrap;font-size:13px;">
                            R$ {{ number_format($r->valor,2,',','.') }}
                        </td>
                        <td>
                            @if($recebida)
                                <form method="POST" action="{{ route('fluxo-caixa.estornar-receita', $r) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" title="Estornar baixa"
                                        style="font-size:10px;color:var(--color-text-subtle);"
                                        onclick="return confirm('Desfazer o recebimento?')">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </form>
                            @else
                                <button onclick="abrirBaixaReceita({{ $r->id }}, '{{ addslashes($r->observacoes ?? $r->categoria?->nome ?? 'Receita') }}', {{ $r->valor }})"
                                    class="btn btn-success btn-sm" style="font-size:11px;white-space:nowrap;">
                                    <i class="fa-solid fa-check"></i> Baixar
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── A PAGAR ─────────────────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-title" style="color:#dc2626;">
            <i class="fa-solid fa-arrow-trend-down" style="color:#dc2626;"></i>
            A Pagar
            <span class="badge badge-red" style="margin-left:auto;">{{ $despesas->count() }}</span>
        </div>

        @if($despesas->isEmpty())
            <div class="empty-state" style="padding:24px 0;">
                <i class="fa-solid fa-inbox" style="font-size:28px;"></i>
                <p>Nenhuma despesa no período.</p>
            </div>
        @else
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Descrição</th>
                        <th>Vence</th>
                        <th style="text-align:right;">Valor</th>
                        <th style="width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($despesas as $d)
                    @php $paga = !is_null($d->data_pagamento); @endphp
                    <tr style="{{ $paga ? 'opacity:.55;' : '' }}">
                        <td>
                            @if($paga)
                                <span style="display:inline-flex;width:26px;height:26px;border-radius:50%;background:#dcfce7;align-items:center;justify-content:center;">
                                    <i class="fa-solid fa-check" style="color:#16a34a;font-size:11px;"></i>
                                </span>
                            @elseif($d->status === 'vencido')
                                <span style="display:inline-flex;width:26px;height:26px;border-radius:50%;background:#fee2e2;align-items:center;justify-content:center;" title="Vencido">
                                    <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;font-size:10px;"></i>
                                </span>
                            @else
                                <span style="display:inline-flex;width:26px;height:26px;border-radius:50%;background:#fff0f0;align-items:center;justify-content:center;">
                                    <i class="fa-solid fa-clock" style="color:#dc2626;font-size:10px;"></i>
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-600" style="font-size:12px;{{ $paga ? 'text-decoration:line-through;' : '' }}">
                                {{ $d->observacoes ?? $d->fornecedor?->nome ?? $d->categoria?->nome ?? '—' }}
                            </div>
                            @if($d->banco)
                            <div style="font-size:10px;color:var(--color-text-subtle);">
                                <i class="fa-solid fa-credit-card"></i> {{ $d->banco->nome }}
                            </div>
                            @endif
                            @if($paga)
                            <div style="font-size:10px;color:#16a34a;">
                                Pago em {{ $d->data_pagamento->format('d/m/Y') }}
                            </div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;font-size:12px;">
                            {{ $d->data_compra->format('d/m') }}
                            @if($d->status === 'vencido' && !$paga)
                            <div style="font-size:10px;color:#dc2626;">Vencido</div>
                            @endif
                        </td>
                        <td style="text-align:right;font-weight:700;color:#dc2626;white-space:nowrap;font-size:13px;">
                            R$ {{ number_format($d->valor,2,',','.') }}
                        </td>
                        <td>
                            @if($paga)
                                <form method="POST" action="{{ route('fluxo-caixa.estornar-despesa', $d) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm" title="Estornar baixa"
                                        style="font-size:10px;color:var(--color-text-subtle);"
                                        onclick="return confirm('Desfazer o pagamento?')">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>
                                </form>
                            @else
                                <button onclick="abrirBaixaDespesa({{ $d->id }}, '{{ addslashes($d->observacoes ?? $d->fornecedor?->nome ?? $d->categoria?->nome ?? 'Despesa') }}', {{ $d->valor }})"
                                    class="btn btn-danger btn-sm" style="font-size:11px;white-space:nowrap;">
                                    <i class="fa-solid fa-check"></i> Baixar
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>{{-- /grid-2 --}}

{{-- ─── Modal Baixar Receita ──────────────────────────────────────────────── --}}
<div class="modal-backdrop" id="modal-baixa-receita">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <i class="fa-solid fa-check-circle" style="color:#16a34a;"></i>
            <h3>Confirmar Recebimento</h3>
            <button class="modal-close" onclick="closeModal('modal-baixa-receita')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="baixa-receita-desc"
                 style="font-size:13px;font-weight:600;margin-bottom:4px;color:var(--color-text);"></div>
            <div id="baixa-receita-val"
                 style="font-size:20px;font-weight:700;color:#16a34a;margin-bottom:16px;"></div>
            <form method="POST" action="" id="form-baixa-receita">
                @csrf
                <div class="form-group">
                    <label class="form-label">Data do Recebimento</label>
                    <input type="date" name="data_recebimento" id="baixa-receita-data"
                           class="form-control" required value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-baixa-receita')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-check"></i> Confirmar Recebimento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ─── Modal Baixar Despesa ──────────────────────────────────────────────── --}}
<div class="modal-backdrop" id="modal-baixa-despesa">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <i class="fa-solid fa-check-circle" style="color:#dc2626;"></i>
            <h3>Confirmar Pagamento</h3>
            <button class="modal-close" onclick="closeModal('modal-baixa-despesa')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="baixa-despesa-desc"
                 style="font-size:13px;font-weight:600;margin-bottom:4px;color:var(--color-text);"></div>
            <div id="baixa-despesa-val"
                 style="font-size:20px;font-weight:700;color:#dc2626;margin-bottom:16px;"></div>
            <form method="POST" action="" id="form-baixa-despesa">
                @csrf
                <div class="form-group">
                    <label class="form-label">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" id="baixa-despesa-data"
                           class="form-control" required value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-baixa-despesa')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-check"></i> Confirmar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function abrirBaixaReceita(id, desc, valor) {
    document.getElementById('form-baixa-receita').action = `/fluxo-caixa/baixar-receita/${id}`;
    document.getElementById('baixa-receita-desc').textContent = desc;
    document.getElementById('baixa-receita-val').textContent  = 'R$ ' + valor.toFixed(2).replace('.', ',');
    document.getElementById('baixa-receita-data').value = '{{ now()->format('Y-m-d') }}';
    openModal('modal-baixa-receita');
}

function abrirBaixaDespesa(id, desc, valor) {
    document.getElementById('form-baixa-despesa').action = `/fluxo-caixa/baixar-despesa/${id}`;
    document.getElementById('baixa-despesa-desc').textContent = desc;
    document.getElementById('baixa-despesa-val').textContent  = 'R$ ' + valor.toFixed(2).replace('.', ',');
    document.getElementById('baixa-despesa-data').value = '{{ now()->format('Y-m-d') }}';
    openModal('modal-baixa-despesa');
}
</script>
@endpush
