@extends('layouts.main')
@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')

<div style="display:flex;flex-direction:column;gap:18px;max-width:600px;">

    {{-- Informações do Perfil --}}
    <div class="card">
        <div class="card-title">
            <i class="fa-solid fa-user" style="color:var(--color-primary);"></i> Informações do Perfil
        </div>

        @if(session('status') === 'profile-updated')
            <div class="alert-success mb-3">Perfil atualizado com sucesso!</div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <div style="display:flex;flex-direction:column;gap:12px;">
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
// Reopen delete modal if there are deletion errors
@if($errors->userDeletion->isNotEmpty())
    document.addEventListener('DOMContentLoaded', () => openModal('modal-excluir-conta'));
@endif
</script>
@endpush
