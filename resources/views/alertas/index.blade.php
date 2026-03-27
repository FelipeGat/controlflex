@extends('layouts.main')
@section('title', 'Alertas Financeiros')
@section('page-title', 'Alertas Financeiros')

@section('content')

@php
$config = [
    'critico'      => ['cor' => '#ef4444', 'bg' => '#fef2f2', 'borda' => '#fca5a5', 'label' => 'Crítico'],
    'atencao'      => ['cor' => '#f59e0b', 'bg' => '#fffbeb', 'borda' => '#fcd34d', 'label' => 'Atenção'],
    'info'         => ['cor' => '#3b82f6', 'bg' => '#eff6ff', 'borda' => '#93c5fd', 'label' => 'Informativo'],
    'oportunidade' => ['cor' => '#16a34a', 'bg' => '#f0fdf4', 'borda' => '#86efac', 'label' => 'Oportunidade'],
];
@endphp

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 1. RESUMO EXECUTIVO --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; margin-bottom:20px;">
    {{-- Saldo projetado --}}
    <div style="background:#fff; border-radius:12px; padding:16px 20px; border:1px solid {{ $resumo['saldo_projetado'] < 0 ? '#fca5a5' : '#e2e8f0' }};">
        <div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">Saldo projetado</div>
        <div style="font-size:22px; font-weight:800; color:{{ $resumo['saldo_projetado'] < 0 ? '#ef4444' : '#16a34a' }};">
            R$ {{ number_format($resumo['saldo_projetado'], 2, ',', '.') }}
        </div>
    </div>

    {{-- Tendência --}}
    @php
        $tendCor = match($resumo['tendencia']) {
            'POSITIVA' => '#16a34a',
            'NEGATIVA' => '#ef4444',
            default    => '#64748b',
        };
        $tendIcone = match($resumo['tendencia']) {
            'POSITIVA' => 'fa-arrow-trend-up',
            'NEGATIVA' => 'fa-arrow-trend-down',
            default    => 'fa-minus',
        };
    @endphp
    <div style="background:#fff; border-radius:12px; padding:16px 20px; border:1px solid #e2e8f0;">
        <div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">Tendência</div>
        <div style="font-size:22px; font-weight:800; color:{{ $tendCor }}; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid {{ $tendIcone }}" style="font-size:18px;"></i>
            {{ $resumo['tendencia'] }}
        </div>
    </div>

    {{-- Alertas críticos --}}
    <div style="background:#fff; border-radius:12px; padding:16px 20px; border:1px solid {{ $resumo['alertas_criticos'] > 0 ? '#fca5a5' : '#e2e8f0' }};">
        <div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">Alertas críticos</div>
        <div style="font-size:22px; font-weight:800; color:{{ $resumo['alertas_criticos'] > 0 ? '#ef4444' : '#16a34a' }};">
            {{ $resumo['alertas_criticos'] }}
        </div>
    </div>

    {{-- Score financeiro --}}
    @php
        $scoreCor = $resumo['score'] >= 70 ? '#16a34a' : ($resumo['score'] >= 40 ? '#f59e0b' : '#ef4444');
    @endphp
    <div style="background:#fff; border-radius:12px; padding:16px 20px; border:1px solid #e2e8f0;">
        <div style="font-size:11px; color:#94a3b8; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px;">Score financeiro</div>
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="font-size:22px; font-weight:800; color:{{ $scoreCor }};">{{ $resumo['score'] }}<span style="font-size:14px; font-weight:600; color:#94a3b8;">/100</span></div>
            <div style="flex:1; height:8px; background:#f1f5f9; border-radius:99px; overflow:hidden;">
                <div style="width:{{ $resumo['score'] }}%; height:100%; background:{{ $scoreCor }}; border-radius:99px; transition:width .6s;"></div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 2. ALERTA PRINCIPAL (DESTAQUE) --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
@if($alertaPrincipal)
@php $cp = $config[$alertaPrincipal['tipo']] ?? $config['critico']; @endphp
<div style="
    background: linear-gradient(135deg, {{ $cp['cor'] }}10, {{ $cp['cor'] }}08);
    border: 2px solid {{ $cp['cor'] }};
    border-radius: 14px;
    padding: 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
