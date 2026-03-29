@extends('layouts.main')
@section('title', 'Receitas')
@section('page-title', 'Receitas')

@section('content')

{{-- ─── Filtros ─────────────────────────────────────────────────────────── --}}
@php
    $dtR         = \Carbon\Carbon::parse($inicio);
    $dtRAnt      = $dtR->copy()->subMonth();
    $dtRProx     = $dtR->copy()->addMonth();
    $mesNomeR    = $dtR->locale('pt_BR')->isoFormat('MMMM [de] YYYY');
    $ehMesAtualR = $dtR->format('Y-m') === now()->format('Y-m');
    $ehHojeR     = $inicio === now()->format('Y-m-d') && $fim === now()->format('Y-m-d');

    $urlRMesAnt  = route('receitas.index', array_merge(request()->except(['inicio','fim']), ['inicio' => $dtRAnt->startOfMonth()->format('Y-m-d'), 'fim' => $dtRAnt->copy()->endOfMonth()->format('Y-m-d')]));
    $urlRMesProx = route('receitas.index', array_merge(request()->except(['inicio','fim']), ['inicio' => $dtRProx->startOfMonth()->format('Y-m-d'), 'fim' => $dtRProx->copy()->endOfMonth()->format('Y-m-d')]));
    $urlRHoje    = route('receitas.index', array_filter(['inicio' => now()->format('Y-m-d'), 'fim' => now()->format('Y-m-d'), 'familiar_id' => $familiarId]));
    $urlRMesAtu  = route('receitas.index', array_filter(['familiar_id' => $familiarId]));
    $urlRTodas   = route('receitas.index', array_filter(['inicio' => $inicio, 'fim' => $fim]));
@endphp

<div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-bottom:12px;">
    <button class="btn btn-success" onclick="openModal('modal-nova-receita')">
        <i class="fa-solid fa-plus"></i> Nova Receita
    </button>
</div>

