@extends('layouts.main')
@section('title', 'Receitas')
@section('page-title', 'Receitas')

@section('content')

<div class="section-header mb-4">
    <form method="GET" action="{{ route('receitas.index') }}" class="d-flex flex-wrap align-center gap-2">
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="width:135px;">
        <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="width:135px;">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
        <a href="{{ route('receitas.index') }}" class="btn btn-secondary btn-sm">Mês Atual</a>
    </form>
    <button class="btn btn-success" onclick="openModal('modal-nova-receita')">
        <i class="fa-solid fa-plus"></i> Nova Receita
    </button>
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
                    <th class="hide-mobile">Conta</th>
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
                        <td class="hide-mobile">{{ $receita->banco?->nome ?? '—' }}</td>
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
                            <option value="">— Selecione —</option>
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
                            <option value="">— Selecione —</option>
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
                    <div id="r-edit-escopo-container" style="display:none;" class="form-group span-2">
                        <label class="form-label">Escopo da edição</label>
                        <select name="escopo" class="form-control">
                            <option value="apenas_esta">Apenas esta</option>
                            <option value="esta_e_futuras">Esta e futuras</option>
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

@endsection

@push('scripts')
<script>
function editarReceita(id, data) {
    document.getElementById('form-editar-receita').action = `/receitas/${id}`;
    document.getElementById('r-edit-valor').value = data.valor;
    document.getElementById('r-edit-dpr').value = data.data_prevista_recebimento ? data.data_prevista_recebimento.substring(0,10) : '';
    document.getElementById('r-edit-dr').value = data.data_recebimento ? data.data_recebimento.substring(0,10) : '';
    document.getElementById('r-edit-quem').value = data.quem_recebeu || '';
    document.getElementById('r-edit-cat').value = data.categoria_id || '';
    document.getElementById('r-edit-banco').value = data.forma_recebimento || '';
    document.getElementById('r-edit-obs').value = data.observacoes || '';
    document.getElementById('r-edit-escopo-container').style.display = data.grupo_recorrencia_id ? 'block' : 'none';
    openModal('modal-editar-receita');
}
function excluirReceita(id, isRecorrente) {
    let escopo = 'apenas_esta';
    if (isRecorrente) {
        escopo = confirm('Excluir esta e todas as futuras?') ? 'esta_e_futuras' : 'apenas_esta';
    } else {
        if (!confirm('Excluir esta receita?')) return;
    }
    const form = document.getElementById('form-excluir-receita');
    form.action = `/receitas/${id}`;
    document.getElementById('r-escopo-excluir').value = escopo;
    form.submit();
}
</script>
@endpush
