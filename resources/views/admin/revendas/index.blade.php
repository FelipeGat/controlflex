@extends('layouts.main')
@section('title', 'Revendas')
@section('page-title', 'Revendas')

@section('content')
<div class="section-header">
    <h2><i class="fa-solid fa-building" style="color:var(--color-primary)"></i> Revendas</h2>
    <button class="btn btn-primary" onclick="openModal('modal-provisionar')">
        <i class="fa-solid fa-rocket"></i> Provisionar Revenda
    </button>
</div>

<div class="card">
    @if($revendas->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-building"></i>
            <p>Nenhuma revenda cadastrada ainda.<br>Provisione a primeira revenda para começar.</p>
        </div>
    @else
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th class="hide-mobile">CNPJ</th>
                    <th class="hide-mobile">E-mail</th>
                    <th>Status</th>
                    <th class="hide-mobile">Clientes</th>
                    <th class="hide-mobile">Admin</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revendas as $revenda)
                <tr>
                    <td class="fw-600">{{ $revenda->nome }}</td>
                    <td class="text-muted hide-mobile">{{ $revenda->cnpj ?? '—' }}</td>
                    <td class="text-muted hide-mobile">{{ $revenda->email ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.revendas.update', $revenda) }}" style="display:inline;">
                            @csrf @method('PUT')
                            <input type="hidden" name="nome" value="{{ $revenda->nome }}">
                            <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto;padding:4px 8px;font-size:12px;">
                                <option value="ativo" {{ $revenda->status === 'ativo' ? 'selected' : '' }}>Ativo</option>
                                <option value="inativo" {{ $revenda->status === 'inativo' ? 'selected' : '' }}>Inativo</option>
                            </select>
                        </form>
                    </td>
                    <td class="hide-mobile">{{ $revenda->tenants_count }}</td>
                    <td class="text-muted hide-mobile">{{ $revenda->admin?->email ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-secondary btn-sm btn-icon"
                                onclick="editarRevenda({{ $revenda->id }}, '{{ addslashes($revenda->nome) }}', '{{ addslashes($revenda->cnpj ?? '') }}', '{{ addslashes($revenda->email ?? '') }}', '{{ addslashes($revenda->telefone ?? '') }}')"
                                title="Editar">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-secondary btn-sm btn-icon"
                                onclick="resetSenhaRevenda({{ $revenda->id }}, '{{ addslashes($revenda->nome) }}')"
                                title="Reset Senha">
                                <i class="fa-solid fa-key"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.revendas.destroy', $revenda) }}" onsubmit="return confirm('Remover esta revenda e todos seus dados?')">
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

{{-- Modal Provisionar --}}
<div class="modal-backdrop" id="modal-provisionar">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <i class="fa-solid fa-rocket" style="color:var(--color-primary)"></i>
            <h3>Provisionar Nova Revenda</h3>
            <button class="modal-close" onclick="closeModal('modal-provisionar')">×</button>
        </div>
        <form method="POST" action="{{ route('admin.revendas.provisionar') }}">
            @csrf
            <div class="modal-body">
                <div style="font-size:12px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;margin-bottom:8px;">Dados da Revenda</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome da Revenda *</label>
                        <input type="text" name="nome_revenda" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email_revenda" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control">
                    </div>

                <div style="font-size:12px;font-weight:700;color:var(--color-text-subtle);text-transform:uppercase;margin:16px 0 8px;">Administrador da Revenda</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome_admin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email_admin" class="form-control" required>
                    </div>
                    <div class="form-group span-2">
                        <label class="form-label">Senha *</label>
                        <input type="password" name="senha_admin" class="form-control" required minlength="8">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-provisionar')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-rocket"></i> Provisionar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar Revenda --}}
<div class="modal-backdrop" id="modal-editar-revenda">
    <div class="modal" style="max-width:550px">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary)"></i>
            <h3>Editar Revenda</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-revenda')">×</button>
        </div>
        <form method="POST" id="form-editar-revenda" action="">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="edit-revenda-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" id="edit-revenda-cnpj" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" id="edit-revenda-email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="edit-revenda-telefone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit-revenda-status" class="form-control">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-editar-revenda')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Reset Senha --}}
<div class="modal-backdrop" id="modal-reset-senha">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <i class="fa-solid fa-key" style="color:var(--color-warning)"></i>
            <h3>Redefinir Senha</h3>
            <button class="modal-close" onclick="closeModal('modal-reset-senha')">×</button>
        </div>
        <form method="POST" id="form-reset-senha" action="">
            @csrf
            <div class="modal-body">
                <p class="text-muted mb-3" id="reset-senha-info" style="font-size:13px;"></p>
                <div class="form-group">
                    <label class="form-label">Nova Senha *</label>
                    <input type="password" name="nova_senha" class="form-control" required minlength="8">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-reset-senha')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Redefinir</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editarRevenda(id, nome, cnpj, email, telefone) {
    document.getElementById('edit-revenda-nome').value = nome;
    document.getElementById('edit-revenda-cnpj').value = cnpj;
    document.getElementById('edit-revenda-email').value = email;
    document.getElementById('edit-revenda-telefone').value = telefone;
    document.getElementById('form-editar-revenda').action = '/admin/revendas/' + id;
    openModal('modal-editar-revenda');
}

function resetSenhaRevenda(id, nome) {
    document.getElementById('reset-senha-info').textContent = 'Redefinir senha do administrador da revenda: ' + nome;
    document.getElementById('form-reset-senha').action = '/admin/revendas/' + id + '/reset-senha';
    openModal('modal-reset-senha');
}
</script>
@endpush
