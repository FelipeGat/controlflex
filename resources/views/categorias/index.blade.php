@extends('layouts.main')
@section('title', 'Categorias')
@section('page-title', 'Categorias')

@section('content')
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button class="btn-primary" onclick="openModal('modal-nova-categoria')">
        <i class="fa-solid fa-plus"></i> Nova Categoria
    </button>
</div>

<div class="grid-2">
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#ef4444;margin-bottom:16px;">
            <i class="fa-solid fa-arrow-trend-down"></i> Despesas
        </div>
        <table class="table">
            <thead><tr><th>Ícone</th><th>Nome</th><th>Ações</th></tr></thead>
            <tbody>
                @foreach($categorias->where('tipo', 'DESPESA') as $cat)
                    <tr>
                        <td><i class="fa-solid {{ $cat->icone ?? 'fa-tag' }}" style="color:#ef4444;"></i></td>
                        <td>{{ $cat->nome }}</td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button onclick="editarCategoria({{ $cat->id }}, '{{ $cat->nome }}', '{{ $cat->tipo }}', '{{ $cat->icone }}')" class="btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="{{ route('categorias.destroy', $cat) }}" style="display:inline;" onsubmit="return confirm('Excluir esta categoria?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card">
        <div style="font-size:15px;font-weight:700;color:#10b981;margin-bottom:16px;">
            <i class="fa-solid fa-arrow-trend-up"></i> Receitas
        </div>
        <table class="table">
            <thead><tr><th>Ícone</th><th>Nome</th><th>Ações</th></tr></thead>
            <tbody>
                @foreach($categorias->where('tipo', 'RECEITA') as $cat)
                    <tr>
                        <td><i class="fa-solid {{ $cat->icone ?? 'fa-tag' }}" style="color:#10b981;"></i></td>
                        <td>{{ $cat->nome }}</td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button onclick="editarCategoria({{ $cat->id }}, '{{ $cat->nome }}', '{{ $cat->tipo }}', '{{ $cat->icone }}')" class="btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="{{ route('categorias.destroy', $cat) }}" style="display:inline;" onsubmit="return confirm('Excluir esta categoria?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Nova --}}
<div class="modal-backdrop" id="modal-nova-categoria">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-tags" style="color:#6366f1;"></i> Nova Categoria
            <button onclick="closeModal('modal-nova-categoria')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('categorias.store') }}">
            @csrf
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required placeholder="Nome da categoria">
                </div>
                <div>
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" class="form-control" required>
                        <option value="DESPESA">Despesa</option>
                        <option value="RECEITA">Receita</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Ícone (Font Awesome)</label>
                    <input type="text" name="icone" class="form-control" placeholder="fa-tag, fa-home, fa-car...">
                    <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Ex: fa-house, fa-car, fa-utensils, fa-heart</div>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-nova-categoria')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-categoria">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#6366f1;"></i> Editar Categoria
            <button onclick="closeModal('modal-editar-categoria')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-categoria">
            @csrf @method('PUT')
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" id="cat-edit-nome" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Tipo *</label>
                    <select name="tipo" id="cat-edit-tipo" class="form-control" required>
                        <option value="DESPESA">Despesa</option>
                        <option value="RECEITA">Receita</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Ícone</label>
                    <input type="text" name="icone" id="cat-edit-icone" class="form-control">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-categoria')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editarCategoria(id, nome, tipo, icone) {
    document.getElementById('form-editar-categoria').action = `/categorias/${id}`;
    document.getElementById('cat-edit-nome').value = nome;
    document.getElementById('cat-edit-tipo').value = tipo;
    document.getElementById('cat-edit-icone').value = icone || '';
    openModal('modal-editar-categoria');
}
</script>
@endpush
