@extends('layouts.main')
@section('title', 'Despesas')
@section('page-title', 'Despesas')

@section('content')

{{-- Toolbar --}}
<div class="section-header mb-4">
    <form method="GET" action="{{ route('despesas.index') }}" class="d-flex flex-wrap align-center gap-2">
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="width:135px;">
        <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="width:135px;">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
        <a href="{{ route('despesas.index') }}" class="btn btn-secondary btn-sm">Mês Atual</a>
    </form>
    <button class="btn btn-primary" onclick="openModal('modal-nova-despesa')">
        <i class="fa-solid fa-plus"></i> Nova Despesa
    </button>
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
                    <th class="hide-mobile">Conta</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($despesas as $despesa)
                    <tr>
                        <td style="white-space:nowrap;">{{ $despesa->data_compra->format('d/m/Y') }}</td>
                        <td style="white-space:nowrap;"><strong class="text-red">R$ {{ number_format($despesa->valor, 2, ',', '.') }}</strong></td>
                        <td class="hide-mobile">
                            @if($despesa->categoria)
                                <span class="badge badge-blue">{{ $despesa->categoria->nome }}</span>
                            @else
                                <span class="text-subtle">—</span>
                            @endif
                        </td>
                        <td class="hide-mobile">{{ $despesa->familiar?->nome ?? '—' }}</td>
                        <td class="hide-mobile">{{ $despesa->fornecedor?->nome ?? '—' }}</td>
                        <td class="hide-mobile">{{ $despesa->banco?->nome ?? '—' }}</td>
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
                        <input type="number" name="valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data da Compra *</label>
                        <input type="date" name="data_compra" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quem Comprou</label>
                        <select name="quem_comprou" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Onde Comprou</label>
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
                        <label class="form-label">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
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
                    <div class="form-group">
                        <label class="form-label">Parcelas (0 = sem limite)</label>
                        <input type="number" name="parcelas" value="1" min="0" class="form-control">
                    </div>
                    <div class="d-flex align-center">
                        <label class="form-check">
                            <input type="checkbox" name="recorrente" value="1">
                            Recorrente / parcelada
                        </label>
                    </div>
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
                        <label class="form-label">Data da Compra *</label>
                        <input type="date" name="data_compra" id="edit-data_compra" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" name="data_pagamento" id="edit-data_pagamento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quem Comprou</label>
                        <select name="quem_comprou" id="edit-quem_comprou" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Onde Comprou</label>
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
                        <label class="form-label">Forma de Pagamento</label>
                        <select name="forma_pagamento" id="edit-forma_pagamento" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="edit-escopo-container" style="display:none;" class="form-group">
                        <label class="form-label">Escopo da edição</label>
                        <select name="escopo" class="form-control">
                            <option value="apenas_esta">Apenas esta</option>
                            <option value="esta_e_futuras">Esta e futuras</option>
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

@endsection

@push('scripts')
<script>
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
        const opc = confirm('Excluir esta e todas as futuras? (OK = sim, Cancelar = apenas esta)');
        escopo = opc ? 'esta_e_futuras' : 'apenas_esta';
    } else {
        if (!confirm('Excluir esta despesa?')) return;
    }
    const form = document.getElementById('form-excluir-despesa');
    form.action = `/despesas/${id}`;
    document.getElementById('escopo-excluir').value = escopo;
    form.submit();
}
</script>
@endpush
