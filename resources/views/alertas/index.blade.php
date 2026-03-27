@extends('layouts.main')
@section('title', 'Alertas Financeiros')
@section('page-title', 'Alertas Financeiros')

@section('content')

@php
$config = [
    'urgente'  => ['cor' => '#ef4444', 'bg' => '#fef2f2', 'borda' => '#fca5a5', 'badge_bg' => '#ef4444', 'label' => 'Urgente'],
    'atencao'  => ['cor' => '#f59e0b', 'bg' => '#fffbeb', 'borda' => '#fcd34d', 'badge_bg' => '#f59e0b', 'label' => 'Atenção'],
    'info'     => ['cor' => '#3b82f6', 'bg' => '#eff6ff', 'borda' => '#93c5fd', 'badge_bg' => '#3b82f6', 'label' => 'Informativo'],
    'positivo' => ['cor' => '#16a34a', 'bg' => '#f0fdf4', 'borda' => '#86efac', 'badge_bg' => '#16a34a', 'label' => 'Positivo'],
    'dica'     => ['cor' => '#8b5cf6', 'bg' => '#f5f3ff', 'borda' => '#c4b5fd', 'badge_bg' => '#8b5cf6', 'label' => 'Dica'],
];
@endphp

{{-- Resumo --}}
<div class="d-flex align-center justify-between flex-wrap gap-3 mb-5">
    <div>
        <p style="color:#64748b; font-size:14px; margin:0;">
            Análise financeira inteligente do período atual.
            <strong>{{ $contadores['total'] }} alerta(s)</strong> encontrado(s).
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @foreach(['urgente','atencao','info','dica','positivo'] as $tipo)
        @if($contadores[$tipo] > 0)
        <span style="background:{{ $config[$tipo]['bg'] }}; color:{{ $config[$tipo]['cor'] }}; border:1px solid {{ $config[$tipo]['borda'] }}; padding:4px 12px; border-radius:99px; font-size:13px; font-weight:600; white-space:nowrap;">
            {{ $config[$tipo]['label'] }}: {{ $contadores[$tipo] }}
        </span>
        @endif
        @endforeach
    </div>
</div>

@if($alertas->isEmpty())
<div class="card text-center" style="padding: 60px 20px;">
    <div style="font-size: 64px; margin-bottom: 16px;">🎉</div>
    <h3 style="color:#16a34a; font-size:22px; margin-bottom:8px;">Tudo em ordem!</h3>
    <p style="color:#64748b; font-size:15px;">Nenhum alerta financeiro identificado para o período atual. Continue com o bom trabalho!</p>
</div>
@else

{{-- Grid de alertas --}}
<div style="display: flex; flex-direction: column; gap: 10px;">
    @foreach($alertas as $alerta)
    @php $c = $config[$alerta['tipo']]; @endphp
    <div style="
        background: {{ $c['bg'] }};
        border: 1px solid {{ $c['borda'] }};
        border-left: 4px solid {{ $c['cor'] }};
        border-radius: 10px;
        padding: 12px 16px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    ">
        {{-- Ícone --}}
        <div style="
            width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
            background: {{ $c['cor'] }}22;
            display: flex; align-items: center; justify-content: center;
        ">
            <i class="fa-solid {{ $alerta['icone'] }}" style="font-size: 14px; color: {{ $c['cor'] }};"></i>
        </div>

        {{-- Conteúdo --}}
        <div style="flex: 1; min-width: 0;">
            <div class="d-flex align-center gap-2 flex-wrap" style="margin-bottom: 3px;">
                <span style="
                    background: {{ $c['cor'] }}; color: #fff;
                    font-size: 9px; font-weight: 700; letter-spacing: .5px;
                    padding: 2px 7px; border-radius: 99px; text-transform: uppercase;
                ">{{ $c['label'] }}</span>
                <strong style="font-size: 13px; color: #1e293b;">{{ $alerta['titulo'] }}</strong>
            </div>

            <p style="color: #475569; font-size: 13px; margin: 2px 0 8px 0; line-height: 1.5;">
                {!! $alerta['descricao'] !!}
            </p>

            @if(!empty($alerta['detalhe']))
            <ul style="margin: 0 0 8px 0; padding-left: 16px; color: #475569; font-size: 12px; line-height: 1.7;">
                @foreach($alerta['detalhe'] as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
            @endif

            <a href="{{ $alerta['acao_url'] }}"
               style="
                   display: inline-flex; align-items: center; gap: 5px;
                   background: {{ $c['cor'] }}; color: #fff;
                   font-size: 12px; font-weight: 600;
                   padding: 5px 12px; border-radius: 6px;
                   text-decoration: none;
               ">
                {{ $alerta['acao_txt'] }} <i class="fa-solid fa-arrow-right" style="font-size:10px;"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>

@endif

{{-- Rodapé informativo --}}
<div style="margin-top: 32px; padding: 16px 20px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
    <div class="d-flex align-center gap-3 flex-wrap">
        <i class="fa-solid fa-circle-info" style="color: #94a3b8; font-size: 16px;"></i>
        <span style="font-size: 13px; color: #64748b;">
            Os alertas são calculados em tempo real com base nos seus lançamentos. Atualize a página para ver as informações mais recentes.
        </span>
        <a href="{{ route('alertas.index') }}" class="btn btn-secondary btn-sm" style="margin-left:auto; font-size:12px;">
            <i class="fa-solid fa-rotate-right me-1"></i> Atualizar alertas
        </a>
    </div>
</div>

@endsection
