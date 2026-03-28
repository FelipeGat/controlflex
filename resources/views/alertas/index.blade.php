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
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(min(180px,100%), 1fr)); gap:12px; margin-bottom:20px;">
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

{{-- CSS do Alerta Principal --}}
<style>
    .alerta-principal {
        background: linear-gradient(135deg, var(--cor-10), var(--cor-08));
        border: 2px solid var(--cor);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .alerta-principal-icone {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: var(--cor);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .alerta-principal-icone i {
        font-size: 22px;
        color: #fff;
    }

    .alerta-principal-conteudo {
        flex: 1;
        min-width: 0;
    }

    .alerta-principal-badge {
        background: var(--cor);
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 99px;
        text-transform: uppercase;
        letter-spacing: .4px;
        display: inline-block;
    }

    .alerta-principal-badge-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }

    .alerta-principal-titulo {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
    }

    .alerta-principal-descricao {
        margin: 0 0 4px;
        font-size: 14px;
        color: #475569;
        line-height: 1.5;
    }

    .alerta-principal-impacto {
        margin: 0;
        font-size: 13px;
        color: var(--cor);
        font-weight: 700;
    }

    .alerta-principal-previsao {
        margin: 4px 0 0;
        font-size: 12px;
        color: #64748b;
        font-style: italic;
    }

    .alerta-principal-acao {
        background: var(--cor);
        color: #fff;
        padding: 10px 22px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
        white-space: nowrap;
        transition: opacity .2s;
    }

    .alerta-principal-acao:hover {
        opacity: 0.9;
    }

    /* Mobile: < 480px */
    @media (max-width: 480px) {
        .alerta-principal {
            padding: 16px;
            text-align: center;
            justify-content: center;
        }

        .alerta-principal-icone {
            width: 48px;
            height: 48px;
            margin: 0 auto;
        }

        .alerta-principal-icone i {
            font-size: 18px;
        }

        .alerta-principal-conteudo {
            width: 100%;
            margin-top: 8px;
        }

        .alerta-principal-badge-wrap {
            justify-content: center;
        }

        .alerta-principal-titulo {
            font-size: 16px !important;
        }

        .alerta-principal-descricao {
            font-size: 13px !important;
        }

        .alerta-principal-impacto {
            font-size: 12px !important;
        }

        .alerta-principal-previsao {
            font-size: 11px !important;
        }

        .alerta-principal-acao {
            width: 100%;
            justify-content: center;
            margin-top: 12px;
            padding: 12px 16px;
            font-size: 14px;
        }
    }
</style>

@if($alertaPrincipal)
@php $cp = $config[$alertaPrincipal['tipo']] ?? $config['critico']; @endphp
<div class="alerta-principal" style="--cor: {{ $cp['cor'] }}; --cor-10: {{ $cp['cor'] }}10; --cor-08: {{ $cp['cor'] }}08;">
    <div class="alerta-principal-icone">
        <i class="fa-solid {{ $alertaPrincipal['icone'] }}"></i>
    </div>
    <div class="alerta-principal-conteudo">
        <div class="alerta-principal-badge-wrap">
            <span class="alerta-principal-badge">{{ $cp['label'] }}</span>
        </div>
        <h3 class="alerta-principal-titulo">{{ $alertaPrincipal['titulo'] }}</h3>
        <p class="alerta-principal-descricao">{!! $alertaPrincipal['descricao'] !!}</p>
        <p class="alerta-principal-impacto">{{ $alertaPrincipal['impacto'] }}</p>
        @if($alertaPrincipal['previsao'])
        <p class="alerta-principal-previsao">
            <i class="fa-solid fa-forward" style="font-size:10px; margin-right:4px;"></i>{{ $alertaPrincipal['previsao'] }}
        </p>
        @endif
    </div>
    <a href="{{ $alertaPrincipal['acao_url'] }}" class="alerta-principal-acao">
        {{ $alertaPrincipal['acao_label'] }} <i class="fa-solid fa-arrow-right" style="font-size:11px;"></i>
    </a>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════ --}}
{{-- 3. FILTROS POR INTENÇÃO --}}
{{-- ═══════════════════════════════════════════════════════════════════ --}}
<div style="display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; justify-content:center;">
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

