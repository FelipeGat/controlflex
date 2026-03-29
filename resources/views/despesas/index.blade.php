@extends('layouts.main')
@section('title', 'Despesas')
@section('page-title', 'Despesas')

@section('content')

{{-- ─── Filtros ─────────────────────────────────────────────────────────── --}}
@php
    $dtD         = \Carbon\Carbon::parse($inicio);
    $dtDAnt      = $dtD->copy()->subMonth();
    $dtDProx     = $dtD->copy()->addMonth();
    $mesNomeD    = $dtD->locale('pt_BR')->isoFormat('MMMM [de] YYYY');
    $ehMesAtualD = $dtD->format('Y-m') === now()->format('Y-m');
    $ehHojeD     = $inicio === now()->format('Y-m-d') && $fim === now()->format('Y-m-d');

    $urlDMesAnt  = route('despesas.index', array_merge(request()->except(['inicio','fim']), ['inicio' => $dtDAnt->startOfMonth()->format('Y-m-d'), 'fim' => $dtDAnt->copy()->endOfMonth()->format('Y-m-d')]));
    $urlDMesProx = route('despesas.index', array_merge(request()->except(['inicio','fim']), ['inicio' => $dtDProx->startOfMonth()->format('Y-m-d'), 'fim' => $dtDProx->copy()->endOfMonth()->format('Y-m-d')]));
    $urlDHoje    = route('despesas.index', array_filter(['inicio' => now()->format('Y-m-d'), 'fim' => now()->format('Y-m-d'), 'familiar_id' => $familiarId]));
    $urlDMesAtu  = route('despesas.index', array_filter(['familiar_id' => $familiarId]));
    $urlDTodas   = route('despesas.index', array_filter(['inicio' => $inicio, 'fim' => $fim]));
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;flex-wrap:wrap;">
    <div>{{-- espaço --}}</div>
    <button class="btn btn-danger" onclick="openModal('modal-nova-despesa')">
        <i class="fa-solid fa-plus"></i> Nova Despesa
    </button>
</div>

