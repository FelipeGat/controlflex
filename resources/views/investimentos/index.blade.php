@extends('layouts.main')
@section('title', 'Investimentos')
@section('page-title', 'Investimentos')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
    <div class="card" style="padding:16px 24px;display:inline-block;">
        <div style="font-size:12px;color:#94a3b8;font-weight:700;text-transform:uppercase;">Total Investido</div>
        <div style="font-size:28px;font-weight:800;color:#f59e0b;">R$ {{ number_format($totalInvestido, 2, ',', '.') }}</div>
    </div>
    <button class="btn-primary" style="background:#f59e0b;" onclick="openModal('modal-novo-investimento')">
        <i class="fa-solid fa-plus"></i> Novo Investimento
    </button>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Ativo</th>
                <th>Tipo</th>
                <th>Valor Aportado</th>
                <th>Cotas</th>
                <th>Conta</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($investimentos as $inv)
                <tr>
                    <td>{{ $inv->data_aporte->format('d/m/Y') }}</td>
                    <td><strong>{{ $inv->nome_ativo }}</strong></td>
                    <td><span class="badge badge-warning">{{ $inv->tipo_investimento }}</span></td>
                    <td><strong style="color:#f59e0b;">R$ {{ number_format($inv->valor_aportado, 2, ',', '.') }}</strong></td>
                    <td>{{ $inv->quantidade_cotas > 0 ? number_format($inv->quantidade_cotas, 4) : '—' }}</td>
                    <td>{{ $inv->banco?->nome ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button onclick="editarInvestimento({{ $inv->id }}, {{ $inv->toJson() }})" class="btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="{{ route('investimentos.destroy', $inv) }}" onsubmit="return confirm('Excluir este investimento?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;">
                        <i class="fa-solid fa-seedling" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                        Nenhum investimento registrado
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:16px;">{{ $investimentos->links() }}</div>
</div>

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-investimento">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-seedling" style="color:#f59e0b;"></i> Novo Investimento
            <button onclick="closeModal('modal-novo-investimento')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('investimentos.store') }}">
            @csrf
            <div class="grid-2" style="gap:12px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Nome do Ativo *</label>
                    <input type="text" name="nome_ativo" class="form-control" required placeholder="Ex: Tesouro Selic 2027, XPTO11...">
                </div>
                <div>
                    <label class="form-label">Tipo de Investimento *</label>
                    <input type="text" name="tipo_investimento" class="form-control" required placeholder="Renda Fixa, FII, Ação...">
                </div>
                <div>
                    <label class="form-label">Data do Aporte *</label>
                    <input type="date" name="data_aporte" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label">Valor Aportado *</label>
                    <input type="number" name="valor_aportado" step="0.01" min="0.01" class="form-control" required placeholder="0,00">
                </div>
                <div>
                    <label class="form-label">Quantidade de Cotas</label>
                    <input type="number" name="quantidade_cotas" step="0.000001" min="0" value="0" class="form-control">
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Conta/Corretora</label>
                    <select name="banco_id" class="form-control">
                        <option value="">— Selecione —</option>
                        @foreach($bancos as $b)
                            <option value="{{ $b->id }}">{{ $b->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="2" placeholder="Observações..."></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-novo-investimento')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary" style="background:#f59e0b;"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-investimento">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#f59e0b;"></i> Editar Investimento
            <button onclick="closeModal('modal-editar-investimento')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-investimento">
            @csrf @method('PUT')
            <div class="grid-2" style="gap:12px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Nome do Ativo *</label>
                    <input type="text" name="nome_ativo" id="inv-edit-nome" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Tipo *</label>
                    <input type="text" name="tipo_investimento" id="inv-edit-tipo" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Data do Aporte *</label>
                    <input type="date" name="data_aporte" id="inv-edit-data" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Valor *</label>
                    <input type="number" name="valor_aportado" id="inv-edit-valor" step="0.01" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Cotas</label>
                    <input type="number" name="quantidade_cotas" id="inv-edit-cotas" step="0.000001" min="0" class="form-control">
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Conta</label>
                    <select name="banco_id" id="inv-edit-banco" class="form-control">
                        <option value="">— Nenhuma —</option>
                        @foreach($bancos as $b)
                            <option value="{{ $b->id }}">{{ $b->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="inv-edit-obs" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-investimento')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary" style="background:#f59e0b;"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
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
