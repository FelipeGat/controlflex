@extends('layouts.main')

@section('title', 'Receitas')
@section('page-title', 'Receitas')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <form method="GET" action="{{ route('receitas.index') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="date" name="inicio" value="{{ $inicio }}" class="form-control" style="width:150px;">
        <input type="date" name="fim" value="{{ $fim }}" class="form-control" style="width:150px;">
        <button type="submit" class="btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
        <a href="{{ route('receitas.index') }}" class="btn-secondary">Mês Atual</a>
    </form>
    <button class="btn-primary" style="background:#10b981;" onclick="openModal('modal-nova-receita')">
        <i class="fa-solid fa-plus"></i> Nova Receita
    </button>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div style="font-size:15px;font-weight:700;color:#1e293b;">
            <i class="fa-solid fa-arrow-trend-up" style="color:#10b981;"></i>
            {{ $receitas->total() }} receita(s)
        </div>
        <div style="font-size:16px;font-weight:800;color:#10b981;">
            Total: R$ {{ number_format($totalValor, 2, ',', '.') }}
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Data Prevista</th>
                    <th>Valor</th>
                    <th>Categoria</th>
                    <th>Quem Recebeu</th>
                    <th>Conta</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receitas as $receita)
                    <tr>
                        <td>{{ $receita->data_prevista_recebimento->format('d/m/Y') }}</td>
                        <td><strong style="color:#10b981;">R$ {{ number_format($receita->valor, 2, ',', '.') }}</strong></td>
                        <td>
                            @if($receita->categoria)
                                <span class="badge badge-info">{{ $receita->categoria->nome }}</span>
                            @else
                                <span class="badge badge-gray">Sem categoria</span>
                            @endif
                        </td>
                        <td>{{ $receita->familiar?->nome ?? '—' }}</td>
                        <td>{{ $receita->banco?->nome ?? '—' }}</td>
                        <td>
                            @if($receita->data_recebimento)
                                <span class="badge badge-success"><i class="fa-solid fa-check"></i> Recebido</span>
                            @else
                                <span class="badge badge-warning">Pendente</span>
                            @endif
                            @if($receita->recorrente)
                                <span class="badge badge-gray" style="margin-left:4px;"><i class="fa-solid fa-rotate"></i></span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button onclick="editarReceita({{ $receita->id }}, {{ $receita->toJson() }})" class="btn-secondary btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="excluirReceita({{ $receita->id }}, {{ $receita->grupo_recorrencia_id ? 'true' : 'false' }})" class="btn-danger btn-sm" title="Excluir">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">
                            <i class="fa-solid fa-inbox" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                            Nenhuma receita encontrada no período
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">{{ $receitas->links() }}</div>
</div>

{{-- Modal Nova Receita --}}
<div class="modal-backdrop" id="modal-nova-receita">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-plus" style="color:#10b981;"></i> Nova Receita
            <button onclick="closeModal('modal-nova-receita')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('receitas.store') }}">
            @csrf
            <div class="grid-2" style="gap:12px;">
                <div>
                    <label class="form-label">Valor *</label>
                    <input type="number" name="valor" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                </div>
                <div>
                    <label class="form-label">Data Prevista *</label>
                    <input type="date" name="data_prevista_recebimento" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Data do Recebimento</label>
                    <input type="date" name="data_recebimento" class="form-control">
                </div>
                <div>
                    <label class="form-label">Quem Recebeu</label>
                    <select name="quem_recebeu" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($familiares as $f)
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
                    <label class="form-label">Conta de Recebimento</label>
                    <select name="forma_recebimento" class="form-control">
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
                <div>
                    <label class="form-label">Parcelas (0 = sem limite)</label>
                    <input type="number" name="parcelas" value="1" min="0" class="form-control">
                </div>
                <div style="grid-column:span 2;display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="recorrente" id="recorrente-r-new" value="1">
                    <label for="recorrente-r-new" style="font-size:14px;cursor:pointer;">Receita recorrente/parcelada</label>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-nova-receita')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary" style="background:#10b981;"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar Receita --}}
<div class="modal-backdrop" id="modal-editar-receita">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#10b981;"></i> Editar Receita
            <button onclick="closeModal('modal-editar-receita')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-receita">
            @csrf @method('PUT')
            <div class="grid-2" style="gap:12px;">
                <div>
                    <label class="form-label">Valor *</label>
                    <input type="number" name="valor" id="r-edit-valor" step="0.01" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Data Prevista *</label>
                    <input type="date" name="data_prevista_recebimento" id="r-edit-dpr" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Data do Recebimento</label>
                    <input type="date" name="data_recebimento" id="r-edit-dr" class="form-control">
                </div>
                <div>
                    <label class="form-label">Quem Recebeu</label>
                    <select name="quem_recebeu" id="r-edit-quem" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($familiares as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" id="r-edit-cat" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Conta</label>
                    <select name="forma_recebimento" id="r-edit-banco" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($bancos as $b)
                            <option value="{{ $b->id }}">{{ $b->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="r-edit-escopo-container" style="display:none;grid-column:span 2;">
                    <label class="form-label">Escopo da edição</label>
                    <select name="escopo" class="form-control">
                        <option value="apenas_esta">Apenas esta</option>
                        <option value="esta_e_futuras">Esta e futuras</option>
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="r-edit-obs" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-receita')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary" style="background:#10b981;"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
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