<div class="card filtros-bar">
    <div class="filtros-lanc">

        {{-- Mês --}}
        <div class="filtro-grupo filtro-grupo-centro">
            <div style="display:flex;align-items:center;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                <a href="{{ $urlRMesAnt }}" class="nav-mes-btn" title="Mês anterior"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>
                <span class="mes-label-btn">{{ $ehHojeR ? 'Hoje' : ucfirst($mesNomeR) }}</span>
                <a href="{{ $urlRMesProx }}" class="nav-mes-btn" title="Próximo mês"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>
            </div>
            <div style="display:flex;gap:6px;">
                <a href="{{ $urlRHoje }}"
                   style="padding:6px 11px;font-size:12px;font-weight:600;border-radius:6px;text-decoration:none;white-space:nowrap;
                          border:1px solid {{ $ehHojeR ? 'var(--color-primary)' : '#e2e8f0' }};
                          background:{{ $ehHojeR ? 'var(--color-primary)' : '#fff' }};
                          color:{{ $ehHojeR ? '#fff' : '#64748b' }};">
                    <i class="fa-solid fa-calendar-day"></i> Hoje
                </a>
                @if(!$ehMesAtualR && !$ehHojeR)
                <a href="{{ $urlRMesAtu }}"
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
                <a href="{{ $urlRTodas }}" class="av-item" title="Todos da Casa">
                    <div class="av-circulo" style="border:3px solid {{ !$familiarId ? 'var(--color-primary)' : 'transparent' }};outline:{{ !$familiarId ? 'none' : '2px solid #e2e8f0' }};background:{{ !$familiarId ? 'var(--color-primary)' : '#f1f5f9' }};box-shadow:{{ !$familiarId ? '0 0 0 2px var(--color-primary)44' : 'none' }};">
                        <i class="fa-solid fa-house" style="font-size:13px;color:{{ !$familiarId ? '#fff' : '#64748b' }};"></i>
                    </div>
                    <span class="av-nome" style="color:{{ !$familiarId ? 'var(--color-primary)' : '#94a3b8' }};font-weight:{{ !$familiarId ? '700' : '400' }};">Todos</span>
                </a>
                @foreach($familiares as $fam)
                @php
                    $rSel  = $familiarId === $fam->id;
                    $rIni  = implode('', array_map(fn($p) => strtoupper(substr($p,0,1)), array_slice(explode(' ',$fam->nome),0,2)));
                    $rCors = ['#6366f1','#0ea5e9','#16a34a','#f59e0b','#ef4444','#8b5cf6','#14b8a6'];
                    $rCor  = $rCors[$fam->id % count($rCors)];
                    $rUrl  = $rSel
                        ? route('receitas.index', array_filter(['inicio'=>$inicio,'fim'=>$fim]))
                        : route('receitas.index', array_filter(['inicio'=>$inicio,'fim'=>$fim,'familiar_id'=>$fam->id]));
                @endphp
                <a href="{{ $rUrl }}" class="av-item" title="{{ $fam->nome }}">
                    <div class="av-circulo" style="border:3px solid {{ $rSel ? $rCor : 'transparent' }};outline:{{ $rSel ? 'none' : '2px solid #e2e8f0' }};box-shadow:{{ $rSel ? '0 0 0 2px '.$rCor.'44' : 'none' }};">
                        @if($fam->foto)
                            <img src="{{ Storage::url($fam->foto) }}" alt="{{ $fam->nome }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div style="width:100%;height:100%;background:{{ $rCor }};color:#fff;font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;border-radius:50%;">{{ $rIni }}</div>
                        @endif
                    </div>
                    <span class="av-nome" style="color:{{ $rSel ? $rCor : '#94a3b8' }};font-weight:{{ $rSel ? '700' : '400' }};">{{ explode(' ',$fam->nome)[0] }}</span>
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
            <i class="fa-solid fa-arrow-trend-up text-green"></i>
            <strong class="fw-600" style="color:var(--color-text);">{{ $receitas->total() }}</strong> receita(s)
        </div>
        <div class="fw-700 text-green" style="font-size:15px;">
            R$ {{ number_format($totalValor, 2, ',', '.') }}
        </div>
    </div>

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Data Prevista</th>
                    <th>Valor</th>
                    <th class="hide-mobile">Categoria</th>
                    <th class="hide-mobile">Quem Recebeu</th>
                    <th class="hide-mobile">Conta / Forma</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($receitas as $receita)
                    <tr>
                        <td style="white-space:nowrap;">{{ $receita->data_prevista_recebimento->format('d/m/Y') }}</td>
                        <td style="white-space:nowrap;"><strong class="text-green">R$ {{ number_format($receita->valor, 2, ',', '.') }}</strong></td>
                        <td class="hide-mobile">
                            @if($receita->categoria)
                                <span class="badge badge-blue">{{ $receita->categoria->nome }}</span>
                            @else
                                <span class="text-subtle">—</span>
                            @endif
                        </td>
                        <td class="hide-mobile">{{ $receita->familiar?->nome ?? '—' }}</td>
                        <td class="hide-mobile">
                            {{ $receita->banco?->nome ?? '—' }}
                            @if($receita->tipo_pagamento)
                                <br>
                                @php
                                    $iconesTipo = [
                                        'dinheiro'     => ['fa-money-bill-wave', 'badge-green',  'Dinheiro'],
                                        'pix'          => ['fa-bolt',            'badge-teal',   'Pix'],
                                        'transferencia'=> ['fa-arrow-right-arrow-left', 'badge-slate', 'Transf.'],
                                        'deposito'     => ['fa-building-columns', 'badge-blue',  'Depósito'],
                                        'outros'       => ['fa-circle',           'badge-slate',  'Outros'],
                                    ];
                                    $t = $iconesTipo[$receita->tipo_pagamento] ?? ['fa-circle', 'badge-slate', $receita->tipo_pagamento];
                                @endphp
                                <span class="badge {{ $t[1] }}" style="font-size:10px;">
                                    <i class="fa-solid {{ $t[0] }}"></i> {{ $t[2] }}
                                </span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            @if($receita->status === 'recebido')
                                <span class="badge badge-green"><i class="fa-solid fa-check"></i> Recebido</span>
                            @elseif($receita->status === 'vencido')
                                <span class="badge badge-red"><i class="fa-solid fa-triangle-exclamation"></i> Vencido</span>
                            @else
                                <span class="badge badge-amber">A Receber</span>
                            @endif
                            @if($receita->recorrente)
                                <span class="badge badge-slate"><i class="fa-solid fa-rotate"></i></span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button onclick="editarReceita({{ $receita->id }}, {{ $receita->toJson() }})" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="excluirReceita({{ $receita->id }}, {{ $receita->grupo_recorrencia_id ? 'true' : 'false' }})" class="btn btn-ghost btn-icon btn-sm text-red" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p>Nenhuma receita encontrada no período</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $receitas->links() }}</div>
</div>

