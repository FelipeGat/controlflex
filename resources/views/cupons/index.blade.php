@extends('layouts.main')
@section('title', 'Indicar Amigos')
@section('page-title', 'Indicar Amigos')

@section('content')

<div style="max-width:700px;margin:0 auto;">

    {{-- Card principal --}}
    <div class="card" style="padding:24px;margin-bottom:20px;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:64px;height:64px;border-radius:50%;background:var(--color-success-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fa-solid fa-gift" style="font-size:28px;color:var(--color-success);"></i>
            </div>
            <h2 style="font-size:20px;font-weight:700;color:var(--color-text);margin:0 0 6px;">Indique e ganhe desconto!</h2>
            <p style="font-size:14px;color:var(--color-text-muted);margin:0;">Compartilhe seu cupom com amigos. Quando alguém se cadastrar usando seu cupom, <strong>voce ganha {{ number_format($cupom->desconto_percentual, 0) }}% de desconto</strong> na proxima mensalidade.</p>
        </div>

        <div style="background:var(--color-bg-inset);border:2px dashed var(--color-border);border-radius:12px;padding:20px;text-align:center;margin-bottom:16px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--color-text-subtle);font-weight:600;margin-bottom:6px;">Seu cupom de indicacao</div>
            <div id="codigo-cupom" style="font-size:32px;font-weight:800;color:var(--color-primary);letter-spacing:3px;margin-bottom:12px;">{{ $cupom->codigo }}</div>
            <button onclick="copiarCupom()" id="btn-copiar"
                style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-copy"></i> Copiar Cupom
            </button>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;">
            <div style="background:var(--color-success-soft);border-radius:10px;padding:14px 8px;">
                <div style="font-size:22px;font-weight:700;color:var(--color-success);">{{ $cupom->creditos_disponiveis }}</div>
                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">Indicacoes</div>
            </div>
            <div style="background:var(--color-info-soft);border-radius:10px;padding:14px 8px;">
                <div style="font-size:22px;font-weight:700;color:var(--color-info);">{{ $cupom->creditos_pendentes }}</div>
                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">Descontos disponiveis</div>
            </div>
            <div style="background:var(--color-warning-soft);border-radius:10px;padding:14px 8px;">
                <div style="font-size:22px;font-weight:700;color:var(--color-amber);">{{ $cupom->creditos_utilizados }}</div>
                <div style="font-size:11px;color:var(--color-text-muted);margin-top:2px;">Descontos usados</div>
            </div>
        </div>
    </div>

    {{-- Link de convite --}}
    <div class="card" style="padding:18px;margin-bottom:20px;">
        <div style="font-size:13px;font-weight:600;color:var(--color-text);margin-bottom:8px;"><i class="fa-solid fa-link" style="color:var(--color-primary);"></i> Link de convite</div>
        <div style="display:flex;gap:8px;">
            <input type="text" id="link-convite" readonly
                value="{{ url('/register?cupom=' . $cupom->codigo) }}"
                style="flex:1;padding:10px 12px;border:1px solid var(--color-border);border-radius:8px;font-size:13px;color:var(--color-text-muted);background:var(--color-bg-inset);">
            <button onclick="copiarLink()" id="btn-copiar-link"
                style="padding:10px 16px;background:var(--color-bg-inset);border:1px solid var(--color-border);border-radius:8px;font-size:13px;color:var(--color-text-muted);cursor:pointer;white-space:nowrap;transition:all .2s;">
                <i class="fa-solid fa-copy"></i>
            </button>
        </div>
    </div>

    {{-- Historico de indicacoes --}}
    @if($indicacoes->isNotEmpty())
    <div class="card" style="padding:18px;">
        <div style="font-size:13px;font-weight:600;color:var(--color-text);margin-bottom:12px;"><i class="fa-solid fa-clock-rotate-left" style="color:var(--color-primary);"></i> Historico de indicacoes</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($indicacoes as $ind)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--color-bg-inset);border-radius:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:var(--color-info-soft);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-user" style="font-size:12px;color:var(--color-violet);"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--color-text);">{{ $ind->tenantIndicado->nome ?? 'Usuario' }}</div>
                        <div style="font-size:11px;color:var(--color-text-subtle);">{{ $ind->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <span style="padding:4px 10px;background:var(--color-success-soft);color:var(--color-success);border-radius:6px;font-size:11px;font-weight:600;">+{{ number_format($cupom->desconto_percentual, 0) }}% desconto</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
function copiarCupom() {
    navigator.clipboard.writeText(document.getElementById('codigo-cupom').textContent.trim());
    const btn = document.getElementById('btn-copiar');
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Copiado!';
    btn.style.background = 'var(--color-success)';
    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copiar Cupom'; btn.style.background = 'var(--color-primary)'; }, 2000);
}
function copiarLink() {
    navigator.clipboard.writeText(document.getElementById('link-convite').value);
    const btn = document.getElementById('btn-copiar-link');
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    btn.style.background = 'var(--color-success-soft)';
    btn.style.color = 'var(--color-success)';
    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-copy"></i>'; btn.style.background = 'var(--color-bg-inset)'; btn.style.color = 'var(--color-text-muted)'; }, 2000);
}
</script>
@endsection
