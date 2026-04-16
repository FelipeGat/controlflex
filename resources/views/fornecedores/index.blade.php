@extends('layouts.main')
@section('title', 'Fornecedores')
@section('page-title', 'Fornecedores')

@section('content')

<div class="section-header mb-4">
    <div style="position:relative;flex:1;max-width:320px;min-width:0;width:100%;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--color-text-subtle);font-size:13px;pointer-events:none;"></i>
        <input type="text" id="busca-fornecedor" placeholder="Buscar fornecedor..." autocomplete="off"
            style="width:100%;padding:8px 10px 8px 32px;border:1px solid var(--color-border);border-radius:var(--radius-btn);font-size:13px;background:var(--color-bg-card);color:var(--color-text);">
    </div>
    <div class="d-flex gap-2">
        @if(Auth::user()->temPermissao('fornecedores', 'criar'))
        <button class="btn btn-primary" onclick="openModal('modal-novo-fornecedor')">
            <i class="fa-solid fa-plus"></i> Novo Fornecedor
        </button>
        @endif
    </div>
</div>

@if($fornecedores->isEmpty())
<div class="card">
    <div class="empty-state">
        <i class="fa-solid fa-store"></i>
        <p>Nenhum fornecedor cadastrado.<br>Clique em <strong>Novo Fornecedor</strong> para começar.</p>
    </div>
</div>
@else

{{-- Aviso de busca ativa --}}
<div id="aviso-busca" style="display:none;margin-bottom:12px;padding:8px 14px;background:var(--color-warning-soft);border:1px solid var(--color-amber);border-radius:6px;font-size:12px;color:var(--color-amber);">
    <i class="fa-solid fa-magnifying-glass"></i>
    Mostrando resultados para: <strong id="termo-busca-label"></strong>
    — <a href="#" onclick="limparBusca();return false;" style="color:var(--color-amber);">limpar</a>
</div>

{{-- Grade de grupos --}}
<div id="lista-grupos" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(260px,100%),1fr));gap:16px;">
@foreach($fornecedores->sortKeys() as $grupo => $itens)
@php
    $iconeGrupo = match($grupo) {
        'Supermercados'    => 'fa-cart-shopping',
        'Farmácias'        => 'fa-pills',
        'Combustível'      => 'fa-gas-pump',
        'Alimentação'      => 'fa-utensils',
        'Streaming'        => 'fa-tv',
        'Telecom'          => 'fa-signal',
        'Bancos'           => 'fa-building-columns',
        'Energia e Água'   => 'fa-bolt',
        'Academia e Saúde' => 'fa-dumbbell',
        'Moda'             => 'fa-shirt',
        'Varejo e Eletro'  => 'fa-store',
        'Seguros'          => 'fa-shield-halved',
        'Transporte'       => 'fa-car',
        'Educação'         => 'fa-graduation-cap',
        default            => 'fa-tag',
    };
    $grupoSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower($grupo));
@endphp