{{-- Modal Nova Receita --}}
<div class="modal-backdrop" id="modal-nova-receita">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-plus" style="color:#16a34a;"></i>
            <h3>Nova Receita</h3>
            <button class="modal-close" onclick="closeModal('modal-nova-receita')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('receitas.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Valor *</label>
                        <input type="number" name="valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Prevista *</label>
                        <input type="date" name="data_prevista_recebimento" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Recebimento</label>
                        <input type="date" name="data_recebimento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quem Recebeu</label>
                        <select name="quem_recebeu" class="form-control">
                            <option value="">🏠 Todos da Casa</option>
                            @foreach($familiares as $f)
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
                        <label class="form-label">Conta de Recebimento</label>
                        <select name="forma_recebimento" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Recebimento</label>
                        <select name="tipo_pagamento" class="form-control">
                            <option value="">— Selecione —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">⚡ Pix</option>
                            <option value="transferencia">🔄 Transferência Bancária</option>
                            <option value="deposito">🏦 Depósito Bancário</option>
                            <option value="outros">📌 Outros</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nº de Parcelas <span style="font-size:11px; color:var(--color-muted);">(0 = sem limite)</span></label>
                        <input type="number" name="parcelas" id="rec-novo-parcelas" value="1" min="0" max="360" class="form-control"
                               oninput="onParcelasChangeReceita(this,'rec-novo-recorrente','rec-novo-frequencia-row')">
                    </div>
                    <div class="form-group" id="rec-novo-frequencia-row" style="display:none;">
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
                    {{-- campo oculto ativado pelo JS --}}
                    <input type="hidden" name="recorrente" id="rec-novo-recorrente" value="">

                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-nova-receita')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar Receita --}}
<div class="modal-backdrop" id="modal-editar-receita">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:#16a34a;"></i>
            <h3>Editar Receita</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-receita')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-receita">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Valor *</label>
                        <input type="number" name="valor" id="r-edit-valor" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Prevista *</label>
                        <input type="date" name="data_prevista_recebimento" id="r-edit-dpr" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Recebimento</label>
                        <input type="date" name="data_recebimento" id="r-edit-dr" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quem Recebeu</label>
                        <select name="quem_recebeu" id="r-edit-quem" class="form-control">
                            <option value="">🏠 Todos da Casa</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" id="r-edit-cat" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($categorias as $c)
                                <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Conta</label>
                        <select name="forma_recebimento" id="r-edit-banco" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forma de Recebimento</label>
                        <select name="tipo_pagamento" id="r-edit-tipo" class="form-control">
                            <option value="">— Selecione —</option>
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">⚡ Pix</option>
                            <option value="transferencia">🔄 Transferência Bancária</option>
                            <option value="deposito">🏦 Depósito Bancário</option>
                            <option value="outros">📌 Outros</option>
                        </select>
                    </div>

                    {{-- Escopo da edição (só para recorrentes) --}}
                    <div id="r-edit-escopo-container" style="display:none;" class="form-group span-2">
                        <div class="alert alert-info" style="padding:8px 12px; border-radius:6px; background:#fffbeb; border:1px solid #fde68a; font-size:13px; margin-bottom:8px;">
                            <i class="fa-solid fa-rotate" style="color:#f59e0b;"></i>
                            Esta é uma receita recorrente. Escolha o que deseja alterar:
                        </div>
                        <select name="escopo" id="r-edit-escopo" class="form-control">
                            <option value="apenas_esta">Alterar somente esta ocorrência / este mês</option>
                            <option value="esta_e_futuras">Alterar esta e todas as próximas ocorrências</option>
                        </select>
                    </div>

                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="r-edit-obs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-receita')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" action="" id="form-excluir-receita" style="display:none;">
    @csrf @method('DELETE')
    <input type="hidden" name="escopo" id="r-escopo-excluir" value="apenas_esta">
</form>

