@extends('layouts.main')
@section('title', 'Lançamentos')
@section('page-title', 'Lançamentos')

@section('content')

{{-- ─── Alertas de validação ─────────────────────────────────────────────── --}}
@if($errors->any())
<div class="alert alert-danger mb-3">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif
@if(session('success'))
<div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif

{{-- ─── Filtros + Navegação de meses ────────────────────────────────────── --}}
@php
    $mesAtual     = \Carbon\Carbon::parse($inicio);
    $mesAnterior  = $mesAtual->copy()->subMonth();
    $mesProximo   = $mesAtual->copy()->addMonth();
    $mesNome      = $mesAtual->locale('pt_BR')->isoFormat('MMMM [de] YYYY');
    $ehMesAtual   = $mesAtual->format('Y-m') === now()->format('Y-m');

    $urlMesAnterior = route('lancamentos.index', array_merge(request()->except(['inicio','fim']), [
        'inicio' => $mesAnterior->startOfMonth()->format('Y-m-d'),
        'fim'    => $mesAnterior->copy()->endOfMonth()->format('Y-m-d'),
    ]));
    $urlMesProximo = route('lancamentos.index', array_merge(request()->except(['inicio','fim']), [
        'inicio' => $mesProximo->startOfMonth()->format('Y-m-d'),
        'fim'    => $mesProximo->copy()->endOfMonth()->format('Y-m-d'),
    ]));
    $urlMesAtualReal = route('lancamentos.index', array_merge(request()->except(['inicio','fim']), [
        'inicio' => now()->startOfMonth()->format('Y-m-d'),
        'fim'    => now()->endOfMonth()->format('Y-m-d'),
    ]));
@endphp

<div class="card" style="padding:14px 16px;margin-bottom:18px;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">

        {{-- Navegação de mês --}}
        <div style="display:flex;align-items:center;gap:0;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;flex-shrink:0;">
            <a href="{{ $urlMesAnterior }}" class="nav-mes-btn" title="Mês anterior">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div id="mes-label" style="padding:6px 14px;font-weight:700;font-size:13px;color:var(--color-text);min-width:0;text-align:center;white-space:nowrap;cursor:pointer;user-select:none;" onclick="toggleDatePicker()" title="Clique para escolher uma data">
                {{ ucfirst($mesNome) }}
            </div>
            <a href="{{ $urlMesProximo }}" class="nav-mes-btn" title="Próximo mês">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        @if(!$ehMesAtual)
        <a href="{{ $urlMesAtualReal }}" style="font-size:12px;color:var(--color-primary);text-decoration:none;font-weight:600;white-space:nowrap;" title="Voltar para o mês atual">
            <i class="fa-solid fa-rotate-left"></i> Mês Atual
        </a>
        @endif

        <div style="width:1px;height:28px;background:#e2e8f0;flex-shrink:0;" class="hide-mobile"></div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('lancamentos.index') }}" id="form-filtro" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;flex:1;">
            <input type="hidden" name="inicio" id="f-inicio" value="{{ $inicio }}">
            <input type="hidden" name="fim"    id="f-fim"    value="{{ $fim }}">

            {{-- Date picker oculto --}}
            <div id="date-picker-wrapper" style="display:none;position:absolute;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);padding:16px;z-index:200;margin-top:8px;left:0;right:0;max-width:360px;">
                <div style="font-size:12px;color:#64748b;margin-bottom:10px;font-weight:600;">Período personalizado</div>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:4px;">De</div>
                        <input type="date" id="dp-inicio" value="{{ $inicio }}" class="form-control" style="max-width:148px;">
                    </div>
                    <div>
                        <div style="font-size:11px;color:#94a3b8;margin-bottom:4px;">Até</div>
                        <input type="date" id="dp-fim" value="{{ $fim }}" class="form-control" style="max-width:148px;">
                    </div>
                    <button type="button" onclick="aplicarPeriodoCustom()" class="btn btn-primary btn-sm" style="margin-top:14px;">
                        <i class="fa-solid fa-check"></i> Aplicar
                    </button>
                </div>
            </div>

            <select name="banco_id" class="form-control" style="max-width:190px;min-width:0;font-size:13px;flex:1;" onchange="this.form.submit()">
                <option value="">Todas as contas</option>
                @foreach($bancos as $b)
                <option value="{{ $b->id }}" {{ $bancoId == $b->id ? 'selected' : '' }}>{{ $b->nome }}</option>
                @endforeach
            </select>

            <div style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                @foreach(['todos' => 'Todos', 'debito' => '↓ Saídas', 'credito' => '↑ Entradas'] as $val => $label)
                <button type="submit" name="tipo" value="{{ $val }}"
                    style="padding:6px 12px;font-size:12px;font-weight:600;border:none;cursor:pointer;white-space:nowrap;
                           background:{{ $tipo === $val ? 'var(--color-primary)' : '#fff' }};
                           color:{{ $tipo === $val ? '#fff' : '#64748b' }};
                           transition:background .15s;">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </form>
    </div>