{{-- CSS dos cards de alerta --}}
<style>
    .alertas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(360px, 100%), 1fr));
        gap: 12px;
    }

    .alerta-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        transition: all .2s;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 180px;
    }

    .alerta-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .alerta-header {
        padding: 14px 16px;
        cursor: pointer;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        flex: 1;
    }

    .alerta-icone {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .alerta-conteudo {
        flex: 1;
        min-width: 0;
    }

    .alerta-badge {
        display: inline-block;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 99px;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: #fff;
    }

    .alerta-titulo {
        font-size: 13px;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .alerta-descricao {
        font-size: 12px;
        color: #64748b;
        margin: 0 0 6px;
        line-height: 1.5;
    }

    .alerta-impacto {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .alerta-impacto-texto {
        font-size: 11px;
        font-weight: 700;
    }

    .alerta-previsao {
        font-size: 11px;
        color: #94a3b8;
        font-style: italic;
    }

    .alerta-chevron {
        font-size: 10px;
        color: #94a3b8;
        flex-shrink: 0;
        margin-top: 4px;
        transition: .2s;
    }

    .alerta-chevron.rotacionado {
        transform: rotate(180deg);
    }

    .alerta-detalhes {
        display: none;
        padding: 0 16px 10px 64px;
        border-top: 1px solid;
        padding-top: 10px;
    }

    .alerta-detalhes ul {
        margin: 0;
        padding-left: 14px;
        color: #475569;
        font-size: 11px;
        line-height: 1.9;
    }

    .alerta-footer {
        padding: 8px 16px 14px;
        display: flex;
        justify-content: flex-end;
        margin-top: auto;
    }

    .alerta-acao {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #fff;
        padding: 6px 16px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: opacity .2s;
    }

    .alerta-acao:hover {
        opacity: 0.85;
    }

    .alerta-oculto {
        display: none;
    }
</style>

<div id="alertas-grid" class="alertas-grid">
    @foreach($alertas->slice(1) as $i => $alerta)
    @php
        $c = $config[$alerta['tipo']] ?? $config['info'];
        $temContas = !empty($alerta['contas_afetadas']);
        $hidden = $i >= 7 ? 'alerta-oculto' : '';
    @endphp

    <div class="alerta-card {{ $hidden }}" data-tipo="{{ $alerta['tipo'] }}" data-intencao="{{ $alerta['intencao'] }}" data-indice="{{ $i }}" style="border: 1px solid {{ $c['borda'] }}; border-left: 4px solid {{ $c['cor'] }};">

        {{-- Header --}}
        <div class="alerta-header" onclick="toggleAlerta('det-{{ $alerta['id'] }}')">
            <div class="alerta-icone" style="background:{{ $c['cor'] }}15;">
                <i class="fa-solid {{ $alerta['icone'] }}" style="font-size:14px; color:{{ $c['cor'] }};"></i>
            </div>
            <div class="alerta-conteudo">
                <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                    <span class="alerta-badge" style="background:{{ $c['cor'] }};">{{ $c['label'] }}</span>
                    <span class="alerta-titulo">{{ $alerta['titulo'] }}</span>
                </div>
                <p class="alerta-descricao">{!! $alerta['descricao'] !!}</p>
                <div class="alerta-impacto">
                    <span class="alerta-impacto-texto" style="color:{{ $c['cor'] }};">
                        <i class="fa-solid fa-circle-dollar-to-slot" style="font-size:10px; margin-right:2px;"></i>{{ $alerta['impacto'] }}
                    </span>
                    @if($alerta['previsao'])
                    <span class="alerta-previsao">
                        <i class="fa-solid fa-forward" style="font-size:9px; margin-right:2px;"></i>{{ $alerta['previsao'] }}
                    </span>
                    @endif
                </div>
            </div>
            <i id="chev-{{ $alerta['id'] }}" class="alerta-chevron fa-solid {{ $temContas ? 'fa-chevron-down' : 'fa-circle' }}" style="{{ !$temContas ? 'visibility:hidden;' : '' }}"></i>
        </div>

        {{-- Contas afetadas (expansível) --}}
        @if($temContas)
        <div id="det-{{ $alerta['id'] }}" class="alerta-detalhes" style="border-top-color: {{ $c['borda'] }};">
            <ul>
                @foreach($alerta['contas_afetadas'] as $item)
                <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Ação --}}
        <div class="alerta-footer">
            <a href="{{ $alerta['acao_url'] }}" class="alerta-acao" style="background:{{ $c['cor'] }};">
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
        c.classList.toggle('alerta-oculto', !match);
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
        const match = c.getAttribute('data-tipo') === tipo;
        c.classList.toggle('alerta-oculto', !match);
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
    if (chev) {
        chev.classList.toggle('rotacionado', !aberto);
    }
}

function mostrarTodos() {
    document.querySelectorAll('.alerta-card').forEach(c => {
        if (filtroAtual === 'todos' || !filtroAtual || c.getAttribute('data-intencao') === filtroAtual) {
            c.classList.remove('alerta-oculto');
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
