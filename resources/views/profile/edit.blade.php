@extends('layouts.main')
@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')

<div style="display:flex;flex-direction:column;gap:18px;max-width:600px;width:100%;">

    {{-- Foto + Informações do Perfil --}}
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-user" style="color:var(--color-primary);"></i> Informações do Perfil
        </div>

        @if(session('status') === 'profile-updated')
            <div class="alert-success mb-3">Perfil atualizado com sucesso!</div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div style="display:flex;flex-direction:column;gap:12px;">
                {{-- Foto --}}
                <div class="form-group" style="display:flex;align-items:center;gap:16px;">
                    <div id="foto-preview" style="width:72px;height:72px;border-radius:50%;background:var(--color-bg-subtle);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;border:2px solid var(--color-border);">
                        @if($user->foto)
                            <img src="{{ asset('storage/' . $user->foto) }}" alt="Foto" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <i class="fa-solid fa-camera" style="font-size:24px;color:var(--color-text-subtle);"></i>
                        @endif
                    </div>
                    <div>
                        <label class="btn btn-secondary btn-sm" style="cursor:pointer;">
                            <i class="fa-solid fa-upload"></i> Alterar Foto
                            <input type="file" name="foto" accept="image/*" style="display:none;" onchange="previewFoto(this)">
                        </label>
                        <div style="font-size:11px;color:var(--color-text-subtle);margin-top:4px;">JPG, PNG ou GIF. Max 2MB.</div>
                        @error('foto') <div class="form-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required autofocus>
                    @error('name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            </div>
        </form>
    </div>

    {{-- Minha Licença --}}
    @if($licenca)
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-id-badge" style="color:var(--color-primary);"></i> Minha Licença
        </div>

        <div style="display:flex;flex-direction:column;gap:14px;">
            {{-- Plano --}}
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:12px;color:var(--color-text-subtle);text-transform:uppercase;font-weight:700;">Plano</div>
                    <div style="font-size:16px;font-weight:600;">{{ $licenca['plano_nome'] }}</div>
                    @if($licenca['plano_descricao'])
                        <div style="font-size:12px;color:var(--color-text-subtle);">{{ $licenca['plano_descricao'] }}</div>
                    @endif
                </div>
                <div>
                    @if($licenca['vencido'])
                        <span class="badge badge-red"><i class="fa-solid fa-circle" style="font-size:7px"></i> Vencido</span>
                    @else
                        <span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:7px"></i> Ativo</span>
                    @endif
                </div>
            </div>

            {{-- Cobrança e período --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <div style="font-size:12px;color:var(--color-text-subtle);text-transform:uppercase;font-weight:700;">Cobrança</div>
                    <div style="font-weight:500;">{{ $licenca['tipo_cobranca'] === 'anual' ? 'Anual' : 'Mensal' }}</div>
                </div>
                <div>
                    <div style="font-size:12px;color:var(--color-text-subtle);text-transform:uppercase;font-weight:700;">Período</div>
                    <div style="font-weight:500;">
                        @if($licenca['data_inicio'] && $licenca['data_fim'])
                            {{ $licenca['data_inicio']->format('d/m/Y') }} — {{ $licenca['data_fim']->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            {{-- Dias restantes --}}
            @if($licenca['dias_restantes'] !== null)
            <div>
                <div style="font-size:12px;color:var(--color-text-subtle);text-transform:uppercase;font-weight:700;margin-bottom:6px;">Dias Restantes</div>
                @if($licenca['dias_restantes'] <= 0)
                    <span class="badge badge-red" style="font-size:14px;padding:6px 14px;">Vencido</span>
                @elseif($licenca['dias_restantes'] <= 5)
                    <span class="badge badge-red" style="font-size:14px;padding:6px 14px;">{{ $licenca['dias_restantes'] }} dias</span>
                @elseif($licenca['dias_restantes'] <= 15)
                    <span class="badge badge-yellow" style="font-size:14px;padding:6px 14px;">{{ $licenca['dias_restantes'] }} dias</span>
                @else
                    <span class="badge badge-green" style="font-size:14px;padding:6px 14px;">{{ $licenca['dias_restantes'] }} dias</span>
                @endif
            </div>
            @endif

            {{-- Uso: Usuários --}}
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                    <span style="color:var(--color-text-subtle);text-transform:uppercase;font-weight:700;">Usuários</span>
                    <span style="font-weight:600;">{{ $licenca['uso_usuarios'] }} / {{ $licenca['max_usuarios'] == -1 ? 'Ilimitado' : $licenca['max_usuarios'] }}</span>
                </div>
                @if($licenca['max_usuarios'] != -1)
                    @php $pctUsuarios = $licenca['max_usuarios'] > 0 ? min(100, round(($licenca['uso_usuarios'] / $licenca['max_usuarios']) * 100)) : 0; @endphp
                    <div style="background:var(--color-bg-subtle);border-radius:6px;height:8px;overflow:hidden;">
                        <div style="width:{{ $pctUsuarios }}%;height:100%;border-radius:6px;background:{{ $pctUsuarios >= 90 ? '#dc2626' : ($pctUsuarios >= 70 ? '#eab308' : 'var(--color-primary)') }};transition:width .3s;"></div>
                    </div>
                @endif
            </div>

            {{-- Uso: Bancos --}}
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                    <span style="color:var(--color-text-subtle);text-transform:uppercase;font-weight:700;">Contas Bancárias</span>
                    <span style="font-weight:600;">{{ $licenca['uso_bancos'] }} / {{ $licenca['max_bancos'] == -1 ? 'Ilimitado' : $licenca['max_bancos'] }}</span>
                </div>
                @if($licenca['max_bancos'] != -1)
                    @php $pctBancos = $licenca['max_bancos'] > 0 ? min(100, round(($licenca['uso_bancos'] / $licenca['max_bancos']) * 100)) : 0; @endphp
                    <div style="background:var(--color-bg-subtle);border-radius:6px;height:8px;overflow:hidden;">
                        <div style="width:{{ $pctBancos }}%;height:100%;border-radius:6px;background:{{ $pctBancos >= 90 ? '#dc2626' : ($pctBancos >= 70 ? '#eab308' : 'var(--color-primary)') }};transition:width .3s;"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Trocar Senha --}}
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-lock" style="color:var(--color-primary);"></i> Trocar Senha
        </div>

        @if(session('status') === 'password-updated')
            <div class="alert-success mb-3">Senha atualizada com sucesso!</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div class="form-group">
                    <label class="form-label">Senha Atual *</label>
                    <input type="password" name="current_password" class="form-control" autocomplete="current-password">
                    @error('current_password', 'updatePassword') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Nova Senha *</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                    @error('password', 'updatePassword') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar Nova Senha *</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    @error('password_confirmation', 'updatePassword') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Atualizar Senha</button>
            </div>
        </form>
    </div>

    {{-- Excluir Conta --}}
    <div class="card" style="border-top:3px solid #dc2626;">
        <div class="card-title" style="color:#dc2626;">
            <i class="fa-solid fa-triangle-exclamation"></i> Zona de Perigo
        </div>
        <p style="font-size:13px;margin-bottom:14px;" class="text-subtle">
            Ao excluir sua conta, todos os dados (despesas, receitas, investimentos, etc.) serão permanentemente removidos.
        </p>
        <button class="btn btn-danger" onclick="openModal('modal-excluir-conta')">
            <i class="fa-solid fa-trash"></i> Excluir Minha Conta
        </button>
    </div>

</div>

{{-- Modal Excluir Conta --}}
<div class="modal-backdrop" id="modal-excluir-conta">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;"></i>
            <h3>Excluir Conta</h3>
            <button class="modal-close" onclick="closeModal('modal-excluir-conta')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size:14px;margin-bottom:16px;">
                Tem certeza? Esta ação é <strong>irreversível</strong>. Digite sua senha para confirmar.
            </p>
            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <div class="form-group">
                    <label class="form-label">Senha *</label>
                    <input type="password" name="password" class="form-control" required placeholder="Sua senha atual">
                    @error('password', 'userDeletion') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-excluir-conta')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Excluir Conta</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('foto-preview').innerHTML = '<img src="' + e.target.result + '" alt="Foto" style="width:100%;height:100%;object-fit:cover;">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

@if($errors->userDeletion->isNotEmpty())
    document.addEventListener('DOMContentLoaded', () => openModal('modal-excluir-conta'));
@endif
</script>
@endpush