<div class="card filtros-bar">
    <div class="filtros-lanc">

        {{-- Mês --}}
        <div class="filtro-grupo filtro-grupo-centro">
            <div style="display:flex;align-items:center;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <a href="{{ $urlDMesAnt }}" class="nav-mes-btn" title="Mês anterior"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>
                <span class="mes-label-btn">{{ $ehHojeD ? 'Hoje' : ucfirst($mesNomeD) }}</span>
                <a href="{{ $urlDMesProx }}" class="nav-mes-btn" title="Próximo mês"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>
            </div>
            <div style="display:flex;gap:6px;">
                <a href="{{ $urlDHoje }}"
                   style="padding:6px 11px;font-size:12px;font-weight:600;border-radius:6px;text-decoration:none;white-space:nowrap;
                          border:1px solid {{ $ehHojeD ? 'var(--color-primary)' : '#e2e8f0' }};
                          background:{{ $ehHojeD ? 'var(--color-primary)' : '#fff' }};
                          color:{{ $ehHojeD ? '#fff' : '#64748b' }};">
                    <i class="fa-solid fa-calendar-day"></i> Hoje
                </a>
                @if(!$ehMesAtualD && !$ehHojeD)
                <a href="{{ $urlDMesAtu }}"
                   style="padding:6px 11px;font-size:12px;font-weight:600;border-radius:6px;text-decoration:none;white-space:nowrap;border:1px solid #e2e8f0;background:#fff;color:#64748b;">
                    <i class="fa-solid fa-rotate-left"></i> Mês Atual
                </a>
                @endif
            </div>
        </div>

        {{-- Membros --}}
        @if($familiares->isNotEmpty())
        <div class="filtro-grupo filtro-grupo-members">
            <div class="av-grupo">
                <a href="{{ $urlDTodas }}" class="av-item" title="Todos da Casa">
                    <div class="av-circulo" style="border:3px solid {{ !$familiarId ? 'var(--color-primary)' : 'transparent' }};outline:{{ !$familiarId ? 'none' : '2px solid #e2e8f0' }};background:{{ !$familiarId ? 'var(--color-primary)' : '#f1f5f9' }};box-shadow:{{ !$familiarId ? '0 0 0 2px var(--color-primary)44' : 'none' }};">
                        <i class="fa-solid fa-house" style="font-size:13px;color:{{ !$familiarId ? '#fff' : '#64748b' }};"></i>
                    </div>
                    <span class="av-nome" style="color:{{ !$familiarId ? 'var(--color-primary)' : '#94a3b8' }};font-weight:{{ !$familiarId ? '700' : '400' }};">Todos</span>
                </a>
                @foreach($familiares as $fam)
                @php
                    $dSel  = $familiarId === $fam->id;
                    $dIni  = implode('', array_map(fn($p) => strtoupper(substr($p,0,1)), array_slice(explode(' ',$fam->nome),0,2)));
                    $dCors = ['#6366f1','#0ea5e9','#16a34a','#f59e0b','#ef4444','#8b5cf6','#14b8a6'];
                    $dCor  = $dCors[$fam->id % count($dCors)];
                    $dUrl  = $dSel
                        ? route('despesas.index', array_filter(['inicio'=>$inicio,'fim'=>$fim]))
                        : route('despesas.index', array_filter(['inicio'=>$inicio,'fim'=>$fim,'familiar_id'=>$fam->id]));
                @endphp
                <a href="{{ $dUrl }}" class="av-item" title="{{ $fam->nome }}">
                    <div class="av-circulo" style="border:3px solid {{ $dSel ? $dCor : 'transparent' }};outline:{{ $dSel ? 'none' : '2px solid #e2e8f0' }};box-shadow:{{ $dSel ? '0 0 0 2px '.$dCor.'44' : 'none' }};">
                        @if($fam->foto)
                            <img src="{{ Storage::url($fam->foto) }}" alt="{{ $fam->nome }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div style="width:100%;height:100%;background:{{ $dCor }};color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;border-radius:50%;">{{ $dIni }}</div>
                        @endif
                    </div>
                    <span class="av-nome" style="color:{{ $dSel ? $dCor : '#94a3b8' }};font-weight:{{ $dSel ? '700' : '400' }};">{{ explode(' ',$fam->nome)[0] }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

<div class="card">
    <div class="d-flex justify-between align-center mb-4 flex-wrap gap-2">
        <div style="font-size:13px;" class="text-muted">
            <i class="fa-solid fa-arrow-trend-down text-red"></i>
            <strong class="fw-600" style="color:var(--color-text);">{{ $despesas->total() }}</strong> despesa(s) ·
            {{ \Carbon\Carbon::parse($inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($fim)->format('d/m/Y') }}
        </div>
        <div class="fw-700 text-red" style="font-size:15px;">
            R$ {{ number_format($totalValor, 2, ',', '.') }}
        </div>
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th class="hide-mobile">Categoria</th>
                    <th class="hide-mobile">Quem</th>
                    <th class="hide-mobile">Onde</th>
                    <th class="hide-mobile">Conta / Forma</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($despesas as $despesa)
                    <tr>
                        <td style="white-space:nowrap;">{{ $despesa->data_compra->format('d/m/Y') }}</td>
                        <td style="white-space:nowrap;">
                            <strong class="text-red">R$ {{ number_format($despesa->valor, 2, ',', '.') }}</strong>
                            @if($despesa->recorrente && $despesa->parcelas > 1)
                                <br><span class="text-subtle" style="font-size:11px;">Parcela {{ $despesa->grupo_recorrencia_id ? '' : '1/' }}{{ $despesa->parcelas }}</span>
                            @endif
                        </td>
                        <td class="hide-mobile">
                            @if($despesa->categoria)
                                <span class="badge badge-blue">{{ $despesa->categoria->nome }}</span>
                            @else
                                <span class="text-subtle">—</span>
                            @endif
                        </td>
                        <td class="hide-mobile">{{ $despesa->familiar?->nome ?? '—' }}</td>
                        <td class="hide-mobile">{{ $despesa->fornecedor?->nome ?? '—' }}</td>
                        <td class="hide-mobile">
                            {{ $despesa->banco?->nome ?? '—' }}
                            @if($despesa->tipo_pagamento)
                                <br>
                                @php
                                    $iconesTipo = [
                                        'dinheiro'     => ['fa-money-bill-wave', 'badge-green',  'Dinheiro'],
                                        'pix'          => ['fa-bolt',            'badge-teal',   'Pix'],
                                        'debito'       => ['fa-credit-card',     'badge-blue',   'Débito'],
                                        'credito'      => ['fa-credit-card',     'badge-amber',  'Crédito'],
                                        'transferencia'=> ['fa-arrow-right-arrow-left', 'badge-slate', 'Transf.'],
                                    ];
                                    $t = $iconesTipo[$despesa->tipo_pagamento] ?? ['fa-circle', 'badge-slate', $despesa->tipo_pagamento];
                                @endphp
                                <span class="badge {{ $t[1] }}" style="font-size:10px;">
                                    <i class="fa-solid {{ $t[0] }}"></i> {{ $t[2] }}
                                </span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            @if($despesa->status === 'pago')
                                <span class="badge badge-green"><i class="fa-solid fa-check"></i> Pago</span>
                            @elseif($despesa->status === 'vencido')
                                <span class="badge badge-red"><i class="fa-solid fa-triangle-exclamation"></i> Vencido</span>
                            @else
                                <span class="badge badge-amber">A Pagar</span>
                            @endif
                            @if($despesa->recorrente)
                                <span class="badge badge-slate" title="{{ $despesa->frequencia }}"><i class="fa-solid fa-rotate"></i></span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button onclick="editarDespesa({{ $despesa->id }}, {{ $despesa->toJson() }})" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="excluirDespesa({{ $despesa->id }}, {{ $despesa->grupo_recorrencia_id ? 'true' : 'false' }})" class="btn btn-ghost btn-icon btn-sm text-red" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p>Nenhuma despesa encontrada no período</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $despesas->links() }}</div>
