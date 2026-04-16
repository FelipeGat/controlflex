@extends('layouts.main')
@section('title', 'Categorias')
@section('page-title', 'Categorias')

@php
$iconesDisponiveis = [
    'fa-house','fa-bolt','fa-faucet','fa-wrench','fa-broom','fa-couch','fa-wifi','fa-door-open',
    'fa-cart-shopping','fa-utensils','fa-apple-whole','fa-pizza-slice','fa-burger','fa-mug-hot','fa-basket-shopping',
    'fa-car','fa-gas-pump','fa-bus','fa-bicycle','fa-plane','fa-motorcycle','fa-taxi','fa-train',
    'fa-heart-pulse','fa-pills','fa-hospital','fa-tooth','fa-dumbbell','fa-spa','fa-stethoscope','fa-syringe',
    'fa-graduation-cap','fa-book','fa-pencil','fa-school','fa-chalkboard-user',
    'fa-film','fa-music','fa-gamepad','fa-camera','fa-umbrella-beach','fa-tv','fa-headphones',
    'fa-shirt','fa-socks','fa-shoe-prints','fa-hat-cowboy',
    'fa-paw','fa-dog','fa-cat','fa-fish',
    'fa-baby','fa-child','fa-people-roof','fa-heart','fa-person',
    'fa-coins','fa-credit-card','fa-wallet','fa-piggy-bank','fa-chart-line','fa-hand-holding-dollar','fa-money-bill-wave','fa-building-columns',
    'fa-briefcase','fa-laptop','fa-building','fa-file-invoice','fa-handshake',
    'fa-tag','fa-gift','fa-star','fa-fire','fa-leaf','fa-recycle','fa-shield-halved','fa-bell','fa-globe',
];
@endphp

@section('content')

<div class="section-header mb-4">
    <span></span>
    <div class="d-flex gap-2">
        @if(Auth::user()->temPermissao('categorias', 'criar'))
        <button class="btn btn-primary" onclick="openModal('modal-nova-categoria')">
            <i class="fa-solid fa-plus"></i> Nova Categoria
        </button>
        @endif
    </div>
</div>

<div class="grid-2">
    {{-- Despesas --}}
    <div class="card">
        <div class="card-title" style="color:var(--color-danger);">
            <i class="fa-solid fa-arrow-trend-down" style="color:var(--color-danger);"></i> Despesas
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
                            <td>
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:var(--color-danger-soft);">
                                    <i class="fa-solid {{ $cat->icone ?? 'fa-tag' }}" style="color:var(--color-danger);font-size:13px;"></i>
                                </span>
                            </td>
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
        <div class="card-title" style="color:var(--color-success);">
            <i class="fa-solid fa-arrow-trend-up" style="color:var(--color-success);"></i> Receitas
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
                            <td>
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:var(--color-success-soft);">
                                    <i class="fa-solid {{ $cat->icone ?? 'fa-tag' }}" style="color:var(--color-success);font-size:13px;"></i>
                                </span>
                            </td>
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
    <div class="modal" style="max-width:520px;">
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
                        <label class="form-label">Ícone</label>
                        <input type="hidden" name="icone" id="novo-icone-val" value="fa-tag">
                        {{-- Prévia do ícone selecionado --}}
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;padding:8px 12px;background:var(--color-bg);border-radius:6px;border:1px solid var(--color-border);">
                            <span style="font-size:11px;color:var(--color-text-muted);">Selecionado:</span>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;background:var(--color-primary);">
                                <i id="novo-icone-preview" class="fa-solid fa-tag" style="color:#fff;font-size:15px;"></i>
                            </span>
                            <span id="novo-icone-label" style="font-size:12px;color:var(--color-text-subtle);">fa-tag</span>
                        </div>
                        {{-- Grade de ícones --}}
                        <div style="max-height:220px;overflow-y:auto;border:1px solid var(--color-border);border-radius:6px;padding:8px;">
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(44px,1fr));gap:4px;" id="grade-icones-novo">
                                @foreach($iconesDisponiveis as $ic)
                                <button type="button"
                                    onclick="selecionarIcone('novo', '{{ $ic }}')"
                                    title="{{ $ic }}"
                                    class="icone-tile"
                                    data-icone="{{ $ic }}"
                                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:44px;height:44px;border-radius:6px;border:1px solid transparent;background:var(--color-bg);cursor:pointer;transition:all .15s;padding:0;">
                                    <i class="fa-solid {{ $ic }}" style="font-size:16px;color:var(--color-text-subtle);"></i>
                                </button>
                                @endforeach
                            </div>
                        </div>
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
    <div class="modal" style="max-width:520px;">
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
                        <input type="hidden" name="icone" id="edit-icone-val" value="fa-tag">
                        {{-- Prévia do ícone selecionado --}}
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;padding:8px 12px;background:var(--color-bg);border-radius:6px;border:1px solid var(--color-border);">
                            <span style="font-size:11px;color:var(--color-text-muted);">Selecionado:</span>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;background:var(--color-primary);">
                                <i id="edit-icone-preview" class="fa-solid fa-tag" style="color:#fff;font-size:15px;"></i>
                            </span>
                            <span id="edit-icone-label" style="font-size:12px;color:var(--color-text-subtle);">fa-tag</span>
                        </div>
                        {{-- Grade de ícones --}}
                        <div style="max-height:220px;overflow-y:auto;border:1px solid var(--color-border);border-radius:6px;padding:8px;">
                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(44px,1fr));gap:4px;" id="grade-icones-edit">
                                @foreach($iconesDisponiveis as $ic)
                                <button type="button"
                                    onclick="selecionarIcone('edit', '{{ $ic }}')"
                                    title="{{ $ic }}"
                                    class="icone-tile"
                                    data-icone="{{ $ic }}"
                                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:44px;height:44px;border-radius:6px;border:1px solid transparent;background:var(--color-bg);cursor:pointer;transition:all .15s;padding:0;">
                                    <i class="fa-solid {{ $ic }}" style="font-size:16px;color:var(--color-text-subtle);"></i>
                                </button>
                                @endforeach
                            </div>
                        </div>
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
<style>
.icone-tile:hover {
    background: var(--color-primary-light, #eff6ff) !important;
    border-color: var(--color-primary) !important;
}
.icone-tile:hover i {
    color: var(--color-primary) !important;
}
.icone-tile.selecionado {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}
.icone-tile.selecionado i {
    color: #fff !important;
}
</style>
<script>
function selecionarIcone(prefixo, icone) {
    // Atualiza o hidden input
    document.getElementById(prefixo + '-icone-val').value = icone;
    // Atualiza a prévia
    document.getElementById(prefixo + '-icone-preview').className = 'fa-solid ' + icone;
    document.getElementById(prefixo + '-icone-label').textContent = icone;
    // Atualiza estado selecionado na grade
    const gradeId = prefixo === 'novo' ? 'grade-icones-novo' : 'grade-icones-edit';
    document.querySelectorAll('#' + gradeId + ' .icone-tile').forEach(btn => {
        btn.classList.toggle('selecionado', btn.dataset.icone === icone);
    });
}

function editarCategoria(id, nome, tipo, icone) {
    document.getElementById('form-editar-categoria').action = `/categorias/${id}`;
    document.getElementById('cat-edit-nome').value = nome;
    document.getElementById('cat-edit-tipo').value = tipo;
    // Pré-seleciona o ícone na grade
    selecionarIcone('edit', icone || 'fa-tag');
    openModal('modal-editar-categoria');
}
</script>
@endpush
