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
        @php $seg = $mnt->segundosRestantes(); @endphp
        @if($seg !== null)
        <span style="white-space:nowrap;font-variant-numeric:tabular-nums;" id="mnt-timer">
            &bull;
            <span id="mnt-countdown">{{ gmdate('H:i:s', $seg) }}</span>
        </span>
        @endif
    </span>

    <button
        type="button"
        onclick="document.getElementById('manutencao-banner').style.display='none'; sessionStorage.setItem('mnt_banner_dismissed','1');"
        style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px;padding:0 4px;flex-shrink:0;"
        aria-label="Fechar aviso"
    >✕</button>
</div>

<script>
(function() {
    // Esconder se já foi dispensado nesta sessão
    if (sessionStorage.getItem('mnt_banner_dismissed') === '1') {
        document.getElementById('manutencao-banner').style.display = 'none';
        return;
    }

    @if($seg !== null)
    var target = Date.now() + {{ $seg }} * 1000;
    var el = document.getElementById('mnt-countdown');

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
        var diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
        var h = Math.floor(diff / 3600);
        var m = Math.floor((diff % 3600) / 60);
        var s = diff % 60;
        if (el) el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
    }

    tick();
    setInterval(tick, 1000);
    @endif
})();
</script>
@endif