</div>

<style>
.nav-mes-btn {
    display:flex;align-items:center;justify-content:center;
    width:34px;height:34px;
    color:#64748b;text-decoration:none;
    background:#fff;transition:background .15s;
}
.nav-mes-btn:hover { background:#f1f5f9;color:var(--color-primary); }
</style>

{{-- ─── Cards de resumo ──────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(120px,100%),1fr));gap:12px;margin-bottom:20px;">
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;"><i class="fa-solid fa-arrow-trend-up" style="color:#16a34a;"></i> Entradas</div>
        <div class="fw-700 kpi-value" style="color:#16a34a;">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;"><i class="fa-solid fa-arrow-trend-down" style="color:#dc2626;"></i> Saídas</div>
        <div class="fw-700 kpi-value" style="color:#dc2626;">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:11px;color:#64748b;margin-bottom:4px;"><i class="fa-solid fa-scale-balanced" style="color:#7c3aed;"></i> Saldo do Período</div>
        <div class="fw-700 kpi-value" style="color:{{ $saldoPeriodo >= 0 ? '#16a34a' : '#dc2626' }};">
            R$ {{ number_format($saldoPeriodo, 2, ',', '.') }}
        </div>
    </div>
</div>

{{-- ─── Botões de ação ──────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(150px,100%),1fr));gap:12px;margin-bottom:24px;">
    <button onclick="openModal('modal-manual')" class="btn-acao" style="border-color:var(--color-primary);background:#f5f3ff;" onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
        <div class="btn-acao-icon" style="background:var(--color-primary);"><i class="fa-solid fa-pen-to-square" style="color:#fff;font-size:18px;"></i></div>
        <div><div style="font-weight:700;font-size:13px;color:var(--color-text);">Lançamento Manual</div><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">Despesa ou receita</div></div>
    </button>
    <button onclick="document.getElementById('camera-input').click()" class="btn-acao" style="border-color:#16a34a;background:#f0fdf4;" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
        <div class="btn-acao-icon" style="background:#16a34a;"><i class="fa-solid fa-receipt" style="color:#fff;font-size:18px;"></i></div>
        <div><div style="font-weight:700;font-size:13px;color:var(--color-text);">Cupom / NF</div><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">Escanear ou foto</div></div>
    </button>
    <button onclick="openModal('modal-importar')" class="btn-acao" style="border-color:#0891b2;background:#ecfeff;" onmouseover="this.style.background='#cffafe'" onmouseout="this.style.background='#ecfeff'">
        <div class="btn-acao-icon" style="background:#0891b2;"><i class="fa-solid fa-file-arrow-up" style="color:#fff;font-size:18px;"></i></div>
        <div><div style="font-weight:700;font-size:13px;color:var(--color-text);">Importar Extrato</div><div style="font-size:10px;color:var(--color-text-muted);margin-top:2px;">OFX / CSV</div></div>
    </button>
</div>

<input type="file" id="camera-input" accept="image/*" capture="environment" style="display:none;">

<style>
.btn-acao { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:20px 12px;border-radius:12px;border:2px dashed;cursor:pointer;transition:background .15s; }
.btn-acao-icon { width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center; }
</style>

{{-- ─── Tabela de Extrato ────────────────────────────────────────────────── --}}
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div class="card-title" style="margin:0;">
            <i class="fa-solid fa-list-ul"></i> Extrato
            @if($bancoBuscado) — <span style="color:var(--color-primary);">{{ $bancoBuscado->nome }}</span>@endif
        </div>
        <span style="font-size:12px;color:#64748b;">{{ count($movimentacoes) }} lançamento(s)</span>
    </div>

    @if(count($movimentacoes) > 0)
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:90px;">Data</th>
                    <th>Descrição</th>
                    <th class="hide-mobile">Categoria</th>
                    <th class="hide-mobile">Conta</th>
                    <th style="text-align:right;">Saída</th>
                    <th style="text-align:right;">Entrada</th>
                    <th class="hide-mobile">Status</th>
                    <th style="width:36px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimentacoes as $mov)
                <tr style="border-left:3px solid {{ $mov['tipo'] === 'credito' ? '#16a34a' : '#dc2626' }};">
                    <td style="white-space:nowrap;font-size:12px;color:#64748b;">
                        {{ $mov['data_fmt'] }}
                    </td>
                    <td>
                        <div class="fw-600" style="font-size:13px;">
                            {{ Str::limit($mov['descricao'], 55) }}
                        </div>
                        <div style="display:flex;gap:6px;margin-top:2px;flex-wrap:wrap;">
                            @if($mov['recorrente'])
                            <span style="font-size:10px;color:#7c3aed;"><i class="fa-solid fa-rotate"></i> Recorrente</span>
                            @endif
                            @if($mov['origem'] === 'qr_ocr' || $mov['origem'] === 'ocr')
                            <span style="font-size:10px;color:#0891b2;"><i class="fa-solid fa-camera"></i> Cupom</span>
                            @elseif($mov['origem'] === 'importacao_extrato')
                            <span style="font-size:10px;color:#0891b2;"><i class="fa-solid fa-file-import"></i> Importado</span>
                            @endif
                            @if($mov['numero_doc'])
                            <span style="font-size:10px;color:#94a3b8;">Nº {{ $mov['numero_doc'] }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="hide-mobile">
                        @if($mov['categoria'])
                        <span class="badge badge-slate"><i class="fa-solid {{ $mov['categoria_icone'] }}"></i> {{ $mov['categoria'] }}</span>
                        @else <span class="text-subtle">—</span>
                        @endif
                    </td>
                    <td class="hide-mobile">
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $mov['conta_cor'] }};display:inline-block;"></span>
                            {{ $mov['conta'] }}
                        </span>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        @if($mov['tipo'] === 'debito')
                        <span class="fw-700" style="color:#dc2626;">- R$ {{ number_format($mov['valor'], 2, ',', '.') }}</span>
                        @else <span class="text-subtle">—</span>
                        @endif
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        @if($mov['tipo'] === 'credito')
                        <span class="fw-700" style="color:#16a34a;">+ R$ {{ number_format($mov['valor'], 2, ',', '.') }}</span>
                        @else <span class="text-subtle">—</span>
                        @endif
                    </td>
                    <td class="hide-mobile">
                        @php $st = $mov['status']; @endphp
                        @if($st === 'pago' || $st === 'recebido')
                            <span class="badge badge-green"><i class="fa-solid fa-check"></i> {{ $mov['tipo'] === 'credito' ? 'Recebido' : 'Pago' }}</span>
                        @elseif($st === 'vencido')
                            <span class="badge badge-red"><i class="fa-solid fa-triangle-exclamation"></i> Vencido</span>
                        @else
                            <span class="badge badge-amber">{{ $mov['tipo'] === 'credito' ? 'A Receber' : 'A Pagar' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($mov['model'] === 'despesa')
                        <form method="POST" action="{{ route('despesas.destroy', $mov['id']) }}" onsubmit="return confirm('Excluir este lançamento?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red" title="Excluir"><i class="fa-solid fa-trash" style="font-size:11px;"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;">
                    <td colspan="4" class="fw-600" style="font-size:12px;color:#64748b;">Total do período</td>
                    <td style="text-align:right;" class="fw-700" style="color:#dc2626;">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</td>
                    <td style="text-align:right;" class="fw-700" style="color:#16a34a;">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="empty-state" style="text-align:center;padding:40px;">
        <i class="fa-solid fa-receipt" style="font-size:36px;color:var(--color-text-subtle);margin-bottom:12px;display:block;"></i>
        <p style="color:var(--color-text-muted);font-size:13px;">Nenhum lançamento encontrado para o período.<br>Use os botões acima para registrar movimentações.</p>
    </div>
    @endif
</div>

{{-- ─── Loading overlay ────────────────────────────────────────────────── --}}
<div id="scan-loading" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;flex-direction:column;gap:14px;">
    <div style="width:56px;height:56px;border:4px solid rgba(255,255,255,.25);border-top-color:#fff;border-radius:50%;animation:ld-spin 0.7s linear infinite;"></div>
    <div id="scan-loading-msg" style="color:#fff;font-weight:600;font-size:15px;text-align:center;max-width:260px;line-height:1.4;"></div>
</div>
<div id="scan-toast" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:11px 22px;border-radius:8px;font-size:13px;z-index:600;box-shadow:0 4px 16px rgba(0,0,0,.35);max-width:340px;text-align:center;"></div>
<style>@keyframes ld-spin { to { transform: rotate(360deg); } }</style>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 1: Lançamento Manual                                             --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="modal-manual">
    <div class="modal" style="max-width:580px;">
        <div class="modal-header">
            <i class="fa-solid fa-pen-to-square" style="color:var(--color-primary);"></i>
            <h3>Lançamento Manual</h3>
            <button class="modal-close" onclick="closeModal('modal-manual')">&times;</button>
        </div>
        <div class="modal-body">

            {{-- Tabs Despesa / Receita --}}
            <div style="display:flex;border-bottom:2px solid #e2e8f0;margin-bottom:18px;">
                <button type="button" id="tab-despesa" onclick="alternarTipo('despesa')"
                    style="padding:8px 20px;font-weight:600;font-size:13px;border:none;background:none;cursor:pointer;border-bottom:2px solid var(--color-primary);color:var(--color-primary);margin-bottom:-2px;">
                    <i class="fa-solid fa-arrow-trend-down"></i> Saída / Despesa
                </button>
                <button type="button" id="tab-receita" onclick="alternarTipo('receita')"
                    style="padding:8px 20px;font-weight:600;font-size:13px;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;color:#94a3b8;margin-bottom:-2px;">
                    <i class="fa-solid fa-arrow-trend-up"></i> Entrada / Receita
                </button>
            </div>

            {{-- Formulário Despesa --}}
            <form method="POST" action="{{ route('despesas.store') }}" id="form-despesa">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Valor (R$) *</label><input type="number" name="valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00" autofocus></div>
                    <div class="form-group"><label class="form-label">Data *</label><input type="date" name="data_compra" class="form-control" required value="{{ now()->format('Y-m-d') }}"></div>
                    <div class="form-group"><label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-control"><option value="">— Selecione —</option>@foreach($categorias as $cat)<option value="{{ $cat->id }}">{{ $cat->nome }}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label class="form-label">Estabelecimento</label>
                        <select name="onde_comprou" class="form-control"><option value="">— Selecione —</option>@foreach($fornecedores as $f)<option value="{{ $f->id }}">{{ $f->nome }}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label class="form-label">Quem Comprou</label>
                        <select name="quem_comprou" class="form-control"><option value="">— Selecione —</option>@foreach($familiares as $fam)<option value="{{ $fam->id }}" {{ $fam->id == $meuFamiliarId ? 'selected' : '' }}>{{ $fam->nome }}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label class="form-label">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-control" id="manual-banco">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)<option value="{{ $b->id }}" data-nome="{{ strtolower($b->nome) }}" {{ $bancoId == $b->id ? 'selected' : '' }}>{{ $b->nome }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="120" placeholder="Descrição do lançamento..."></textarea>
                    </div>
                </div>
                <div style="text-align:right;margin-top:16px;">
                    <button type="button" onclick="closeModal('modal-manual')" class="btn btn-secondary" style="margin-right:8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Salvar Despesa</button>
                </div>
            </form>

            {{-- Formulário Receita --}}
            <form method="POST" action="{{ route('receitas.store') }}" id="form-receita" style="display:none;">
                @csrf
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Valor (R$) *</label><input type="number" name="valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00"></div>
                    <div class="form-group"><label class="form-label">Data Prevista *</label><input type="date" name="data_prevista_recebimento" class="form-control" required value="{{ now()->format('Y-m-d') }}"></div>
                    <div class="form-group"><label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach(App\Models\Categoria::where('tipo','RECEITA')->orderBy('nome')->get() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Quem Recebeu</label>
                        <select name="quem_recebeu" class="form-control"><option value="">— Selecione —</option>@foreach($familiares as $fam)<option value="{{ $fam->id }}" {{ $fam->id == $meuFamiliarId ? 'selected' : '' }}>{{ $fam->nome }}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label class="form-label">Conta de Destino</label>
                        <select name="forma_recebimento" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)<option value="{{ $b->id }}" {{ $bancoId == $b->id ? 'selected' : '' }}>{{ $b->nome }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" maxlength="120" placeholder="Descrição..."></textarea>
                    </div>
                </div>
                <div style="text-align:right;margin-top:16px;">
                    <button type="button" onclick="closeModal('modal-manual')" class="btn btn-secondary" style="margin-right:8px;">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Salvar Receita</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 2: Confirmação Cupom / NF                                        --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="modal-cupom">
    <div class="modal" style="max-width:580px;">
        <div class="modal-header">
            <i class="fa-solid fa-receipt" style="color:#16a34a;"></i>
            <h3>Confirmar Lançamento — Cupom/NF</h3>
            <button class="modal-close" onclick="closeModal('modal-cupom')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="scan-info-box" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;"></div>
            <form method="POST" action="{{ route('despesas.store') }}" id="form-cupom">
                @csrf
                <input type="hidden" name="origem" value="ocr">
                <input type="hidden" name="numero_documento" id="scan-numero-documento">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Valor (R$) *</label><input type="number" name="valor" id="scan-valor" step="0.01" min="0.01" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Data *</label><input type="date" name="data_compra" id="scan-data" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-control"><option value="">— Selecione —</option>@foreach($categorias as $cat)<option value="{{ $cat->id }}">{{ $cat->nome }}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label class="form-label">Estabelecimento</label>
                        <select name="onde_comprou" id="scan-fornecedor" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($fornecedores as $f)<option value="{{ $f->id }}" data-cnpj="{{ $f->cnpj ?? '' }}" data-nome="{{ strtolower($f->nome) }}">{{ $f->nome }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Quem Comprou</label>
                        <select name="quem_comprou" class="form-control"><option value="">— Selecione —</option>@foreach($familiares as $fam)<option value="{{ $fam->id }}" {{ $fam->id == $meuFamiliarId ? 'selected' : '' }}>{{ $fam->nome }}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label class="form-label">Forma de Pagamento</label>
                        <select name="forma_pagamento" id="scan-banco" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)<option value="{{ $b->id }}" data-nome="{{ strtolower($b->nome) }}">{{ $b->nome }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:span 2;"><label class="form-label">Observações</label>
                        <textarea name="observacoes" id="scan-obs" class="form-control" rows="2" maxlength="120"></textarea>
                    </div>
                </div>
                <div style="text-align:right;margin-top:16px;">
                    <button type="button" onclick="closeModal('modal-cupom')" class="btn btn-secondary" style="margin-right:8px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Confirmar Lançamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL 3: Importar Extrato OFX / CSV                                    --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="modal-importar">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <i class="fa-solid fa-file-arrow-up" style="color:#0891b2;"></i>
            <h3>Importar Extrato Bancário</h3>
            <button class="modal-close" onclick="fecharImportacao()">&times;</button>
        </div>
        <div class="modal-body">

            {{-- Passo 1: Upload --}}
            <div id="import-step-1">
                <p style="font-size:13px;color:#64748b;margin-bottom:16px;">
                    Selecione o banco de destino e faça upload do arquivo exportado pelo seu internet banking.<br>
                    <strong>Formatos suportados:</strong> OFX, OFC, QFX (Open Financial Exchange) e CSV.
                </p>
                <div class="form-grid" style="margin-bottom:16px;">
                    <div class="form-group">
                        <label class="form-label">Conta / Banco *</label>
                        <select id="import-banco-id" class="form-control">
                            <option value="">— Selecione a conta —</option>
                            @foreach($bancos as $b)<option value="{{ $b->id }}">{{ $b->nome }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Arquivo do Extrato *</label>
                        <input type="file" id="import-arquivo" class="form-control" accept=".ofx,.ofc,.qfx,.csv,.txt">
                    </div>
                </div>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;font-size:11px;color:#1d4ed8;margin-bottom:16px;">
                    <strong><i class="fa-solid fa-circle-info"></i> Como exportar o extrato:</strong><br>
                    • <strong>Bradesco / BB:</strong> Internet Banking → Extrato → Exportar OFX<br>
                    • <strong>Itaú:</strong> Itaú Online → Extrato → Baixar arquivo OFX<br>
                    • <strong>Nubank:</strong> App → Configurações → Exportar extrato (CSV)<br>
                    • <strong>Caixa:</strong> Internet Banking → Extrato → Exportar
                </div>
                <div style="text-align:right;">
                    <button type="button" onclick="fecharImportacao()" class="btn btn-secondary" style="margin-right:8px;">Cancelar</button>
                    <button type="button" onclick="processarArquivo()" class="btn" style="background:#0891b2;color:#fff;"><i class="fa-solid fa-magnifying-glass"></i> Analisar Arquivo</button>
                </div>
            </div>

            {{-- Passo 2: Preview das transações --}}
            <div id="import-step-2" style="display:none;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div>
                        <div class="fw-600" style="font-size:14px;" id="import-resumo-texto"></div>
                        <div style="font-size:11px;color:#64748b;margin-top:2px;">Marque as transações que deseja importar</div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="button" onclick="selecionarTodos(true)" class="btn btn-secondary btn-sm">Selecionar Todos</button>
                        <button type="button" onclick="selecionarTodos(false)" class="btn btn-secondary btn-sm">Desmarcar Todos</button>
                    </div>
                </div>
                <div style="max-height:320px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
                    <table class="table" style="margin:0;font-size:12px;">
                        <thead style="position:sticky;top:0;background:#f8fafc;z-index:1;">
                            <tr>
                                <th style="width:36px;"><input type="checkbox" id="check-all" onchange="selecionarTodos(this.checked)"></th>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th style="text-align:right;">Valor</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody id="import-tbody"></tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('lancamentos.confirmar-importacao') }}" id="form-importacao">
                    @csrf
                    <input type="hidden" name="banco_id" id="import-banco-id-hidden">
                    <div id="import-campos-hidden"></div>
                    <div style="text-align:right;margin-top:16px;">
                        <button type="button" onclick="voltarImportacao()" class="btn btn-secondary" style="margin-right:8px;"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
                        <button type="submit" class="btn" style="background:#0891b2;color:#fff;" id="btn-confirmar-importacao">
                            <i class="fa-solid fa-file-import"></i> Importar Selecionados
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- JAVASCRIPT                                                             --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>

// ── Tabs Manual (Despesa / Receita) ──────────────────────────────────────────
function alternarTipo(tipo) {
    const tabD = document.getElementById('tab-despesa');
    const tabR = document.getElementById('tab-receita');
    const formD = document.getElementById('form-despesa');
    const formR = document.getElementById('form-receita');
    const ativo = 'border-bottom:2px solid var(--color-primary);color:var(--color-primary);margin-bottom:-2px;';
    const inativo = 'border-bottom:2px solid transparent;color:#94a3b8;margin-bottom:-2px;';
    tabD.style.cssText += tipo === 'despesa' ? ativo : inativo;
    tabR.style.cssText += tipo === 'receita' ? ativo : inativo;
    formD.style.display = tipo === 'despesa' ? '' : 'none';
    formR.style.display = tipo === 'receita' ? '' : 'none';
}

// ── Scanner de Cupom ─────────────────────────────────────────────────────────
document.getElementById('camera-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    mostrarLoading('Processando imagem...');

    const reader = new FileReader();
    reader.onload = function(ev) {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const max = 1600;
            let w = img.width, h = img.height;
            if (w > max || h > max) { if (w > h) { h = h * max / w; w = max; } else { w = w * max / h; h = max; } }
            canvas.width = w; canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);

            // Tenta QR Code
            const imageData = ctx.getImageData(0, 0, w, h);
            const qr = jsQR(imageData.data, w, h);

            if (qr) {
                atualizarLoading('QR Code detectado! Consultando...');
                enviarParaServidor({ qr_code: qr.data });
            } else {
                atualizarLoading('Lendo cupom com IA...');
                const b64 = canvas.toDataURL('image/jpeg', 0.85).split(',')[1];
                enviarParaServidor({ imagem: b64, mime: 'image/jpeg' });
            }
        };
        img.src = ev.target.result;
    };
    reader.readAsDataURL(file);
    e.target.value = '';
});