</div>

{{-- Modal Nova Despesa --}}
<div class="modal-backdrop" id="modal-nova-despesa">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-plus" style="color:var(--color-primary);"></i>
            <h3>Nova Despesa</h3>
            <button class="modal-close" onclick="closeModal('modal-nova-despesa')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('despesas.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Valor *</label>
                        <input type="number" name="valor" id="novo-valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data da Despesa *</label>
                        <input type="date" name="data_compra" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quem</label>
                        <select name="quem_comprou" class="form-control">
                            <option value="">🏠 Todos da Casa</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Onde</label>
                        <select name="onde_comprou" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Conta / Banco</label>
                        <select name="forma_pagamento" id="novo-forma_pagamento" class="form-control"
                                onchange="despesaOnBancoChange(this)">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}"
                                    data-credito="{{ $b->tem_cartao_credito ? 1 : 0 }}"
                                    data-fechamento="{{ $b->dia_fechamento_cartao ?? '' }}"
                                    data-vencimento="{{ $b->dia_vencimento_cartao ?? '' }}"
                                    data-limite="{{ $b->limite_cartao ?? 0 }}">
                                    {{ $b->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Pagamento</label>
                        <select name="tipo_pagamento" id="novo-tipo_pagamento" class="form-control"
                                onchange="despesaOnTipoPagChange(this)">
                            <option value="">— Selecione —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">⚡ Pix</option>
                            <option value="debito">💳 Cartão de Débito</option>
                            <option value="credito">💳 Cartão de Crédito</option>
                            <option value="transferencia">🔄 Transferência Bancária</option>
                        </select>
                    </div>

                    {{-- Bloco cartão de crédito: aparece quando tipo = credito --}}
                    <div id="novo-info-credito" style="display:none;" class="form-group span-2">
                        <div style="padding:10px 14px;border-radius:7px;background:#fffbeb;border:1px solid #fde68a;font-size:12px;margin-bottom:10px;line-height:1.7;">
                            <i class="fa-solid fa-credit-card" style="color:#f59e0b;"></i>
                            <strong>Compra no Cartão de Crédito</strong><br>
                            <span id="novo-aviso-fatura-texto">Selecione o banco para ver as informações de fatura.</span>
                        </div>
                        <div style="display:flex;gap:12px;align-items:flex-end;">
                            <div style="flex:1;">
                                <label class="form-label">Nº de Parcelas</label>
                                <input type="number" name="parcelas" id="novo-parcelas" value="1" min="1" max="48" class="form-control"
                                       oninput="despesaOnParcelasChange(this)">
                            </div>
                            <div style="flex:1;">
                                <label class="form-label" style="color:#64748b;font-size:11px;">Valor por parcela</label>
                                <div id="novo-valor-parcela" style="padding:8px 12px;background:#f1f5f9;border-radius:6px;font-weight:700;color:#1e293b;font-size:13px;">—</div>
                            </div>
                        </div>
                    </div>

                    {{-- Parcelas para despesas recorrentes (não cartão) --}}
                    <div class="form-group" id="novo-parcelas-recorrente-row" style="display:none;">
                        <label class="form-label">Nº de Parcelas <span style="font-size:11px; color:var(--color-muted);">(0 = sem limite)</span></label>
                        <input type="number" name="parcelas" id="novo-parcelas-rec" value="1" min="0" max="360" class="form-control"
                               oninput="onParcelasChange(this,'novo-recorrente','novo-frequencia-row',null,null)">
                    </div>
                    <div class="form-group" id="novo-frequencia-row" style="display:none;">
                        <label class="form-label">Frequência</label>
                        <select name="frequencia" class="form-control">
                            <option value="mensal">Mensal</option>
                            <option value="diaria">Diária</option>
                            <option value="semanal">Semanal</option>
                            <option value="quinzenal">Quinzenal</option>
                            <option value="trimestral">Trimestral</option>
                            <option value="semestral">Semestral</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    {{-- checkbox oculto - ativado automaticamente pelo JS quando parcelas > 1 --}}
                    <input type="hidden" name="recorrente" id="novo-recorrente" value="">

                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-nova-despesa')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar Despesa --}}
<div class="modal-backdrop" id="modal-editar-despesa">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary);"></i>
            <h3>Editar Despesa</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-despesa')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-despesa">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Valor *</label>
                        <input type="number" name="valor" id="edit-valor" step="0.01" min="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data da Despesa *</label>
                        <input type="date" name="data_compra" id="edit-data_compra" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" id="edit-data_pagamento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quem</label>
                        <select name="quem_comprou" id="edit-quem_comprou" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Onde</label>
                        <select name="onde_comprou" id="edit-onde_comprou" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($fornecedores as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" id="edit-categoria_id" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Conta / Banco</label>
                        <select name="forma_pagamento" id="edit-forma_pagamento" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Pagamento</label>
                        <select name="tipo_pagamento" id="edit-tipo_pagamento" class="form-control">
                            <option value="">— Selecione —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">⚡ Pix</option>
                            <option value="debito">💳 Cartão de Débito</option>
                            <option value="credito">💳 Cartão de Crédito</option>
                            <option value="transferencia">🔄 Transferência Bancária</option>
                        </select>
                    </div>

                    {{-- Escopo da edição (aparece só em despesas recorrentes) --}}
                    <div id="edit-escopo-container" style="display:none;" class="form-group span-2">
                        <div class="alert alert-info" style="padding:8px 12px; border-radius:6px; background:#fffbeb; border:1px solid #fde68a; font-size:13px; margin-bottom:8px;">
                            <i class="fa-solid fa-rotate" style="color:#f59e0b;"></i>
                            Esta é uma despesa recorrente. Escolha o que deseja alterar:
                        </div>
                        <select name="escopo" id="edit-escopo" class="form-control">
                            <option value="apenas_esta">Alterar somente esta parcela / este mês</option>
                            <option value="esta_e_futuras">Alterar esta e todas as próximas parcelas</option>
                        </select>
                    </div>

                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="edit-observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-despesa')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="" id="form-excluir-despesa" style="display:none;">
    @csrf @method('DELETE')
    <input type="hidden" name="escopo" id="escopo-excluir" value="apenas_esta">
</form>

{{-- ── Modal de confirmação de exclusão ──────────────────────────────────── --}}
<div class="modal-backdrop" id="modal-confirmar-exclusao-despesa" style="z-index:1100;">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;"></i>
            <h3>Excluir Despesa</h3>
            <button class="modal-close" onclick="closeModal('modal-confirmar-exclusao-despesa')">&times;</button>
        </div>
        <div class="modal-body">
            {{-- Lançamento simples --}}
            <div id="exc-d-bloco-simples">
                <p style="font-size:14px;color:var(--color-text);margin:0 0 20px;">
                    Tem certeza que deseja excluir esta despesa? Essa ação não pode ser desfeita.
                </p>
                <div class="modal-footer" style="padding:0;">
                    <button type="button" onclick="closeModal('modal-confirmar-exclusao-despesa')" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="button" onclick="confirmarExclusaoDespesa('apenas_esta')" class="btn" style="background:#dc2626;color:#fff;">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </button>
                </div>
            </div>
            {{-- Lançamento recorrente --}}
            <div id="exc-d-bloco-recorrente" style="display:none;">
                <p style="font-size:14px;color:var(--color-text);margin:0 0 16px;">
                    Esta é uma <strong>despesa recorrente</strong>. O que você deseja fazer?
                </p>
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;" id="lbl-exc-d-apenas">
                        <input type="radio" name="opcao-excluir-d" value="apenas_esta" checked onchange="excDAtualizarSelecao()" style="margin-top:2px;accent-color:#dc2626;">
                        <div>
                            <div style="font-weight:600;font-size:13px;">Somente esta</div>
                            <div style="font-size:12px;color:var(--color-text-muted);">Remove apenas este lançamento</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;" id="lbl-exc-d-futuras">
                        <input type="radio" name="opcao-excluir-d" value="esta_e_futuras" onchange="excDAtualizarSelecao()" style="margin-top:2px;accent-color:#dc2626;">
                        <div>
                            <div style="font-weight:600;font-size:13px;">Esta e todas as próximas</div>
                            <div style="font-size:12px;color:var(--color-text-muted);">Remove este e todos os lançamentos futuros da recorrência</div>
                        </div>
                    </label>
                </div>
                <div class="modal-footer" style="padding:0;">
                    <button type="button" onclick="closeModal('modal-confirmar-exclusao-despesa')" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="button" onclick="confirmarExclusaoDespesa()" class="btn" style="background:#dc2626;color:#fff;">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Cartão de Crédito — Modal Nova Despesa ────────────────────────────────────

function despesaCalcVencimento(dataCompraStr, diaFechamento, diaVencimento) {
    if (!dataCompraStr || !diaFechamento || !diaVencimento) return null;
    const compra   = new Date(dataCompraStr + 'T12:00:00');
    const diaComp  = compra.getDate();
    let ano = compra.getFullYear(), mes = compra.getMonth();
    mes += (diaComp <= diaFechamento) ? 1 : 2;
    if (mes > 11) { ano += Math.floor(mes / 12); mes = mes % 12; }
    return new Date(ano, mes, diaVencimento).toLocaleDateString('pt-BR');
}

function despesaAtualizarInfoCartao() {
    const tipoPag    = document.getElementById('novo-tipo_pagamento');
    const bancoSel   = document.getElementById('novo-forma_pagamento');
    const infoDiv    = document.getElementById('novo-info-credito');
    const textoSpan  = document.getElementById('novo-aviso-fatura-texto');
    const valorInp   = document.querySelector('#modal-nova-despesa input[name="valor"]');
    const parcelasInp= document.getElementById('novo-parcelas');
    const divVP      = document.getElementById('novo-valor-parcela');

    if (!tipoPag || tipoPag.value !== 'credito') {
        if (infoDiv) infoDiv.style.display = 'none';
        return;
    }
    if (infoDiv) infoDiv.style.display = 'block';

    const opt         = bancoSel ? bancoSel.options[bancoSel.selectedIndex] : null;
    const fechamento  = opt ? parseInt(opt.dataset.fechamento) : 0;
    const vencimento  = opt ? parseInt(opt.dataset.vencimento) : 0;
    const limite      = opt ? parseFloat(opt.dataset.limite) || 0 : 0;
    const n           = parseInt(parcelasInp?.value) || 1;
    const valor       = parseFloat(valorInp?.value) || 0;
    const dataCompra  = document.querySelector('#modal-nova-despesa input[name="data_compra"]')?.value
                        || new Date().toISOString().substring(0,10);

    if (textoSpan) {
        if (!fechamento || !vencimento) {
            textoSpan.innerHTML = '⚠️ Configure o dia de fechamento e vencimento do cartão nas configurações do banco.';
        } else {
            const diaComp  = parseInt(dataCompra.split('-')[2]);
            const aviso    = diaComp > fechamento
                ? `⚠️ Compra após o fechamento (dia ${fechamento}) — entra na <strong>próxima fatura</strong>.`
                : `✅ Compra dentro do ciclo atual (fechamento dia ${fechamento}).`;
            const primVenc = despesaCalcVencimento(dataCompra, fechamento, vencimento);
            const parInfo  = n > 1
                ? `<br>📅 <strong>${n}x</strong> — 1ª parcela vence em <strong>${primVenc}</strong>`
                : `<br>📅 Vencimento da fatura: <strong>${primVenc}</strong>`;
            const limInfo  = limite > 0
                ? `<br>💳 Limite: <strong>R$ ${limite.toFixed(2).replace('.',',')}</strong>` : '';
            textoSpan.innerHTML = aviso + parInfo + limInfo;
        }
    }

    if (divVP && valor > 0) {
        const vp = (valor / n).toFixed(2).replace('.',',');
        divVP.textContent = n > 1 ? `R$ ${vp} × ${n}` : `R$ ${valor.toFixed(2).replace('.',',')} (à vista)`;
    } else if (divVP) {
        divVP.textContent = '—';
    }

    // Cartão de crédito: força recorrente + mensal, oculta bloco recorrência manual
    const recInput = document.getElementById('novo-recorrente');
    if (recInput) recInput.value = n > 1 ? '1' : '';
    const recRow = document.getElementById('novo-parcelas-recorrente-row');
    const freqRow = document.getElementById('novo-frequencia-row');
    if (recRow) recRow.style.display = 'none';
    if (freqRow) freqRow.style.display = 'none';
}

function despesaOnBancoChange(sel) {
    despesaAtualizarInfoCartao();
}

function despesaOnTipoPagChange(sel) {
    const infoDiv = document.getElementById('novo-info-credito');
    const recRow  = document.getElementById('novo-parcelas-recorrente-row');
    const freqRow = document.getElementById('novo-frequencia-row');

    if (sel.value === 'credito') {
        despesaAtualizarInfoCartao();
        if (recRow)  recRow.style.display  = 'none';
        if (freqRow) freqRow.style.display = 'none';
    } else {
        if (infoDiv) infoDiv.style.display = 'none';
        // Permite configurar recorrência manual para outros tipos
        if (recRow)  recRow.style.display  = '';
        if (freqRow) freqRow.style.display = 'none'; // oculto até mudar parcelas
    }
}

function despesaOnParcelasChange(inp) {
    despesaAtualizarInfoCartao();
}

// Atualiza valor/parcela ao digitar valor — somente se credito ativo
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#modal-nova-despesa form');
    if (!form) return;
    form.querySelector('input[name="valor"]')?.addEventListener('input', despesaAtualizarInfoCartao);
    form.querySelector('input[name="data_compra"]')?.addEventListener('change', despesaAtualizarInfoCartao);
});

