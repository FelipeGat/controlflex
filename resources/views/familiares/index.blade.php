@extends('layouts.main')
@section('title', 'Membros')
@section('page-title', 'Membros')

@section('content')

<div class="section-header mb-4">
    <span></span>
    @if(Auth::user()->temPermissao('familiares', 'criar'))
    <button class="btn btn-primary" onclick="openModal('modal-novo')">
        <i class="fa-solid fa-user-plus"></i> Novo Membro
    </button>
    @endif
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(min(260px,100%),1fr));gap:14px;">
    @forelse($familiares as $familiar)
    @php
        $userVinculado = $familiar->userVinculado;
        $ehMaster = $userVinculado && $userVinculado->role === 'master';
        $membro = $ehMaster ? null : $userVinculado;
    @endphp
    <div class="card" style="text-align:center;position:relative;">

        {{-- Badge master --}}
        @if($ehMaster)
        <span style="position:absolute;top:10px;left:10px;background:var(--color-violet);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.03em;">
            <i class="fa-solid fa-crown" style="margin-right:3px;"></i>Master
        </span>
        {{-- Badge acesso ao sistema --}}
        @elseif($membro)
        <span style="position:absolute;top:10px;left:10px;background:var(--color-primary);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.03em;">
            <i class="fa-solid fa-shield-halved" style="margin-right:3px;"></i>Acesso ao sistema
        </span>
        @endif

        {{-- Foto --}}
        <div style="width:64px;height:64px;border-radius:50%;margin:0 auto 12px;overflow:hidden;background:var(--color-violet-soft);display:flex;align-items:center;justify-content:center;">
            @if($familiar->foto)
                <img src="{{ Storage::url($familiar->foto) }}" alt="{{ $familiar->nome }}" style="width:100%;height:100%;object-fit:cover;">
            @else
                <i class="fa-solid fa-user" style="font-size:24px;color:var(--color-violet);"></i>
            @endif
        </div>

        {{-- Nome --}}
        <div class="fw-600" style="font-size:15px;margin-bottom:4px;">{{ $familiar->nome }}</div>

        @if($ehMaster)
        <div class="text-subtle" style="font-size:11px;margin-bottom:10px;">{{ $userVinculado->email }}</div>
        @elseif($membro)
        <div class="text-subtle" style="font-size:11px;margin-bottom:10px;">{{ $membro->email }}</div>
        @else
        <div style="margin-bottom:10px;"></div>
        @endif

        {{-- Dados financeiros --}}
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
                <div class="fw-600" style="font-size:12px;margin-top:2px;color:var(--color-info);">R$ {{ number_format($familiar->limite_cheque, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- Ações --}}
        <div class="d-flex gap-2" style="justify-content:center;">
            @if(Auth::user()->temPermissao('familiares', 'editar'))
            <button onclick="editarMembro({{ $familiar->id }}, {{ $familiar->toJson() }}, {{ $membro ? $membro->toJson() : 'null' }}, {{ $ehMaster ? 'true' : 'false' }})"
                class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-pen"></i> Editar
            </button>
            @endif
            @if(Auth::user()->temPermissao('familiares', 'excluir') && !$ehMaster)
            <form method="POST" action="{{ route('familiares.destroy', $familiar) }}"
                onsubmit="return confirm('Excluir {{ addslashes($familiar->nome) }}? Esta ação também remove o acesso ao sistema se existir.')"
                style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm text-red"><i class="fa-solid fa-trash"></i></button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="card">
        <div class="empty-state">
            <i class="fa-solid fa-users"></i>
            <p>Nenhum membro cadastrado.</p>
        </div>
    </div>
    @endforelse
</div>

{{-- ─── Modal Novo ─────────────────────────────────────────────────────────── --}}
<div class="modal-backdrop" id="modal-novo">
    <div class="modal" style="max-width:620px;">
        <div class="modal-header">
            <i class="fa-solid fa-user-plus" style="color:var(--color-primary);"></i>
            <h3>Novo Membro</h3>
            <button class="modal-close" onclick="closeModal('modal-novo')">&times;</button>
        </div>
        <form method="POST" action="{{ route('familiares.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">

                {{-- Dados pessoais --}}
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

                @if(Auth::user()->isMaster())
                {{-- Toggle acesso ao sistema --}}
                <div style="margin-top:16px;padding:12px 14px;background:var(--color-bg);border-radius:8px;border:1px solid var(--color-border);">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;">
                        <input type="checkbox" name="tem_acesso" id="novo-tem-acesso" value="1"
                            onchange="toggleAcesso('novo-acesso-fields', this.checked)"
                            style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span style="font-weight:600;font-size:13px;">
                            <i class="fa-solid fa-shield-halved" style="color:var(--color-primary);margin-right:5px;"></i>
                            Acesso ao sistema
                        </span>
                        <span class="text-subtle" style="font-size:12px;">permitir login no AlfaHome</span>
                    </label>
                </div>

                <div id="novo-acesso-fields" style="display:none;margin-top:12px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" class="form-control" placeholder="email@exemplo.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Senha *</label>
                            <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres">
                        </div>
                    </div>
                    <div class="mt-3">
                        @include('membros._permissoes_grid', ['prefix' => '', 'permissoes' => []])
                    </div>
                </div>
                @endif

            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('modal-novo')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Modal Editar ───────────────────────────────────────────────────────── --}}
