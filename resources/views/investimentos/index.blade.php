@extends('layouts.main')
@section('title', 'Investimentos')
@section('page-title', 'Investimentos')

@section('content')

<div class="section-header mb-4">
    <div class="card" style="padding:12px 18px;display:inline-block;">
        <div class="kpi-label">Total Investido</div>
        <div class="kpi-value text-amber">R$ {{ number_format($totalInvestido, 2, ',', '.') }}</div>
    </div>
    <button class="btn btn-amber" onclick="openModal('modal-novo-investimento')">
        <i class="fa-solid fa-plus"></i> Novo Investimento
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Ativo</th>
                    <th class="hide-mobile">Tipo</th>
                    <th>Valor</th>
                    <th class="hide-mobile">Cotas</th>
                    <th class="hide-mobile">Conta</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($investimentos as $inv)
                    <tr>
                        <td style="white-space:nowrap;">{{ $inv->data_aporte->format('d/m/Y') }}</td>
                        <td><strong>{{ $inv->nome_ativo }}</strong></td>
                        <td class="hide-mobile"><span class="badge badge-amber">{{ $inv->tipo_investimento }}</span></td>
                        <td style="white-space:nowrap;"><strong class="text-amber">R$ {{ number_format($inv->valor_aportado, 2, ',', '.') }}</strong></td>
                        <td class="hide-mobile">{{ $inv->quantidade_cotas > 0 ? number_format($inv->quantidade_cotas, 4) : '—' }}</td>
                        <td class="hide-mobile">{{ $inv->banco?->nome ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button onclick="editarInvestimento({{ $inv->id }}, {{ $inv->toJson() }})" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('investimentos.destroy', $inv) }}" onsubmit="return confirm('Excluir este investimento?')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fa-solid fa-seedling"></i>
                                <p>Nenhum investimento registrado</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $investimentos->links() }}</div>
</div>

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-investimento">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-seedling" style="color:#d97706;"></i>
            <h3>Novo Investimento</h3>
            <button class="modal-close" onclick="closeModal('modal-novo-investimento')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('investimentos.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome do Ativo *</label>
                        <input type="text" name="nome_ativo" class="form-control" required placeholder="Ex: Tesouro Selic 2027, XPTO11...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Investimento *</label>
                        <input type="text" name="tipo_investimento" class="form-control" required placeholder="Renda Fixa, FII, Ação...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Aporte *</label>
                        <input type="date" name="data_aporte" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor Aportado *</label>
                        <input type="number" name="valor_aportado" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantidade de Cotas</label>
                        <input type="number" name="quantidade_cotas" step="0.000001" min="0" value="0" class="form-control">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Conta / Corretora</label>
                        <select name="banco_id" class="form-control">
                            <option value="">— Selecione —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-novo-investimento')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-amber"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-investimento">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:#d97706;"></i>
            <h3>Editar Investimento</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-investimento')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-investimento">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome do Ativo *</label>
                        <input type="text" name="nome_ativo" id="inv-edit-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <input type="text" name="tipo_investimento" id="inv-edit-tipo" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data do Aporte *</label>
                        <input type="date" name="data_aporte" id="inv-edit-data" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor *</label>
                        <input type="number" name="valor_aportado" id="inv-edit-valor" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cotas</label>
                        <input type="number" name="quantidade_cotas" id="inv-edit-cotas" step="0.000001" min="0" class="form-control">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Conta</label>
                        <select name="banco_id" id="inv-edit-banco" class="form-control">
                            <option value="">— Nenhuma —</option>
                            @foreach($bancos as $b)
                                <option value="{{ $b->id }}">{{ $b->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="inv-edit-obs" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-investimento')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-amber"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function editarInvestimento(id, data) {
    document.getElementById('form-editar-investimento').action = `/investimentos/${id}`;
    document.getElementById('inv-edit-nome').value = data.nome_ativo;
    document.getElementById('inv-edit-tipo').value = data.tipo_investimento;
    document.getElementById('inv-edit-data').value = data.data_aporte ? data.data_aporte.substring(0,10) : '';
    document.getElementById('inv-edit-valor').value = data.valor_aportado;
    document.getElementById('inv-edit-cotas').value = data.quantidade_cotas || 0;
    document.getElementById('inv-edit-banco').value = data.banco_id || '';
    document.getElementById('inv-edit-obs').value = data.observacoes || '';
    openModal('modal-editar-investimento');
}
</script>
@endpush