function enviarParaServidor(payload) {
    fetch('{{ route("lancamentos.escanear") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(d => {
        esconderLoading();
        if (d.erro) { mostrarToast(d.erro, 7000); return; }
        preencherModalCupom(d);
    })
    .catch(() => { esconderLoading(); mostrarToast('Erro de comunicação. Tente novamente.', 5000); });
}

function preencherModalCupom(d) {
    document.getElementById('scan-valor').value = d.valor || '';
    document.getElementById('scan-data').value  = d.data  || '{{ now()->format("Y-m-d") }}';
    document.getElementById('scan-obs').value   = d.descricao || '';
    document.getElementById('scan-numero-documento').value = d.numero_cupom || '';

    const infoBox = document.getElementById('scan-info-box');
    let html = '<div style="font-weight:700;color:#15803d;margin-bottom:6px;"><i class="fa-solid fa-circle-check"></i> Dados detectados automaticamente</div>';
    if (d.estabelecimento) html += `<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;margin-right:6px;">${d.estabelecimento}</span>`;
    if (d.cnpj)            html += `<span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:12px;margin-right:6px;">CNPJ: ${d.cnpj}</span>`;
    if (d.numero_cupom)    html += `<span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:12px;margin-right:6px;">Nº ${d.numero_cupom}</span>`;
    if (d.forma_pagamento) html += `<span style="background:#ede9fe;color:#5b21b6;padding:2px 8px;border-radius:12px;">${d.forma_pagamento}</span>`;
    if (d.itens?.length)   html += `<div style="margin-top:8px;border-top:1px solid #bbf7d0;padding-top:8px;"><strong>Itens:</strong> ${d.itens.slice(0,5).join(', ')}</div>`;
    infoBox.innerHTML = html;
    infoBox.style.display = '';

    // Auto-seleciona banco e fornecedor
    if (d.forma_pagamento) autoSelecionarBanco(d.forma_pagamento);
    if (d.cnpj || d.estabelecimento) autoSelecionarFornecedor(d.cnpj, d.estabelecimento);

    openModal('modal-cupom');
}

