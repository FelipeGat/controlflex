@extends('layouts.main')
@section('title', 'Clientes')
@section('page-title', 'Meus Clientes')

@section('content')
<div class="section-header">
    <h2><i class="fa-solid fa-store" style="color:var(--color-primary)"></i> Clientes</h2>
    <button class="btn btn-primary" onclick="openModal('modal-novo-cliente')">
        <i class="fa-solid fa-plus"></i> Novo Cliente
    </button>
</div>

<div class="card">
    @if($clientes->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-store"></i>
            <p>Nenhum cliente cadastrado ainda.<br>Crie o primeiro cliente para ele usar o sistema.</p>
        </div>
    @else
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Master</th>
                    <th>Usuários</th>
                    <th>Status</th>
                    <th>Criado em</th>
                    <th style="width:130px">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $cliente)
                <tr>
                    <td class="fw-600">{{ $cliente->nome }}</td>
                    <td class="text-muted">{{ $cliente->master?->email ?? '—' }}</td>
                    <td>{{ $cliente->users_count }}</td>
                    <td>
                        @if($cliente->status === 'ativo')
                            <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px"></i> Ativo</span>
                        @else
                            <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inativo</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $cliente->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-secondary btn-sm btn-icon"
                                onclick="editarCliente({{ $cliente->id }}, '{{ addslashes($cliente->nome) }}', '{{ $cliente->status }}')"
                                title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-secondary btn-sm btn-icon"
                                onclick="resetSenhaCliente({{ $cliente->id }}, '{{ addslashes($cliente->nome) }}')"
                                title="Reset Senha">
                                <i class="fa-solid fa-key"></i>
                            </button>
                            <form method="POST" action="{{ route('revenda.clientes.destroy', $cliente) }}" onsubmit="return confirm('Remover este cliente e todos seus dados?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Remover">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Modal Novo Cliente --}}
<div class="modal-backdrop" id="modal-novo-cliente">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <i class="fa-solid fa-plus" style="color:var(--color-primary)"></i>
            <h3>Novo Cliente</h3>
            <button class="modal-close" onclick="closeModal('modal-novo-cliente')">×</button>
        </div>
        <form method="POST" action="{{ route('revenda.clientes.store') }}">
            @csrf
            <div class="modal-body">
                <div style="font-size:12px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;margin-bottom:8px;">Dados do Cliente</div>
                <div class="form-group mb-3">
                    <label class="form-label">Nome do Cliente *</label>
                    <input type="text" name="nome_cliente" class="form-control" required>
                </div>

                <div style="font-size:12px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;margin:16px 0 8px;">Usuário Master</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome_master" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email_master" class="form-control" required>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Senha *</label>
                        <input type="password" name="senha_master" class="form-control" required minlength="8">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-novo-cliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Criar Cliente</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar Cliente --}}
<div class="modal-backdrop" id="modal-editar-cliente">
    <div class="modal" style="max-width:450px">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary)"></i>
            <h3>Editar Cliente</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-cliente')">×</button>
        </div>
        <form method="POST" id="form-editar-cliente" action="">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" id="edit-cliente-nome" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit-cliente-status" class="form-control">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-editar-cliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Reset Senha --}}
<div class="modal-backdrop" id="modal-reset-senha-cliente">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <i class="fa-solid fa-key" style="color:var(--color-warning)"></i>
            <h3>Redefinir Senha</h3>
            <button class="modal-close" onclick="closeModal('modal-reset-senha-cliente')">×</button>
        </div>
        <form method="POST" id="form-reset-senha-cliente" action="">
            @csrf
            <div class="modal-body">
                <p class="text-muted mb-3" id="reset-senha-cliente-info" style="font-size:13px;"></p>
                <div class="form-group">
                    <label class="form-label">Nova Senha *</label>
                    <input type="password" name="nova_senha" class="form-control" required minlength="8">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-reset-senha-cliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Redefinir</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editarCliente(id, nome, status) {
    document.getElementById('edit-cliente-nome').value = nome;
    document.getElementById('edit-cliente-status').value = status;
    document.getElementById('form-editar-cliente').action = '/revenda/clientes/' + id;
    openModal('modal-editar-cliente');
}

function resetSenhaCliente(id, nome) {
    document.getElementById('reset-senha-cliente-info').textContent = 'Redefinir senha do master do cliente: ' + nome;
    document.getElementById('form-reset-senha-cliente').action = '/revenda/clientes/' + id + '/reset-senha';
    openModal('modal-reset-senha-cliente');
}
</script>
@endpush
