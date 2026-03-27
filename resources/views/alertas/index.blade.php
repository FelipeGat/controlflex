@extends('layouts.main')
@section('title', 'Alertas Financeiros')
@section('page-title', 'Alertas Financeiros')

@section('content')

@php
$config = [
    'urgente'  => ['cor' => '#ef4444', 'bg' => '#fef2f2', 'borda' => '#fca5a5', 'label' => 'Urgente'],
    'atencao'  => ['cor' => '#f59e0b', 'bg' => '#fffbeb', 'borda' => '#fcd34d', 'label' => 'Atenção'],
    'info'     => ['cor' => '#3b82f6', 'bg' => '#eff6ff', 'borda' => '#93c5fd', 'label' => 'Informativo'],
    'positivo' => ['cor' => '#16a34a', 'bg' => '#f0fdf4', 'borda' => '#86efac', 'label' => 'Positivo'],
    'dica'     => ['cor' => '#8b5cf6', 'bg' => '#f5f3ff', 'borda' => '#c4b5fd', 'label' => 'Dica'],
];
@endphp

{{-- Resumo --}}
<div class="d-flex align-center justify-between flex-wrap gap-2 mb-4">
    <p style="color:#64748b; font-size:13px; margin:0;">
        <strong>{{ $contadores['total'] }}</strong> alerta(s) encontrado(s) — clique para expandir detalhes.
    </p>
    <div class="d-flex gap-2 flex-wrap">
        @foreach(['urgente','atencao','info','dica','positivo'] as $tipo)
        @if($contadores[$tipo] > 0)
        <span style="background:{{ $config[$tipo]['bg'] }}; color:{{ $config[$tipo]['cor'] }}; border:1px solid {{ $config[$tipo]['borda'] }}; padding:3px 10px; border-radius:99px; font-size:12px; font-weight:600;">
            {{ $config[$tipo]['label'] }}: {{ $contadores[$tipo] }}
        </span>
        @endif
        @endforeach
    </div>
</div>

@if($alertas->isEmpty())
<div class="card text-center" style="padding: 40px 20px;">
    <div style="font-size: 48px; margin-bottom: 12px;">🎉</div>
    <h3 style="color:#16a34a; font-size:18px; margin-bottom:6px;">Tudo em ordem!</h3>
    <p style="color:#64748b; font-size:14px;">Nenhum alerta financeiro identificado para o período atual.</p>
</div>
@else

{{-- Grid de alertas compactos --}}
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 10px;">
    @foreach($alertas as $i => $alerta)
    @php $c = $config[$alerta['tipo']]; $temDetalhe = !empty($alerta['detalhe']); @endphp

    <div style="
        background: #fff;
        border: 1px solid {{ $c['borda'] }};
        border-top: 3px solid {{ $c['cor'] }};
        border-radius: 10px;
        overflow: hidden;
    ">
        {{-- Cabeçalho clicável --}}
        <div onclick="toggleAlerta({{ $i }})"
             style="padding: 10px 14px; cursor: {{ $temDetalhe ? 'pointer' : 'default' }}; display:flex; align-items:center; gap:10px;">

            {{-- Ícone --}}
            <div style="
                width:30px; height:30px; border-radius:8px; flex-shrink:0;
                background:{{ $c['cor'] }}18;
                display:flex; align-items:center; justify-content:center;
            ">
                <i class="fa-solid {{ $alerta['icone'] }}" style="font-size:12px; color:{{ $c['cor'] }};"></i>
            </div>

            {{-- Texto --}}
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:6px; margin-bottom:2px;">
                    <span style="
                        background:{{ $c['cor'] }}; color:#fff;
                        font-size:9px; font-weight:700; letter-spacing:.4px;
                        padding:1px 6px; border-radius:99px; text-transform:uppercase; flex-shrink:0;
                    ">{{ $c['label'] }}</span>
                    <span style="font-size:12px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $alerta['titulo'] }}
                    </span>
                </div>
                <p style="font-size:11px; color:#64748b; margin:0; line-height:1.4;">{!! $alerta['descricao'] !!}</p>
            </div>

            @if($temDetalhe)
            <i id="chevron-{{ $i }}" class="fa-solid fa-chevron-down" style="font-size:10px; color:#94a3b8; flex-shrink:0; transition:.2s;"></i>
            @endif
        </div>

        {{-- Detalhe colapsável --}}
        @if($temDetalhe)
        <div id="detalhe-{{ $i }}" style="display:none; padding: 0 14px 10px 54px; border-top:1px solid {{ $c['borda'] }}; margin-top:0; padding-top:8px;">
            <ul style="margin:0; padding-left:14px; color:#475569; font-size:11px; line-height:1.8;">
                @foreach($alerta['detalhe'] as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Rodapé com ação --}}
        <div style="padding: 6px 14px 10px 14px; display:flex; justify-content:flex-end;">
            <a href="{{ $alerta['acao_url'] }}"
               style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; color:{{ $c['cor'] }}; text-decoration:none;">
                {{ $alerta['acao_txt'] }} <i class="fa-solid fa-arrow-right" style="font-size:9px;"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>

@endif

{{-- Rodapé --}}
<div style="margin-top:20px; padding:10px 16px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:10px;">
    <i class="fa-solid fa-circle-info" style="color:#94a3b8; font-size:13px;"></i>
    <span style="font-size:12px; color:#64748b; flex:1;">Alertas calculados em tempo real com base nos seus lançamentos.</span>
    <a href="{{ route('alertas.index') }}" class="btn btn-secondary btn-sm" style="font-size:11px; padding:4px 10px;">
        <i class="fa-solid fa-rotate-right me-1"></i> Atualizar
    </a>
</div>

<script>
function toggleAlerta(i) {
    const detalhe  = document.getElementById('detalhe-' + i);
    const chevron  = document.getElementById('chevron-' + i);
    if (!detalhe) return;
    const aberto = detalhe.style.display !== 'none';
    detalhe.style.display = aberto ? 'none' : 'block';
    if (chevron) chevron.style.transform = aberto ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>

@endsection

