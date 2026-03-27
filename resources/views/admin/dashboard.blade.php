@extends('layouts.main')
@section('title', 'Painel SaaS')
@section('page-title', 'Painel SaaS')

@section('content')
<div class="section-header">
    <h2><i class="fa-solid fa-chart-pie" style="color:var(--color-primary)"></i> Dashboard SaaS</h2>
</div>

{{-- KPIs Revendas --}}
<div style="font-size:12px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;margin-bottom:8px;">Revendas</div>
<div class="grid-4 mb-4">
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Total Revendas</div>
        <div class="kpi-value" style="color:var(--color-primary)">{{ $totalRevendas }}</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Ativas</div>
        <div class="kpi-value" style="color:var(--color-success)">{{ $revendasAtivas }}</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Inativas</div>
        <div class="kpi-value" style="color:var(--color-danger)">{{ $revendasInativas }}</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Total Usuários</div>
        <div class="kpi-value">{{ $totalUsuarios }}</div>
    </div>
</div>

{{-- KPIs Clientes --}}
<div style="font-size:12px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;margin-bottom:8px;">Clientes (Tenants)</div>
<div class="grid-3 mb-4">
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Total Clientes</div>
        <div class="kpi-value" style="color:var(--color-primary)">{{ $totalTenants }}</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Ativos</div>
        <div class="kpi-value" style="color:var(--color-success)">{{ $tenantsAtivos }}</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div class="text-subtle" style="font-size:12px;margin-bottom:4px;">Inativos</div>
        <div class="kpi-value" style="color:var(--color-danger)">{{ $tenantsInativos }}</div>
    </div>
</div>

{{-- Últimas Revendas --}}
<div class="card">
    <div class="d-flex align-center justify-between mb-3">
        <h3 style="font-size:15px;font-weight:600;"><i class="fa-solid fa-building" style="color:var(--color-primary);margin-right:6px;"></i> Últimas Revendas</h3>
        <a href="{{ route('admin.revendas.index') }}" class="btn btn-primary btn-sm">Ver todas</a>
    </div>
    @if($ultimasRevendas->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-building"></i>
            <p>Nenhuma revenda cadastrada ainda.</p>
        </div>
    @else
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th class="hide-mobile">Admin</th>
                    <th>Status</th>
                    <th class="hide-mobile">Plano</th>
                    <th class="hide-mobile">Clientes</th>
                    <th class="hide-mobile">Criada em</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ultimasRevendas as $revenda)
                <tr>
                    <td class="fw-600">{{ $revenda->nome }}</td>
                    <td class="text-muted hide-mobile">{{ $revenda->admin?->email ?? '—' }}</td>
                    <td>
                        @if($revenda->status === 'ativo')
                            <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px"></i> Ativo</span>
                        @else
                            <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inativo</span>
                        @endif
                    </td>
                    <td class="hide-mobile">{{ $revenda->plano?->nome ?? '—' }}</td>
                    <td class="hide-mobile">{{ $revenda->tenants_count }}</td>
                    <td class="text-muted hide-mobile">{{ $revenda->created_at->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