<div class="grupo-bloco" data-grupo="{{ strtolower($grupo) }}">
    {{-- Card do grupo (clicável) --}}
    <div class="card grupo-card" onclick="toggleGrupo('{{ $grupoSlug }}')"
         style="cursor:pointer;padding:16px 18px;display:flex;align-items:center;gap:14px;transition:box-shadow .15s;user-select:none;">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:10px;background:var(--color-primary);flex-shrink:0;">
            <i class="fa-solid {{ $iconeGrupo }}" style="color:#fff;font-size:18px;"></i>
        </span>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:14px;color:var(--color-text);">{{ $grupo }}</div>
            <div class="contador-grupo" style="font-size:12px;color:var(--color-text-subtle);margin-top:2px;">
                {{ $itens->count() }} fornecedor{{ $itens->count() !== 1 ? 'es' : '' }}
            </div>
        </div>
        <i id="chevron-{{ $grupoSlug }}" class="fa-solid fa-chevron-down" style="color:var(--color-text-subtle);font-size:12px;transition:transform .2s;flex-shrink:0;"></i>
    </div>

    {{-- Fornecedores do grupo (expandível) --}}
    <div id="grupo-itens-{{ $grupoSlug }}" style="display:none;margin-top:8px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(200px,100%),1fr));gap:8px;">
            @foreach($itens as $forn)
            <div class="card-fornecedor"
                 data-nome="{{ strtolower($forn->nome) }}"
                 data-grupo="{{ strtolower($grupo) }}"
                 style="background:var(--color-bg-card);border:1px solid var(--color-border);border-radius:8px;padding:10px 12px;display:flex;align-items:center;gap:10px;position:relative;">

                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;background:var(--color-bg-inset);flex-shrink:0;">
                    <i class="fa-solid {{ $forn->icone ?? 'fa-store' }}" style="color:var(--color-primary);font-size:14px;"></i>
                </span>

                <div style="min-width:0;flex:1;">
                    <div style="font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $forn->nome }}</div>
                    @if($forn->telefone)
                    <div style="font-size:11px;color:var(--color-text-subtle);">{{ $forn->telefone }}</div>
                    @endif
                </div>

                <div style="display:flex;gap:4px;flex-shrink:0;">
                    @if(Auth::user()->temPermissao('fornecedores', 'editar'))
                    <button onclick="event.stopPropagation();editarFornecedor({{ $forn->id }}, {{ $forn->toJson() }})"
                        class="btn btn-ghost btn-icon btn-sm" title="Editar" style="width:26px;height:26px;padding:0;">
                        <i class="fa-solid fa-pen" style="font-size:11px;"></i>
                    </button>
                    @endif
                    @if(Auth::user()->temPermissao('fornecedores', 'excluir'))
                    <form method="POST" action="{{ route('fornecedores.destroy', $forn) }}"
                        onsubmit="event.stopPropagation();return confirm('Excluir {{ addslashes($forn->nome) }}?')" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red"
                            title="Excluir" style="width:26px;height:26px;padding:0;" onclick="event.stopPropagation()">
                            <i class="fa-solid fa-trash" style="font-size:11px;"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach
</div>

@endif

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-fornecedor">
    <div class="modal" style="max-width:480px;">
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
                        <label class="form-label">Grupo / Setor</label>
                        <input type="text" name="grupo" class="form-control" list="lista-grupos-novo" placeholder="Ex: Supermercados, Farmácias...">
                        <datalist id="lista-grupos-novo">
                            @foreach($fornecedores->sortKeys()->keys() as $g)
                            <option value="{{ $g }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ícone (Font Awesome)</label>
                        <input type="text" name="icone" id="novo-icone-input" class="form-control" placeholder="fa-store" value="fa-store"
                            oninput="document.getElementById('novo-icone-preview').className='fa-solid '+this.value">
                    </div>
                    <div class="form-group span-2" style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--color-bg);border-radius:6px;">
                        <span style="font-size:12px;color:var(--color-text-muted);">Prévia:</span>
                        <i id="novo-icone-preview" class="fa-solid fa-store" style="font-size:20px;color:var(--color-primary);"></i>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contato</label>
                        <input type="text" name="contato" class="form-control" placeholder="Nome do contato">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observações</label>
                        <input type="text" name="observacoes" class="form-control" placeholder="Observações...">
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
    <div class="modal" style="max-width:480px;">
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
                        <label class="form-label">Grupo / Setor</label>
                        <input type="text" name="grupo" id="forn-edit-grupo" class="form-control" list="lista-grupos-edit">
                        <datalist id="lista-grupos-edit">
                            @foreach($fornecedores->sortKeys()->keys() as $g)
                            <option value="{{ $g }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ícone (Font Awesome)</label>
                        <input type="text" name="icone" id="forn-edit-icone" class="form-control"
                            oninput="document.getElementById('edit-icone-preview').className='fa-solid '+this.value">
                    </div>
                    <div class="form-group span-2" style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--color-bg);border-radius:6px;">
                        <span style="font-size:12px;color:var(--color-text-muted);">Prévia:</span>
                        <i id="edit-icone-preview" class="fa-solid fa-store" style="font-size:20px;color:var(--color-primary);"></i>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="forn-edit-telefone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" id="forn-edit-cnpj" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contato</label>
                        <input type="text" name="contato" id="forn-edit-contato" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observações</label>
                        <input type="text" name="observacoes" id="forn-edit-obs" class="form-control">
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
<style>
.grupo-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
</style>
<script>
// Estado dos grupos abertos
const gruposAbertos = {};

