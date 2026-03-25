@extends('layouts.main')
@section('title', 'Fornecedores')
@section('page-title', 'Fornecedores')

@section('content')

<div class="section-header mb-4">
    <span></span>
    <button class="btn btn-primary" onclick="openModal('modal-novo-fornecedor')">
        <i class="fa-solid fa-plus"></i> Novo Fornecedor
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th class="hide-mobile">Contato</th>
                    <th class="hide-mobile">CNPJ</th>
                    <th class="hide-mobile">Telefone</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($fornecedores as $forn)
                    <tr>
                        <td><strong>{{ $forn->nome }}</strong></td>
                        <td class="hide-mobile">{{ $forn->contato ?? '—' }}</td>
                        <td class="hide-mobile">{{ $forn->cnpj ?? '—' }}</td>
                        <td class="hide-mobile">{{ $forn->telefone ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button onclick="editarFornecedor({{ $forn->id }}, {{ $forn->toJson() }})" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('fornecedores.destroy', $forn) }}" onsubmit="return confirm('Excluir este fornecedor?')" style="display:inline;">
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
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fa-solid fa-store"></i>
                                <p>Nenhum fornecedor cadastrado</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $fornecedores->links() }}</div>
</div>

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-fornecedor">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-store" style="color:var(--color-primary);"></i>
            <h3>Novo Fornecedor</h3>
            <button class="modal-close" onclick="closeModal('modal-novo-fornecedor')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('fornecedores.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Nome do fornecedor / loja">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contato</label>
                        <input type="text" name="contato" class="form-control" placeholder="Nome do contato">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="3" placeholder="Observações..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-novo-fornecedor')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-fornecedor">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary);"></i>
            <h3>Editar Fornecedor</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-fornecedor')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-fornecedor">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="forn-edit-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contato</label>
                        <input type="text" name="contato" id="forn-edit-contato" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="forn-edit-telefone" class="form-control">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" id="forn-edit-cnpj" class="form-control">
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" id="forn-edit-obs" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-fornecedor')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
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