function autoSelecionarBanco(texto) {
    const lower = texto.toLowerCase();
    const sel   = document.getElementById('scan-banco');
    const mapa  = { 'dinheiro': 'carteira', 'espécie': 'carteira', 'especie': 'carteira', 'pix': 'pix', 'crédito': 'crédito', 'credito': 'crédito', 'débito': 'débito', 'debito': 'débito' };
    for (const [k, v] of Object.entries(mapa)) {
        if (lower.includes(k)) {
            for (const opt of sel.options) { if (opt.dataset.nome?.includes(v)) { sel.value = opt.value; return; } }
        }
    }
}

function autoSelecionarFornecedor(cnpj, nome) {
    const sel = document.getElementById('scan-fornecedor');
    const cnpjLimpo = (cnpj || '').replace(/\D/g, '');
    const nomeLower = (nome || '').toLowerCase().slice(0, 5);
    for (const opt of sel.options) {
        if (cnpjLimpo && opt.dataset.cnpj?.replace(/\D/g,'') === cnpjLimpo) { sel.value = opt.value; return; }
        if (nomeLower && opt.dataset.nome?.startsWith(nomeLower)) { sel.value = opt.value; return; }
    }
}

// ── Importação de Extrato ────────────────────────────────────────────────────
let transacoesImportadas = [];