{{-- ── Modal de confirmação de exclusão ──────────────────────────────────── --}}
<div class="modal-backdrop" id="modal-confirmar-exclusao-receita" style="z-index:1100;">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;"></i>
            <h3>Excluir Receita</h3>
            <button class="modal-close" onclick="closeModal('modal-confirmar-exclusao-receita')">&times;</button>
        </div>
        <div class="modal-body">
            {{-- Lançamento simples --}}
            <div id="exc-r-bloco-simples">
                <p style="font-size:14px;color:var(--color-text);margin:0 0 20px;">
                    Tem certeza que deseja excluir esta receita? Essa ação não pode ser desfeita.
                </p>
                <div class="modal-footer" style="padding:0;">
                    <button type="button" onclick="closeModal('modal-confirmar-exclusao-receita')" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="button" onclick="confirmarExclusaoReceita('apenas_esta')" class="btn" style="background:#dc2626;color:#fff;">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </button>
                </div>
            </div>
            {{-- Lançamento recorrente --}}
            <div id="exc-r-bloco-recorrente" style="display:none;">
                <p style="font-size:14px;color:var(--color-text);margin:0 0 16px;">
                    Esta é uma <strong>receita recorrente</strong>. O que você deseja fazer?
                </p>
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;" id="lbl-exc-r-apenas">
                        <input type="radio" name="opcao-excluir-r" value="apenas_esta" checked onchange="excRAtualizarSelecao()" style="margin-top:2px;accent-color:#16a34a;">
                        <div>
                            <div style="font-weight:600;font-size:13px;">Somente esta</div>
                            <div style="font-size:12px;color:var(--color-text-muted);">Remove apenas esta ocorrência</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;" id="lbl-exc-r-futuras">
                        <input type="radio" name="opcao-excluir-r" value="esta_e_futuras" onchange="excRAtualizarSelecao()" style="margin-top:2px;accent-color:#16a34a;">
                        <div>
                            <div style="font-weight:600;font-size:13px;">Esta e todas as próximas</div>
                            <div style="font-size:12px;color:var(--color-text-muted);">Remove esta e todas as ocorrências futuras da recorrência</div>
                        </div>
                    </label>
                </div>
                <div class="modal-footer" style="padding:0;">
                    <button type="button" onclick="closeModal('modal-confirmar-exclusao-receita')" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="button" onclick="confirmarExclusaoReceita()" class="btn" style="background:#dc2626;color:#fff;">
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
// ── Parcelas (nova receita) ────────────────────────────────────────────────────
function onParcelasChangeReceita(inputParcelas, idRecorrente, idFreqRow) {
    const val      = parseInt(inputParcelas.value) || 1;
    const recInput = document.getElementById(idRecorrente);
    const freqRow  = document.getElementById(idFreqRow);

    if (recInput) recInput.value = (val !== 1) ? '1' : '';
    if (freqRow)  freqRow.style.display = (val !== 1) ? '' : 'none';
}

// ── Modal de edição ───────────────────────────────────────────────────────────
function editarReceita(id, data) {
    document.getElementById('form-editar-receita').action = `/receitas/${id}`;
    document.getElementById('r-edit-valor').value   = data.valor;
    document.getElementById('r-edit-dpr').value     = data.data_prevista_recebimento ? data.data_prevista_recebimento.substring(0,10) : '';
    document.getElementById('r-edit-dr').value      = data.data_recebimento ? data.data_recebimento.substring(0,10) : '';
    document.getElementById('r-edit-quem').value    = data.quem_recebeu || '';
    document.getElementById('r-edit-cat').value     = data.categoria_id || '';
    document.getElementById('r-edit-banco').value   = data.forma_recebimento || '';
    document.getElementById('r-edit-tipo').value    = data.tipo_pagamento || '';
    document.getElementById('r-edit-obs').value     = data.observacoes || '';

    const escopoContainer = document.getElementById('r-edit-escopo-container');
    escopoContainer.style.display = data.grupo_recorrencia_id ? 'block' : 'none';
    if (data.grupo_recorrencia_id) {
        document.getElementById('r-edit-escopo').value = 'apenas_esta';
    }

    openModal('modal-editar-receita');
}

// ── Exclusão ──────────────────────────────────────────────────────────────────
let _excReceitaId = null;

function excluirReceita(id, isRecorrente) {
    _excReceitaId = id;
    document.getElementById('form-excluir-receita').action = `/receitas/${id}`;

    if (isRecorrente) {
        document.getElementById('exc-r-bloco-simples').style.display    = 'none';
        document.getElementById('exc-r-bloco-recorrente').style.display = '';
        document.querySelector('[name="opcao-excluir-r"][value="apenas_esta"]').checked = true;
        excRAtualizarSelecao();
    } else {
        document.getElementById('exc-r-bloco-simples').style.display    = '';
        document.getElementById('exc-r-bloco-recorrente').style.display = 'none';
    }
    openModal('modal-confirmar-exclusao-receita');
}

function excRAtualizarSelecao() {
    const val = document.querySelector('[name="opcao-excluir-r"]:checked').value;
    document.getElementById('lbl-exc-r-apenas').style.borderColor  = val === 'apenas_esta'    ? '#16a34a' : '#e2e8f0';
    document.getElementById('lbl-exc-r-futuras').style.borderColor = val === 'esta_e_futuras' ? '#16a34a' : '#e2e8f0';
}

function confirmarExclusaoReceita(escopoFixo) {
    const escopo = escopoFixo || document.querySelector('[name="opcao-excluir-r"]:checked').value;
    document.getElementById('r-escopo-excluir').value = escopo;
    document.getElementById('form-excluir-receita').submit();
    closeModal('modal-confirmar-exclusao-receita');
}
</script>
@endpush
