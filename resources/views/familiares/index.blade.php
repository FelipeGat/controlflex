@extends('layouts.main')
@section('title', 'Familiares')
@section('page-title', 'Familiares')

@section('content')

<div class="section-header mb-4">
    <span></span>
    <button class="btn btn-primary" onclick="openModal('modal-novo-familiar')">
        <i class="fa-solid fa-user-plus"></i> Novo Familiar
    </button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
    @forelse($familiares as $familiar)
        <div class="card" style="text-align:center;">
            <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 12px;overflow:hidden;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                @if($familiar->foto)
                    <img src="{{ Storage::url($familiar->foto) }}" alt="{{ $familiar->nome }}" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <i class="fa-solid fa-user" style="font-size:24px;color:#7c3aed;"></i>
                @endif
            </div>
            <div class="fw-600" style="font-size:15px;margin-bottom:10px;">{{ $familiar->nome }}</div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:12px;">
                <div style="background:var(--color-bg);border-radius:6px;padding:7px 4px;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;" class="text-subtle">Salário</div>
                    <div class="fw-600 text-green" style="font-size:12px;margin-top:2px;">R$ {{ number_format($familiar->salario, 0, ',', '.') }}</div>
                </div>
                <div style="background:var(--color-bg);border-radius:6px;padding:7px 4px;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;" class="text-subtle">Cartão</div>
                    <div class="fw-600 text-amber" style="font-size:12px;margin-top:2px;">R$ {{ number_format($familiar->limite_cartao, 0, ',', '.') }}</div>
                </div>
                <div style="background:var(--color-bg);border-radius:6px;padding:7px 4px;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;" class="text-subtle">Cheque</div>
                    <div class="fw-600" style="font-size:12px;margin-top:2px;color:#2563eb;">R$ {{ number_format($familiar->limite_cheque, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="d-flex gap-2" style="justify-content:center;">
                <button onclick="editarFamiliar({{ $familiar->id }}, {{ $familiar->toJson() }})" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-pen"></i> Editar
                </button>
                <form method="POST" action="{{ route('familiares.destroy', $familiar) }}" onsubmit="return confirm('Excluir este familiar?')" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-sm text-red"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <i class="fa-solid fa-users"></i>
                <p>Nenhum familiar cadastrado.</p>
            </div>
        </div>
    @endforelse
</div>

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-familiar">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-user-plus" style="color:var(--color-primary);"></i>
            <h3>Novo Familiar</h3>
            <button class="modal-close" onclick="closeModal('modal-novo-familiar')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('familiares.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Nome completo">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Salário</label>
                        <input type="number" name="salario" step="0.01" min="0" value="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cartão</label>
                        <input type="number" name="limite_cartao" step="0.01" min="0" value="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cheque</label>
                        <input type="number" name="limite_cheque" step="0.01" min="0" value="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-novo-familiar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-familiar">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary);"></i>
            <h3>Editar Familiar</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-familiar')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-familiar" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="fam-edit-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Salário</label>
                        <input type="number" name="salario" id="fam-edit-salario" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cartão</label>
                        <input type="number" name="limite_cartao" id="fam-edit-cartao" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cheque</label>
                        <input type="number" name="limite_cheque" id="fam-edit-cheque" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nova Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-familiar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function editarFamiliar(id, data) {
    document.getElementById('form-editar-familiar').action = `/familiares/${id}`;
    document.getElementById('fam-edit-nome').value = data.nome;
    document.getElementById('fam-edit-salario').value = data.salario;
    document.getElementById('fam-edit-cartao').value = data.limite_cartao;
    document.getElementById('fam-edit-cheque').value = data.limite_cheque;
    openModal('modal-editar-familiar');
}
</script>
@endpush
