@extends('layouts.main')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard da Revenda')

@section('content')

{{-- Cards de resumo --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px;">
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:var(--color-primary);">{{ $clientes->count() }}</div>
        <div class="text-muted" style="font-size:13px;">Total de Clientes</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:#16a34a;">{{ $totalAtivos }}</div>
        <div class="text-muted" style="font-size:13px;">Ativos</div>
    </div>
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:#dc2626;">{{ $totalInativos }}</div>
        <div class="text-muted" style="font-size:13px;">Inativos</div>
    </div>
    @foreach($porPlano as $nomePlano => $qtd)
    <div class="card" style="text-align:center;padding:20px;">
        <div style="font-size:28px;font-weight:700;color:var(--color-text);">{{ $qtd }}</div>
        <div class="text-muted" style="font-size:13px;">{{ $nomePlano }}</div>
    </div>
    @endforeach
</div>

{{-- Tabela de renovações --}}
<div class="card">
    <div class="card-title">
        <i class="fa-solid fa-calendar-check" style="color:var(--color-primary);"></i> Próximas Renovações
    </div>

    @if($renovacoes->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-calendar-check"></i>
            <p>Nenhum cliente com data de vencimento cadastrada.</p>
        </div>
    @else
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="hide-mobile">Plano</th>
                    <th class="hide-mobile">Cobrança</th>
                    <th>Vencimento</th>
                    <th>Dias Restantes</th>
                    <th class="hide-mobile">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($renovacoes as $cliente)
                @php
                    $dias = $cliente->diasRestantes();
                @endphp
                <tr>
                    <td class="fw-600">{{ $cliente->nome }}</td>
                    <td class="hide-mobile">{{ $cliente->plano?->nome ?? '—' }}</td>
                    <td class="hide-mobile">{{ $cliente->tipo_cobranca === 'anual' ? 'Anual' : 'Mensal' }}</td>
                    <td>{{ $cliente->data_fim_plano->format('d/m/Y') }}</td>
                    <td>
                        @if($dias !== null)
                            @if($dias <= 0)
                                <span class="badge badge-red">Vencido</span>
                            @elseif($dias <= 5)
                                <span class="badge badge-red">{{ $dias }} dias</span>
                            @elseif($dias <= 15)
                                <span class="badge badge-yellow">{{ $dias }} dias</span>
                            @else
                                <span class="badge badge-green">{{ $dias }} dias</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="hide-mobile">
                        @if($cliente->status === 'ativo')
                            <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px"></i> Ativo</span>
                        @else
                            <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inativo</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
