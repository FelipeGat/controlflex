@extends('layouts.main')
@section('title', 'Categorias')
@section('page-title', 'Categorias')

@section('content')

<div class="section-header mb-4">
    <span></span>
    <button class="btn btn-primary" onclick="openModal('modal-nova-categoria')">
        <i class="fa-solid fa-plus"></i> Nova Categoria
    </button>
</div>

<div class="grid-2">
    {{-- Despesas --}}
    <div class="card">
        <div class="card-title" style="color:#dc2626;">
            <i class="fa-solid fa-arrow-trend-down" style="color:#dc2626;"></i> Despesas
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:40px;">Ícone</th>
                        <th>Nome</th>
                        <th style="width:70px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categorias->where('tipo', 'DESPESA') as $cat)
                        <tr>
                            <td><i class="fa-solid {{ $cat->icone ?? 'fa-tag' }}" style="color:#dc2626;font-size:15px;"></i></td>
                            <td>{{ $cat->nome }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button onclick="editarCategoria({{ $cat->id }}, '{{ addslashes($cat->nome) }}', '{{ $cat->tipo }}', '{{ $cat->icone }}')" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('categorias.destroy', $cat) }}" style="display:inline;" onsubmit="return confirm('Excluir esta categoria?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red">
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
    </div>

    {{-- Receitas --}}
    <div class="card">
        <div class="card-title" style="color:#16a34a;">
            <i class="fa-solid fa-arrow-trend-up" style="color:#16a34a;"></i> Receitas
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:40px;">Ícone</th>
                        <th>Nome</th>
                        <th style="width:70px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categorias->where('tipo', 'RECEITA') as $cat)
                        <tr>
                            <td><i class="fa-solid {{ $cat->icone ?? 'fa-tag' }}" style="color:#16a34a;font-size:15px;"></i></td>
                            <td>{{ $cat->nome }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button onclick="editarCategoria({{ $cat->id }}, '{{ addslashes($cat->nome) }}', '{{ $cat->tipo }}', '{{ $cat->icone }}')" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('categorias.destroy', $cat) }}" style="display:inline;" onsubmit="return confirm('Excluir esta categoria?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red">
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
    </div>
</div>

{{-- Modal Nova --}}
<div class="modal-backdrop" id="modal-nova-categoria">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <i class="fa-solid fa-tags" style="color:var(--color-primary);"></i>
            <h3>Nova Categoria</h3>
            <button class="modal-close" onclick="closeModal('modal-nova-categoria')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('categorias.store') }}">
                @csrf
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Nome da categoria">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-control" required>
                            <option value="DESPESA">Despesa</option>
                            <option value="RECEITA">Receita</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ícone (Font Awesome)</label>
                        <input type="text" name="icone" class="form-control" placeholder="fa-tag, fa-house, fa-car...">
                        <div style="font-size:11px;margin-top:4px;" class="text-subtle">Ex: fa-house, fa-car, fa-utensils, fa-heart, fa-graduation-cap</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-nova-categoria')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-categoria">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary);"></i>
            <h3>Editar Categoria</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-categoria')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-categoria">
                @csrf @method('PUT')
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="cat-edit-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" id="cat-edit-tipo" class="form-control" required>
                            <option value="DESPESA">Despesa</option>
                            <option value="RECEITA">Receita</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ícone</label>
                        <input type="text" name="icone" id="cat-edit-icone" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-categoria')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
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
