@extends('layouts.main')
@section('title', 'Fornecedores')
@section('page-title', 'Fornecedores')

@section('content')
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button class="btn-primary" onclick="openModal('modal-novo-fornecedor')">
        <i class="fa-solid fa-plus"></i> Novo Fornecedor
    </button>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Contato</th>
                <th>CNPJ</th>
                <th>Telefone</th>
                <th>Observações</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fornecedores as $forn)
                <tr>
                    <td><strong>{{ $forn->nome }}</strong></td>
                    <td>{{ $forn->contato ?? '—' }}</td>
                    <td>{{ $forn->cnpj ?? '—' }}</td>
                    <td>{{ $forn->telefone ?? '—' }}</td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $forn->observacoes ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button onclick="editarFornecedor({{ $forn->id }}, {{ $forn->toJson() }})" class="btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></button>
                            <form method="POST" action="{{ route('fornecedores.destroy', $forn) }}" onsubmit="return confirm('Excluir este fornecedor?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">
                        <i class="fa-solid fa-store" style="font-size:32px;display:block;margin-bottom:8px;"></i>
                        Nenhum fornecedor cadastrado
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:16px;">{{ $fornecedores->links() }}</div>
</div>

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-fornecedor">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-store" style="color:#6366f1;"></i> Novo Fornecedor
            <button onclick="closeModal('modal-novo-fornecedor')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('fornecedores.store') }}">
            @csrf
            <div class="grid-2" style="gap:12px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required placeholder="Nome do fornecedor / loja">
                </div>
                <div>
                    <label class="form-label">Contato</label>
                    <input type="text" name="contato" class="form-control" placeholder="Nome do contato">
                </div>
                <div>
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000">
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">CNPJ</label>
                    <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00">
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="3" placeholder="Observações..."></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-novo-fornecedor')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-fornecedor">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#6366f1;"></i> Editar Fornecedor
            <button onclick="closeModal('modal-editar-fornecedor')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-fornecedor">
            @csrf @method('PUT')
            <div class="grid-2" style="gap:12px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" id="forn-edit-nome" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Contato</label>
                    <input type="text" name="contato" id="forn-edit-contato" class="form-control">
                </div>
                <div>
                    <label class="form-label">Telefone</label>
                    <input type="text" name="telefone" id="forn-edit-telefone" class="form-control">
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">CNPJ</label>
                    <input type="text" name="cnpj" id="forn-edit-cnpj" class="form-control">
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="forn-edit-obs" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-fornecedor')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editarFornecedor(id, data) {
    document.getElementById('form-editar-fornecedor').action = `/fornecedores/${id}`;
    document.getElementById('forn-edit-nome').value = data.nome;
    document.getElementById('forn-edit-contato').value = data.contato || '';
    document.getElementById('forn-edit-telefone').value = data.telefone || '';
    document.getElementById('forn-edit-cnpj').value = data.cnpj || '';
    document.getElementById('forn-edit-obs').value = data.observacoes || '';
    openModal('modal-editar-fornecedor');
}
</script>
@endpush
