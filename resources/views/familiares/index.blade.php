@extends('layouts.main')
@section('title', 'Familiares')
@section('page-title', 'Familiares')

@section('content')
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button class="btn-primary" onclick="openModal('modal-novo-familiar')">
        <i class="fa-solid fa-user-plus"></i> Novo Familiar
    </button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;">
    @forelse($familiares as $familiar)
        <div class="card" style="text-align:center;">
            <div style="width:70px;height:70px;border-radius:50%;margin:0 auto 12px;overflow:hidden;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                @if($familiar->foto)
                    <img src="{{ Storage::url($familiar->foto) }}" alt="{{ $familiar->nome }}" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <i class="fa-solid fa-user" style="font-size:28px;color:#7c3aed;"></i>
                @endif
            </div>
            <div style="font-size:16px;font-weight:700;color:#1e293b;">{{ $familiar->nome }}</div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:12px;">
                <div style="background:#f8fafc;border-radius:8px;padding:8px;">
                    <div style="font-size:10px;color:#94a3b8;font-weight:700;">SALÁRIO</div>
                    <div style="font-size:13px;font-weight:700;color:#16a34a;">R$ {{ number_format($familiar->salario, 0, ',', '.') }}</div>
                </div>
                <div style="background:#f8fafc;border-radius:8px;padding:8px;">
                    <div style="font-size:10px;color:#94a3b8;font-weight:700;">CARTÃO</div>
                    <div style="font-size:13px;font-weight:700;color:#d97706;">R$ {{ number_format($familiar->limite_cartao, 0, ',', '.') }}</div>
                </div>
                <div style="background:#f8fafc;border-radius:8px;padding:8px;">
                    <div style="font-size:10px;color:#94a3b8;font-weight:700;">CHEQUE</div>
                    <div style="font-size:13px;font-weight:700;color:#2563eb;">R$ {{ number_format($familiar->limite_cheque, 0, ',', '.') }}</div>
                </div>
            </div>

            <div style="display:flex;gap:8px;margin-top:12px;justify-content:center;">
                <button onclick="editarFamiliar({{ $familiar->id }}, {{ $familiar->toJson() }})" class="btn-secondary btn-sm">
                    <i class="fa-solid fa-pen"></i> Editar
                </button>
                <form method="POST" action="{{ route('familiares.destroy', $familiar) }}" onsubmit="return confirm('Excluir este familiar?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
            </div>
        </div>
    @empty
        <div class="card" style="text-align:center;padding:40px;color:#94a3b8;grid-column:span 3;">
            <i class="fa-solid fa-users" style="font-size:40px;display:block;margin-bottom:12px;"></i>
            Nenhum familiar cadastrado.
        </div>
    @endforelse
</div>

{{-- Modal Novo --}}
<div class="modal-backdrop" id="modal-novo-familiar">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-user-plus" style="color:#6366f1;"></i> Novo Familiar
            <button onclick="closeModal('modal-novo-familiar')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('familiares.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid-2" style="gap:12px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required placeholder="Nome completo">
                </div>
                <div>
                    <label class="form-label">Salário</label>
                    <input type="number" name="salario" step="0.01" min="0" value="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Limite Cartão</label>
                    <input type="number" name="limite_cartao" step="0.01" min="0" value="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Limite Cheque</label>
                    <input type="number" name="limite_cheque" step="0.01" min="0" value="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Foto</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-novo-familiar')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-familiar">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#6366f1;"></i> Editar Familiar
            <button onclick="closeModal('modal-editar-familiar')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-familiar" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid-2" style="gap:12px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" id="fam-edit-nome" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Salário</label>
                    <input type="number" name="salario" id="fam-edit-salario" step="0.01" min="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Limite Cartão</label>
                    <input type="number" name="limite_cartao" id="fam-edit-cartao" step="0.01" min="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Limite Cheque</label>
                    <input type="number" name="limite_cheque" id="fam-edit-cheque" step="0.01" min="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Nova Foto</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-familiar')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
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