function processarArquivo() {
    const bancoId  = document.getElementById('import-banco-id').value;
    const arquivo  = document.getElementById('import-arquivo').files[0];
    if (!bancoId)  { mostrarToast('Selecione o banco/conta.', 4000); return; }
    if (!arquivo)  { mostrarToast('Selecione um arquivo.', 4000); return; }

    mostrarLoading('Analisando arquivo...');
    const fd = new FormData();
    fd.append('arquivo', arquivo);
    fd.append('banco_id', bancoId);
    fd.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("lancamentos.importar-extrato") }}', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        esconderLoading();
        if (d.erro) { mostrarToast(d.erro, 6000); return; }
        transacoesImportadas = d.transacoes;
        document.getElementById('import-banco-id-hidden').value = bancoId;
        renderizarPreview(d.transacoes, d.total);
    })
    .catch(() => { esconderLoading(); mostrarToast('Erro ao processar arquivo.', 5000); });
}

function renderizarPreview(lista, total) {
    document.getElementById('import-resumo-texto').textContent = `${total} transações encontradas`;
    const tbody = document.getElementById('import-tbody');
    tbody.innerHTML = '';

    lista.forEach((t, i) => {
        const cor  = t.tipo === 'credito' ? '#16a34a' : '#dc2626';
        const sinal = t.tipo === 'credito' ? '+' : '-';
        const tipoLabel = t.tipo === 'credito' ? '<span style="color:#16a34a;font-weight:600;">Entrada</span>' : '<span style="color:#dc2626;font-weight:600;">Saída</span>';
        tbody.innerHTML += `
        <tr>
            <td><input type="checkbox" name="check_${i}" checked data-idx="${i}"></td>
            <td style="white-space:nowrap;">${t.data ? t.data.split('-').reverse().join('/') : '—'}</td>
            <td style="max-width:260px;word-break:break-word;">${t.descricao || '—'}</td>
            <td style="text-align:right;color:${cor};font-weight:700;">${sinal} R$ ${parseFloat(t.valor).toFixed(2).replace('.',',')}</td>
            <td>${tipoLabel}</td>
        </tr>`;
    });

    document.getElementById('import-step-1').style.display = 'none';
    document.getElementById('import-step-2').style.display = '';
}