">
    <div style="width:56px; height:56px; border-radius:14px; background:{{ $cp['cor'] }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
        <i class="fa-solid {{ $alertaPrincipal['icone'] }}" style="font-size:22px; color:#fff;"></i>
    </div>
    <div style="flex:1;">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
            <span style="background:{{ $cp['cor'] }}; color:#fff; font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; text-transform:uppercase; letter-spacing:.4px;">{{ $cp['label'] }}</span>
        </div>
        <h3 style="margin:0 0 6px; font-size:18px; font-weight:800; color:#1e293b;">{{ $alertaPrincipal['titulo'] }}</h3>
        <p style="margin:0 0 4px; font-size:14px; color:#475569; line-height:1.5;">{!! $alertaPrincipal['descricao'] !!}</p>
        <p style="margin:0; font-size:13px; color:{{ $cp['cor'] }}; font-weight:700;">{{ $alertaPrincipal['impacto'] }}</p>
        @if($alertaPrincipal['previsao'])
        <p style="margin:4px 0 0; font-size:12px; color:#64748b; font-style:italic;">
            <i class="fa-solid fa-forward" style="font-size:10px; margin-right:4px;"></i>{{ $alertaPrincipal['previsao'] }}
        </p>
        @endif
    </div>
    <a href="{{ $alertaPrincipal['acao_url'] }}"
       style="background:{{ $cp['cor'] }}; color:#fff; padding:10px 22px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; white-space:nowrap; display:inline-flex; align-items:center; gap:6px; flex-shrink:0;">
        {{ $alertaPrincipal['acao_label'] }} <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
    </a>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 3. FILTROS POR INTENÇÃO --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap;">
    <span style="font-size:12px; color:#94a3b8; font-weight:600; margin-right:4px;">FILTRAR:</span>

    <button onclick="filtrar('todos')" id="filtro-todos"
            style="border:1px solid #cbd5e1; background:#1e293b; color:#fff; padding:6px 14px; border-radius:99px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s;">
        Todos ({{ $contadores['total'] }})
    </button>

    <button onclick="filtrar('resolver')" id="filtro-resolver"
            style="border:1px solid #fca5a5; background:#fef2f2; color:#ef4444; padding:6px 14px; border-radius:99px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s;">
        <i class="fa-solid fa-bolt" style="font-size:10px; margin-right:3px;"></i>Resolver agora ({{ $alertas->where('intencao', 'resolver')->count() }})
    </button>

    <button onclick="filtrar('economizar')" id="filtro-economizar"
            style="border:1px solid #86efac; background:#f0fdf4; color:#16a34a; padding:6px 14px; border-radius:99px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s;">
        <i class="fa-solid fa-piggy-bank" style="font-size:10px; margin-right:3px;"></i>Economizar ({{ $alertas->where('intencao', 'economizar')->count() }})
    </button>

    <button onclick="filtrar('entender')" id="filtro-entender"
            style="border:1px solid #93c5fd; background:#eff6ff; color:#3b82f6; padding:6px 14px; border-radius:99px; font-size:12px; font-weight:600; cursor:pointer; transition:all .2s;">
        <i class="fa-solid fa-magnifying-glass" style="font-size:10px; margin-right:3px;"></i>Entender gastos ({{ $alertas->where('intencao', 'entender')->count() }})
    </button>

    {{-- Pills de tipo --}}
    <span style="width:1px; height:20px; background:#e2e8f0; margin:0 4px;"></span>
    @foreach(['critico','atencao','info','oportunidade'] as $tipo)
    @if($contadores[$tipo] > 0)
    <button onclick="filtrarTipo('{{ $tipo }}')" id="tipo-{{ $tipo }}"
            style="border:1px solid {{ $config[$tipo]['borda'] }}; background:{{ $config[$tipo]['bg'] }}; color:{{ $config[$tipo]['cor'] }}; padding:4px 10px; border-radius:99px; font-size:11px; font-weight:600; cursor:pointer; transition:all .2s;">
        {{ $config[$tipo]['label'] }}: {{ $contadores[$tipo] }}
    </button>
    @endif
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 4. LISTA DE ALERTAS (CARDS) --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
@if($alertas->isEmpty())
<div class="card text-center" style="padding: 40px 20px;">
    <div style="font-size: 48px; margin-bottom: 12px;">🎉</div>
    <h3 style="color:#16a34a; font-size:18px; margin-bottom:6px;">Tudo em ordem!</h3>
    <p style="color:#64748b; font-size:14px;">Nenhum alerta financeiro identificado para o período atual.</p>
</div>
@else