function toggleGrupo(slug) {
    const itens   = document.getElementById('grupo-itens-' + slug);
    const chevron = document.getElementById('chevron-' + slug);
    if (!itens) return;

    const aberto = itens.style.display !== 'none';
    itens.style.display   = aberto ? 'none' : '';
    chevron.style.transform = aberto ? '' : 'rotate(180deg)';
    gruposAbertos[slug] = !aberto;
}

function editarFornecedor(id, data) {
    document.getElementById('form-editar-fornecedor').action = `/fornecedores/${id}`;
    document.getElementById('forn-edit-nome').value     = data.nome;
    document.getElementById('forn-edit-grupo').value    = data.grupo || '';
    document.getElementById('forn-edit-icone').value    = data.icone || 'fa-store';
    document.getElementById('forn-edit-contato').value  = data.contato || '';
    document.getElementById('forn-edit-telefone').value = data.telefone || '';
    document.getElementById('forn-edit-cnpj').value     = data.cnpj || '';
    document.getElementById('forn-edit-obs').value      = data.observacoes || '';
    document.getElementById('edit-icone-preview').className = 'fa-solid ' + (data.icone || 'fa-store');
    openModal('modal-editar-fornecedor');
}

function limparBusca() {
    document.getElementById('busca-fornecedor').value = '';
    document.getElementById('busca-fornecedor').dispatchEvent(new Event('input'));
}

// Busca em tempo real
document.getElementById('busca-fornecedor').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();

    document.getElementById('aviso-busca').style.display = q ? '' : 'none';
    document.getElementById('termo-busca-label').textContent = q;

    document.querySelectorAll('.grupo-bloco').forEach(bloco => {
        const grupoSlug = bloco.querySelector('[id^="grupo-itens-"]')?.id.replace('grupo-itens-', '');
        let visiveis = 0;

        bloco.querySelectorAll('.card-fornecedor').forEach(card => {
            const nome  = card.dataset.nome;
            const grupo = card.dataset.grupo;
            const show  = !q || nome.includes(q) || grupo.includes(q);
            card.style.display = show ? '' : 'none';
            if (show) visiveis++;
        });

        // Se buscando e há resultados: expande automaticamente
        if (q && visiveis > 0 && grupoSlug) {
            const itens   = document.getElementById('grupo-itens-' + grupoSlug);
            const chevron = document.getElementById('chevron-' + grupoSlug);
            if (itens) itens.style.display = '';
            if (chevron) chevron.style.transform = 'rotate(180deg)';
        } else if (q && visiveis === 0 && grupoSlug) {
            const itens   = document.getElementById('grupo-itens-' + grupoSlug);
            const chevron = document.getElementById('chevron-' + grupoSlug);
            if (itens) itens.style.display = 'none';
            if (chevron) chevron.style.transform = '';
        } else if (!q && grupoSlug) {
            // Restaura estado original ao limpar busca
            const itens   = document.getElementById('grupo-itens-' + grupoSlug);
            const chevron = document.getElementById('chevron-' + grupoSlug);
            const estaAberto = gruposAbertos[grupoSlug] || false;
            if (itens) itens.style.display = estaAberto ? '' : 'none';
            if (chevron) chevron.style.transform = estaAberto ? 'rotate(180deg)' : '';
        }

        bloco.style.display = (q && visiveis === 0) ? 'none' : '';

        // Atualiza contador do grupo
        const contador = bloco.querySelector('.contador-grupo');
        if (contador) {
            const total = bloco.querySelectorAll('.card-fornecedor').length;
            const n = q ? visiveis : total;
            contador.textContent = n + ' fornecedor' + (n !== 1 ? 'es' : '');
        }
    });
});
</script>
@endpush