function selecionarTodos(val) {
    document.querySelectorAll('#import-tbody input[type=checkbox]').forEach(cb => cb.checked = val);
    const ca = document.getElementById('check-all');
    if (ca) ca.checked = val;
}

document.getElementById('form-importacao').addEventListener('submit', function() {
    const container = document.getElementById('import-campos-hidden');
    container.innerHTML = '';
    const checkboxes = document.querySelectorAll('#import-tbody input[type=checkbox]');
    checkboxes.forEach(cb => {
        const idx = parseInt(cb.dataset.idx);
        const t   = transacoesImportadas[idx];
        if (!t || !cb.checked) return;
        const add = (name, val) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = val ?? '';
            container.appendChild(inp);
        };
        add(`transacoes[${idx}][data]`,      t.data);
        add(`transacoes[${idx}][valor]`,     t.valor);
        add(`transacoes[${idx}][tipo]`,      t.tipo);
        add(`transacoes[${idx}][descricao]`, t.descricao);
        add(`transacoes[${idx}][fitid]`,     t.fitid);
        add(`transacoes[${idx}][importar]`,  '1');
    });
});

function voltarImportacao() {
    document.getElementById('import-step-1').style.display = '';
    document.getElementById('import-step-2').style.display = 'none';
}

function fecharImportacao() {
    voltarImportacao();
    closeModal('modal-importar');
}

