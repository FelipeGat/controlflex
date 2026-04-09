@php
    // Injetado pelo middleware via view()->share() apenas para super_admin
    $mnt = $manutencao ?? null;
    $showBanner = $mnt && ($mnt->isAtiva() || $mnt->isAgendada());
@endphp

@if($showBanner)
<div
    id="manutencao-banner"
    class="{{ $mnt->isAtiva() ? 'mnt-banner mnt-banner--danger' : 'mnt-banner mnt-banner--warn' }}"
    style="
        position: sticky;
        top: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 20px;
        font-size: 13px;
        font-weight: 600;
        background: {{ $mnt->isAtiva() ? '#dc2626' : '#d97706' }};
        color: #fff;
    "
>
    <span style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
        <span>{{ $mnt->isAtiva() ? '🔧' : '⏰' }}</span>
        <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <strong>{{ $mnt->titulo }}</strong>
            @if($mnt->inicio_programado)
                &bull; {{ $mnt->inicio_programado->format('d/m/Y H:i') }}
            @endif
            @if($mnt->fim_programado)
                até {{ $mnt->fim_programado->format('d/m/Y H:i') }}
            @endif
        </span>

    </span>

    <button
        type="button"
        onclick="document.getElementById('manutencao-banner').style.display='none'; sessionStorage.setItem('mnt_banner_dismissed','1');"
        style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px;padding:0 4px;flex-shrink:0;"
        aria-label="Fechar aviso"
    >✕</button>
</div>

@endif