// ── Recorrência manual (não cartão) ──────────────────────────────────────────
function onParcelasChange(inputParcelas, idRecorrente, idFreqRow) {
    const val     = parseInt(inputParcelas.value) || 1;
    const recInput= document.getElementById(idRecorrente);
    const freqRow = document.getElementById(idFreqRow);
    if (recInput) recInput.value = (val !== 1) ? '1' : '';
    if (freqRow)  freqRow.style.display = (val !== 1) ? '' : 'none';
}

// ── Modal de edição ───────────────────────────────────────────────────────────

function editarDespesa(id, data) {
    document.getElementById('form-editar-despesa').action = `/despesas/${id}`;
    document.getElementById('edit-valor').value            = data.valor;
    document.getElementById('edit-data_compra').value      = data.data_compra ? data.data_compra.substring(0, 10) : '';
    document.getElementById('edit-data_pagamento').value   = data.data_pagamento ? data.data_pagamento.substring(0, 10) : '';
    document.getElementById('edit-quem_comprou').value     = data.quem_comprou || '';
    document.getElementById('edit-onde_comprou').value     = data.onde_comprou || '';
    document.getElementById('edit-categoria_id').value     = data.categoria_id || '';
    document.getElementById('edit-forma_pagamento').value  = data.forma_pagamento || '';
    document.getElementById('edit-tipo_pagamento').value   = data.tipo_pagamento || '';
    document.getElementById('edit-observacoes').value      = data.observacoes || '';

    const escopoContainer = document.getElementById('edit-escopo-container');
    escopoContainer.style.display = data.grupo_recorrencia_id ? 'block' : 'none';
    if (data.grupo_recorrencia_id) {
        document.getElementById('edit-escopo').value = 'apenas_esta';
    }

    openModal('modal-editar-despesa');
}

