@extends('layouts.main')

@section('title', 'Despesas')
@section('page-title', 'Despesas')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <form method="GET" action="{{ route('despesas.index') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="width:150px;">
        <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="width:150px;">
        <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
        <a href="{{ route('despesas.index') }}" class="btn-secondary">Mês Atual</a>
    </form>
    <button class="btn-primary" onclick="openModal('modal-nova-despesa')">
        <i class="fa-solid fa-plus"></i> Nova Despesa
    </button>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div style="font-size:15px;font-weight:700;color:#1e293b;">
            <i class="fa-solid fa-arrow-trend-down" style="color:#ef4444;"></i>
            {{ $despesas->total() }} despesa(s) — Período: {{ \Carbon\Carbon::parse($inicio)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($fim)->format('d/m/Y') }}
        </div>
        <div style="font-size:16px;font-weight:800;color:#ef4444;">
            Total: R$ {{ number_format($totalValor, 2, ',', '.') }}
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Valor</th>
                    <th>Categoria</th>
                    <th>Quem Comprou</th>
                    <th>Onde Comprou</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($despesas as $despesa)
                    <tr>
                        <td>{{ $despesa->data_compra->format('d/m/Y') }}</td>
                        <td><strong style="color:#ef4444;">R$ {{ number_format($despesa->valor, 2, ',', '.') }}</strong></td>
                        <td>
                            @if($despesa->categoria)
                                <span class="badge badge-info">{{ $despesa->categoria->nome }}</span>
                            @else
                                <span class="badge badge-gray">Sem categoria</span>
                            @endif
                        </td>
                        <td>{{ $despesa->familiar?->nome ?? '—' }}</td>
                        <td>{{ $despesa->fornecedor?->nome ?? '—' }}</td>
                        <td>{{ $despesa->banco?->nome ?? '—' }}</td>
                        <td>
                            @if($despesa->data_pagamento)
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Pago</span>
                            @else
                                <span class="badge badge-warning">Pendente</span>
                            @endif
                            @if($despesa->recorrente)
                                <span class="badge badge-gray" style="margin-left:4px;" title="{{ $despesa->frequencia }}">
                                    <i class="fa-solid fa-rotate"></i>
                                </span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button onclick="editarDespesa({{ $despesa->id }}, {{ $despesa->toJson() }})" class="btn-secondary btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="excluirDespesa({{ $despesa->id }}, {{ $despesa->grupo_recorrencia_id ? 'true' : 'false' }})" class="btn-danger btn-sm" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:#94a3b8;">
                            <i class="fa-solid fa-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                            Nenhuma despesa encontrada no período
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">
        {{ $despesas->links() }}
    </div>
</div>

{{-- Modal Nova Despesa --}}
<div class="modal-backdrop" id="modal-nova-despesa">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-plus" style="color:#6366f1;"></i> Nova Despesa
            <button onclick="closeModal('modal-nova-despesa')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('despesas.store') }}">
            @csrf
            <div class="grid-2" style="gap:12px;">
                <div>
                    <label class="form-label">Valor *</label>
                    <input type="number" name="valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                </div>
                <div>
                    <label class="form-label">Data da Compra *</label>
                    <input type="date" name="data_compra" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" class="form-control">
                </div>
                <div>
                    <label class="form-label">Quem Comprou</label>
                    <select name="quem_comprou" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($familiares as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Onde Comprou</label>
                    <select name="onde_comprou" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($fornecedores as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Forma de Pagamento</label>
                    <select name="forma_pagamento" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($bancos as $b)
                            <option value="{{ $b->id }}">{{ $b->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Recorrência</label>
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
                <div style="grid-column:span 2;">
                    <label class="form-label">Parcelas (0 = sem limite)</label>
                    <input type="number" name="parcelas" value="1" min="0" class="form-control">
                </div>
                <div style="grid-column:span 2;display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="recorrente" id="recorrente-new" value="1" onchange="toggleParcelas(this)">
                    <label for="recorrente-new" style="font-size:14px;cursor:pointer;">Despesa recorrente/parcelada</label>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações..."></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-nova-despesa')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar Despesa --}}
<div class="modal-backdrop" id="modal-editar-despesa">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#6366f1;"></i> Editar Despesa
            <button onclick="closeModal('modal-editar-despesa')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-despesa">
            @csrf
            @method('PUT')
            <div class="grid-2" style="gap:12px;">
                <div>
                    <label class="form-label">Valor *</label>
                    <input type="number" name="valor" id="edit-valor" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Data da Compra *</label>
                    <input type="date" name="data_compra" id="edit-data_compra" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" id="edit-data_pagamento" class="form-control">
                </div>
                <div>
                    <label class="form-label">Quem Comprou</label>
                    <select name="quem_comprou" id="edit-quem_comprou" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($familiares as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Onde Comprou</label>
                    <select name="onde_comprou" id="edit-onde_comprou" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($fornecedores as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" id="edit-categoria_id" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Forma de Pagamento</label>
                    <select name="forma_pagamento" id="edit-forma_pagamento" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($bancos as $b)
                            <option value="{{ $b->id }}">{{ $b->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="edit-escopo-container" style="display:none;grid-column:span 2;">
                    <label class="form-label">Escopo da edição</label>
                    <select name="escopo" class="form-control">
                        <option value="apenas_esta">Apenas esta</option>
                        <option value="esta_e_futuras">Esta e futuras</option>
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="edit-observacoes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-despesa')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
    </div>
</div>

{{-- Form Exclusão --}}
<form method="POST" action="" id="form-excluir-despesa" style="display:none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="escopo" id="escopo-excluir" value="apenas_esta">
</form>
@endsection

@push('scripts')
<script>
function toggleParcelas(cb) {
    // show/hide parcelas context
}

function editarDespesa(id, data) {
    document.getElementById('form-editar-despesa').action = `/despesas/${id}`;
    document.getElementById('edit-valor').value = data.valor;
    document.getElementById('edit-data_compra').value = data.data_compra ? data.data_compra.substring(0, 10) : '';
    document.getElementById('edit-data_pagamento').value = data.data_pagamento ? data.data_pagamento.substring(0, 10) : '';
    document.getElementById('edit-quem_comprou').value = data.quem_comprou || '';
    document.getElementById('edit-onde_comprou').value = data.onde_comprou || '';
    document.getElementById('edit-categoria_id').value = data.categoria_id || '';
    document.getElementById('edit-forma_pagamento').value = data.forma_pagamento || '';
    document.getElementById('edit-observacoes').value = data.observacoes || '';
    document.getElementById('edit-escopo-container').style.display = data.grupo_recorrencia_id ? 'block' : 'none';
    openModal('modal-editar-despesa');
}

function excluirDespesa(id, isRecorrente) {
    let escopo = 'apenas_esta';
    if (isRecorrente) {
        const opcao = confirm('Excluir esta e todas as futuras? (OK = sim, Cancelar = apenas esta)');
        escopo = opcao ? 'esta_e_futuras' : 'apenas_esta';
    } else {
        if (!confirm('Tem certeza que deseja excluir esta despesa?')) return;
    }
    const form = document.getElementById('form-excluir-despesa');
    form.action = `/despesas/${id}`;
    document.getElementById('escopo-excluir').value = escopo;
    form.submit();
}
</script>
@endpush