// ── Date Picker Customizado ──────────────────────────────────────────────────
function toggleDatePicker() {
    const wrapper = document.getElementById('date-picker-wrapper');
    const label   = document.getElementById('mes-label');
    const rect    = label.getBoundingClientRect();
    wrapper.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
    wrapper.style.left = (rect.left  + window.scrollX)      + 'px';
    wrapper.style.display = wrapper.style.display === 'none' ? 'block' : 'none';
}

function aplicarPeriodoCustom() {
    const inicio = document.getElementById('dp-inicio').value;
    const fim    = document.getElementById('dp-fim').value;
    if (!inicio || !fim) { mostrarToast('Selecione as duas datas.', 3000); return; }
    document.getElementById('f-inicio').value = inicio;
    document.getElementById('f-fim').value    = fim;
    document.getElementById('date-picker-wrapper').style.display = 'none';
    document.getElementById('form-filtro').submit();
}

// Fecha date picker ao clicar fora
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('date-picker-wrapper');
    const label   = document.getElementById('mes-label');
    if (!wrapper.contains(e.target) && e.target !== label && !label.contains(e.target)) {
        wrapper.style.display = 'none';
    }
});

function mostrarLoading(msg) {
    document.getElementById('scan-loading-msg').textContent = msg;
    document.getElementById('scan-loading').style.display = 'flex';
}
function atualizarLoading(msg) { document.getElementById('scan-loading-msg').textContent = msg; }
function esconderLoading()     { document.getElementById('scan-loading').style.display = 'none'; }

function mostrarToast(msg, ms = 5000) {
    const t = document.getElementById('scan-toast');
    t.textContent = msg; t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, ms);
}
</script>

@endsection