<div id="alertas-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:12px;">
    @foreach($alertas->slice(1) as $i => $alerta)
    @php
        $c = $config[$alerta['tipo']] ?? $config['info'];
        $temContas = !empty($alerta['contas_afetadas']);
        $hidden = $i >= 7 ? 'display:none;' : '';
    @endphp

    <div class="alerta-card" data-tipo="{{ $alerta['tipo'] }}" data-intencao="{{ $alerta['intencao'] }}" data-indice="{{ $i }}" style="
        background: #fff;
        border: 1px solid {{ $c['borda'] }};
        border-left: 4px solid {{ $c['cor'] }};
        border-radius: 12px;
        overflow: hidden;
        transition: all .2s;
        {{ $hidden }}
    ">
        {{-- Header --}}
        <div onclick="toggleAlerta('det-{{ $alerta['id'] }}')" style="padding:14px 16px; cursor:pointer; display:flex; align-items:flex-start; gap:12px;">
            <div style="width:36px; height:36px; border-radius:10px; background:{{ $c['cor'] }}15; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px;">
                <i class="fa-solid {{ $alerta['icone'] }}" style="font-size:14px; color:{{ $c['cor'] }};"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                    <span style="background:{{ $c['cor'] }}; color:#fff; font-size:9px; font-weight:700; padding:2px 7px; border-radius:99px; text-transform:uppercase; letter-spacing:.3px; flex-shrink:0;">{{ $c['label'] }}</span>
                    <span style="font-size:13px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $alerta['titulo'] }}</span>
                </div>
                <p style="font-size:12px; color:#64748b; margin:0 0 6px; line-height:1.5;">{!! $alerta['descricao'] !!}</p>
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    <span style="font-size:11px; font-weight:700; color:{{ $c['cor'] }};">
                        <i class="fa-solid fa-circle-dollar-to-slot" style="font-size:10px; margin-right:2px;"></i>{{ $alerta['impacto'] }}
                    </span>
                    @if($alerta['previsao'])
                    <span style="font-size:11px; color:#94a3b8; font-style:italic;">
                        <i class="fa-solid fa-forward" style="font-size:9px; margin-right:2px;"></i>{{ $alerta['previsao'] }}
                    </span>
                    @endif
                </div>
            </div>
            @if($temContas)
            <i id="chev-{{ $alerta['id'] }}" class="fa-solid fa-chevron-down" style="font-size:10px; color:#94a3b8; flex-shrink:0; margin-top:4px; transition:.2s;"></i>
            @endif
        </div>

        {{-- Contas afetadas (expansível) --}}
        @if($temContas)
        <div id="det-{{ $alerta['id'] }}" style="display:none; padding:0 16px 10px 64px; border-top:1px solid {{ $c['borda'] }}; padding-top:10px;">
            <ul style="margin:0; padding-left:14px; color:#475569; font-size:11px; line-height:1.9;">
                @foreach($alerta['contas_afetadas'] as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Ação --}}
        <div style="padding:8px 16px 14px; display:flex; justify-content:flex-end;">
            <a href="{{ $alerta['acao_url'] }}"
               style="display:inline-flex; align-items:center; gap:5px; background:{{ $c['cor'] }}; color:#fff; padding:6px 16px; border-radius:8px; font-size:11px; font-weight:700; text-decoration:none; transition:opacity .2s;"
               onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                {{ $alerta['acao_label'] }} <i class="fa-solid fa-arrow-right" style="font-size:9px;"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>

{{-- Botão ver todos --}}
@if($alertas->count() > 8)
<div id="ver-todos-wrap" style="text-align:center; margin-top:16px;">
    <button onclick="mostrarTodos()" id="btn-ver-todos"
            style="background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; padding:10px 24px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; transition:all .2s;">
        <i class="fa-solid fa-chevron-down" style="margin-right:6px;"></i>
        Ver todos os alertas ({{ $alertas->count() - 1 }} restantes)
    </button>
</div>
@endif

@endif

{{-- Rodapé --}}
<div style="margin-top:20px; padding:10px 16px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:10px;">
    <i class="fa-solid fa-robot" style="color:#94a3b8; font-size:13px;"></i>
    <span style="font-size:12px; color:#64748b; flex:1;">Consultor financeiro automático — alertas calculados em tempo real com base nos seus lançamentos.</span>
    <a href="{{ route('alertas.index') }}" class="btn btn-secondary btn-sm" style="font-size:11px; padding:4px 10px;">
        <i class="fa-solid fa-rotate-right me-1"></i> Atualizar
    </a>
</div>

<script>
let filtroAtual = 'todos';
let tipoAtual = null;