<div class="modal-backdrop" id="modal-editar">
    <div class="modal" style="max-width:620px;">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary);"></i>
            <h3>Editar Membro</h3>
            <button class="modal-close" onclick="closeModal('modal-editar')">&times;</button>
        </div>
        <form method="POST" id="form-editar" action="" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="modal-body">

                {{-- Dados pessoais --}}
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="edit-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Salário</label>
                        <input type="number" name="salario" id="edit-salario" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cartão</label>
                        <input type="number" name="limite_cartao" id="edit-cartao" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cheque</label>
                        <input type="number" name="limite_cheque" id="edit-cheque" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nova Foto</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                    </div>
                </div>

                @if(Auth::user()->isMaster())
                {{-- Toggle acesso ao sistema --}}
                <div style="margin-top:16px;padding:12px 14px;background:var(--color-bg);border-radius:8px;border:1px solid var(--color-border);">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;">
                        <input type="checkbox" name="tem_acesso" id="edit-tem-acesso" value="1"
                            onchange="toggleAcesso('edit-acesso-fields', this.checked)"
                            style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span style="font-weight:600;font-size:13px;">
                            <i class="fa-solid fa-shield-halved" style="color:var(--color-primary);margin-right:5px;"></i>
                            Acesso ao sistema
                        </span>
                        <span class="text-subtle" style="font-size:12px;">permitir login no AlfaHome</span>
                    </label>
                </div>

                <div id="edit-acesso-fields" style="display:none;margin-top:12px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">E-mail *</label>
                            <input type="email" name="email" id="edit-email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nova senha <span class="text-subtle">(deixe em branco para manter)</span></label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="form-group" style="align-self:flex-end;">
                            <label class="form-check">
                                <input type="checkbox" name="ativo" id="edit-ativo" value="1"> Conta ativa
                            </label>
                        </div>
                    </div>
                    <div class="mt-3" id="edit-permissoes-grid">
                        @include('membros._permissoes_grid', ['prefix' => '', 'permissoes' => []])
                    </div>
                </div>
                @endif

            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('modal-editar')" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleAcesso(fieldId, show) {
    document.getElementById(fieldId).style.display = show ? 'block' : 'none';
}

function editarMembro(id, familiar, membro, ehMaster) {
    document.getElementById('form-editar').action = '/familiares/' + id;
    document.getElementById('edit-nome').value    = familiar.nome;
    document.getElementById('edit-salario').value = familiar.salario;
    document.getElementById('edit-cartao').value  = familiar.limite_cartao;
    document.getElementById('edit-cheque').value  = familiar.limite_cheque;

    const temAcessoBox = document.getElementById('edit-tem-acesso');
    if (!temAcessoBox) { openModal('modal-editar'); return; }

    const acessoContainer = temAcessoBox.closest('div[style*="background:var(--color-bg)"]');

    if (ehMaster) {
        // Master não pode alterar acesso ao sistema via esta tela
        if (acessoContainer) acessoContainer.style.display = 'none';
        temAcessoBox.checked = false;
        toggleAcesso('edit-acesso-fields', false);
    } else {
        if (acessoContainer) acessoContainer.style.display = '';

        if (membro) {
            temAcessoBox.checked = true;
            toggleAcesso('edit-acesso-fields', true);
            document.getElementById('edit-email').value = membro.email;
            document.getElementById('edit-ativo').checked = membro.ativo == 1 || membro.ativo === true;

            const modulos = ['despesas','receitas','investimentos','bancos','categorias','fornecedores','familiares'];
            const acoes   = ['ver','criar','editar','excluir'];
            const perms   = membro.permissoes || {};
            modulos.forEach(m => {
                acoes.forEach(a => {
                    const cb = document.querySelector(`#edit-permissoes-grid [name="perm_${m}_${a}"]`);
                    if (cb) cb.checked = perms[m] && perms[m][a] ? true : false;
                });
            });
        } else {
            temAcessoBox.checked = false;
            toggleAcesso('edit-acesso-fields', false);
        }
    }

    openModal('modal-editar');
}
</script>
@endpush
