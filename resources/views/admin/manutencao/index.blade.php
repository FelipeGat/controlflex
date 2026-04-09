@extends('layouts.main')
@section('title', 'Manutenção do Sistema')
@section('page-title', 'Manutenção do Sistema')

@section('content')
<div class="section-header">
    <h2><i class="fa-solid fa-wrench" style="color:var(--color-warning)"></i> Manutenção do Sistema</h2>
</div>

{{-- Status atual --}}
@php
    $isAtiva    = $manutencao->isAtiva();
    $isAgendada = $manutencao->isAgendada();
    $segundos   = $manutencao->segundosRestantes();
@endphp

<div class="card mb-4" style="border-left: 4px solid {{ $isAtiva ? 'var(--color-danger)' : ($isAgendada ? 'var(--color-warning)' : 'var(--color-success)') }}; padding: 20px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span style="font-size:28px;">{{ $isAtiva ? '🔴' : ($isAgendada ? '🟡' : '🟢') }}</span>
        <div>
            <div style="font-weight:700;font-size:16px;">
                @if($isAtiva)
                    Manutenção ATIVA agora
                @elseif($isAgendada)
                    Manutenção agendada — aguardando início
                @else
                    Sistema operando normalmente
                @endif
            </div>
            @if($segundos !== null)
            <div style="font-size:13px;color:var(--color-text-muted);margin-top:4px;">
                {{ $isAtiva ? 'Fim em:' : 'Início em:' }}
                <strong id="status-countdown" style="font-variant-numeric:tabular-nums;">{{ gmdate('H:i:s', $segundos) }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif

@if($manutencao->ativo && !$isAtiva && !$isAgendada)
<div class="alert mb-3" style="background:#fef3c7;border:1px solid #d97706;color:#92400e;border-radius:8px;padding:12px 16px;font-size:13px;">
    ⚠️ <strong>Atenção:</strong> O modo manutenção está <strong>ativado</strong>, mas a janela de tempo definida já expirou.
    Limpe os campos de data/hora abaixo e salve novamente para ativar imediatamente — ou defina uma nova janela futura.
</div>
@endif

{{-- Formulário --}}
<div class="card">
    <div style="padding: 24px;">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:20px;color:var(--color-text);">Configurar Manutenção</h3>

        <form method="POST" action="{{ route('admin.manutencao.update') }}">
            @csrf
            @method('PUT')

            {{-- Toggle ativo --}}
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;">
                    <input
                        type="checkbox"
                        name="ativo"
                        value="1"
                        {{ $manutencao->ativo ? 'checked' : '' }}
                        style="width:18px;height:18px;accent-color:var(--color-danger);"
                    >
                    <span style="font-weight:600;font-size:15px;">Ativar modo manutenção</span>
                </label>
                <p style="font-size:12px;color:var(--color-text-muted);margin-top:6px;margin-left:28px;">
                    Quando ativado, todos os usuários (exceto super_admin) serão redirecionados para a tela de manutenção.
                    Se definir data/hora de início, o bloqueio só ocorrerá a partir daquele momento.
                </p>
            </div>

            {{-- Título --}}
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Título <span style="color:var(--color-danger)">*</span></label>
                <input
                    type="text"
                    name="titulo"
                    class="form-control @error('titulo') is-invalid @enderror"
                    value="{{ old('titulo', $manutencao->titulo) }}"
                    maxlength="200"
                    placeholder="Ex: Manutenção Programada"
                    required
                >
                @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Mensagem --}}
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Mensagem para o usuário</label>
                <textarea
                    name="mensagem"
                    class="form-control @error('mensagem') is-invalid @enderror"
                    rows="3"
                    maxlength="1000"
                    placeholder="Ex: Estamos realizando atualizações. Voltaremos em breve!"
                >{{ old('mensagem', $manutencao->mensagem) }}</textarea>
                @error('mensagem') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Janela de tempo --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div class="form-group">
                    <label class="form-label">Início (opcional)</label>
                    <input
                        type="datetime-local"
                        name="inicio_programado"
                        class="form-control @error('inicio_programado') is-invalid @enderror"
                        value="{{ old('inicio_programado', $manutencao->inicio_programado?->format('Y-m-d\TH:i')) }}"
                    >
                    @error('inicio_programado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small style="color:var(--color-text-muted);font-size:12px;">Deixe em branco para iniciar imediatamente</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Fim (opcional)</label>
                    <input
                        type="datetime-local"
                        name="fim_programado"
                        class="form-control @error('fim_programado') is-invalid @enderror"
                        value="{{ old('fim_programado', $manutencao->fim_programado?->format('Y-m-d\TH:i')) }}"
                    >
                    @error('fim_programado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small style="color:var(--color-text-muted);font-size:12px;">Após esta data, o sistema libera automaticamente</small>
                </div>
            </div>

            @if($manutencao->criado_por)
            <p style="font-size:12px;color:var(--color-text-subtle);margin-bottom:16px;">
                Última alteração por: <strong>{{ $manutencao->criado_por }}</strong>
                em {{ $manutencao->updated_at?->format('d/m/Y H:i') }}
            </p>
            @endif

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Salvar configurações
                </button>
                @if($isAtiva)
                <button
                    type="submit"
                    name="ativo"
                    value="0"
                    class="btn btn-danger"
                    onclick="this.form.querySelector('[name=ativo][type=checkbox]').removeAttribute('name')"
                >
                    <i class="fa-solid fa-power-off"></i> Desativar agora
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

@if($segundos !== null)
<script>
(function() {
    var target = Date.now() + {{ $segundos }} * 1000;
    var el = document.getElementById('status-countdown');
    if (!el) return;

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        var diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
        var h = Math.floor(diff / 3600);
        var m = Math.floor((diff % 3600) / 60);
        var s = diff % 60;
        el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
        if (diff <= 0) window.location.reload();
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
@endif
@endsection