const filtrosBtns = {
    todos:      { bg: '#1e293b', cor: '#fff', border: '#1e293b' },
    resolver:   { bg: '#ef4444', cor: '#fff', border: '#ef4444' },
    economizar: { bg: '#16a34a', cor: '#fff', border: '#16a34a' },
    entender:   { bg: '#3b82f6', cor: '#fff', border: '#3b82f6' },
};

const filtrosDefault = {
    todos:      { bg: '#1e293b', cor: '#fff', border: '#cbd5e1' },
    resolver:   { bg: '#fef2f2', cor: '#ef4444', border: '#fca5a5' },
    economizar: { bg: '#f0fdf4', cor: '#16a34a', border: '#86efac' },
    entender:   { bg: '#eff6ff', cor: '#3b82f6', border: '#93c5fd' },
};

const configCores = @json($config);

function aplicarEstiloBotao(id, bg, cor, border) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.background = bg;
    el.style.color = cor;
    el.style.borderColor = border;
}

function filtrar(intencao) {
    tipoAtual = null;
    filtroAtual = intencao;
    const cards = document.querySelectorAll('.alerta-card');

    cards.forEach(c => {
        const match = intencao === 'todos' || c.getAttribute('data-intencao') === intencao;
        c.style.display = match ? '' : 'none';
    });

    // Reset tipo pills
    ['critico','atencao','info','oportunidade'].forEach(t => {
        const el = document.getElementById('tipo-' + t);
        if (!el) return;
        const cfg = configCores[t];
        el.style.background = cfg.bg;
        el.style.color = cfg.cor;
        el.style.borderColor = cfg.borda;
        el.style.opacity = '1';
    });

    // Estilo filtros intenção
    ['todos','resolver','economizar','entender'].forEach(f => {
        if (f === intencao) {
            aplicarEstiloBotao('filtro-' + f, filtrosBtns[f].bg, filtrosBtns[f].cor, filtrosBtns[f].border);
        } else {
            aplicarEstiloBotao('filtro-' + f, filtrosDefault[f].bg, filtrosDefault[f].cor, filtrosDefault[f].border);
        }
    });

    const wrap = document.getElementById('ver-todos-wrap');
    if (wrap) wrap.style.display = intencao === 'todos' ? '' : 'none';
}

function filtrarTipo(tipo) {
    if (tipoAtual === tipo) {
        filtrar('todos');
        return;
    }

    tipoAtual = tipo;
    filtroAtual = null;

    const cards = document.querySelectorAll('.alerta-card');
    cards.forEach(c => {
        c.style.display = c.getAttribute('data-tipo') === tipo ? '' : 'none';
    });

    // Reset intenção
    ['todos','resolver','economizar','entender'].forEach(f => {
        aplicarEstiloBotao('filtro-' + f, filtrosDefault[f].bg, filtrosDefault[f].cor, filtrosDefault[f].border);
    });

    // Estilo tipo pills
    ['critico','atencao','info','oportunidade'].forEach(t => {
        const el = document.getElementById('tipo-' + t);
        if (!el) return;
        const cfg = configCores[t];
        if (t === tipo) {
            el.style.background = cfg.cor;
            el.style.color = '#fff';
            el.style.opacity = '1';
        } else {
            el.style.background = cfg.bg;
            el.style.color = cfg.cor;
            el.style.opacity = '0.4';
        }
    });

    const wrap = document.getElementById('ver-todos-wrap');
    if (wrap) wrap.style.display = 'none';
}

function toggleAlerta(detId) {
    const det = document.getElementById(detId);
    const chevId = detId.replace('det-', 'chev-');
    const chev = document.getElementById(chevId);
    if (!det) return;
    const aberto = det.style.display !== 'none';
    det.style.display = aberto ? 'none' : 'block';
    if (chev) chev.style.transform = aberto ? 'rotate(0deg)' : 'rotate(180deg)';
}

function mostrarTodos() {
    document.querySelectorAll('.alerta-card').forEach(c => {
        if (filtroAtual === 'todos' || !filtroAtual || c.getAttribute('data-intencao') === filtroAtual) {
            c.style.display = '';
        }
    });
    const wrap = document.getElementById('ver-todos-wrap');
    if (wrap) wrap.style.display = 'none';
}
</script>

<style>
@media (max-width: 768px) {
    [style*="grid-template-columns: repeat(4"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 480px) {
    [style*="grid-template-columns: repeat(4"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

@endsection