// ── Exclusão ──────────────────────────────────────────────────────────────────
let _excDespesaId = null;

function excluirDespesa(id, isRecorrente) {
    _excDespesaId = id;
    document.getElementById('form-excluir-despesa').action = `/despesas/${id}`;

    if (isRecorrente) {
        document.getElementById('exc-d-bloco-simples').style.display   = 'none';
        document.getElementById('exc-d-bloco-recorrente').style.display = '';
        // Reseta seleção
        document.querySelector('[name="opcao-excluir-d"][value="apenas_esta"]').checked = true;
        excDAtualizarSelecao();
    } else {
        document.getElementById('exc-d-bloco-simples').style.display   = '';
        document.getElementById('exc-d-bloco-recorrente').style.display = 'none';
    }
    openModal('modal-confirmar-exclusao-despesa');
}

function excDAtualizarSelecao() {
    const val = document.querySelector('[name="opcao-excluir-d"]:checked').value;
    document.getElementById('lbl-exc-d-apenas').style.borderColor  = val === 'apenas_esta'    ? '#dc2626' : '#e2e8f0';
    document.getElementById('lbl-exc-d-futuras').style.borderColor = val === 'esta_e_futuras' ? '#dc2626' : '#e2e8f0';
}

function confirmarExclusaoDespesa(escopoFixo) {
    const escopo = escopoFixo || document.querySelector('[name="opcao-excluir-d"]:checked').value;
    document.getElementById('escopo-excluir').value = escopo;
    document.getElementById('form-excluir-despesa').submit();
    closeModal('modal-confirmar-exclusao-despesa');
}
</script>
@endpush
