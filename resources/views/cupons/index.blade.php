@extends('layouts.main')
@section('title', 'Indicar Amigos')
@section('page-title', 'Indicar Amigos')

@section('content')

<div style="max-width:700px;margin:0 auto;">

    {{-- Card principal --}}
    <div class="card" style="padding:24px;margin-bottom:20px;">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:64px;height:64px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fa-solid fa-gift" style="font-size:28px;color:#16a34a;"></i>
            </div>
            <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin:0 0 6px;">Indique e ganhe desconto!</h2>
            <p style="font-size:14px;color:#64748b;margin:0;">Compartilhe seu cupom com amigos. Quando alguém se cadastrar usando seu cupom, <strong>voce ganha {{ number_format($cupom->desconto_percentual, 0) }}% de desconto</strong> na proxima mensalidade.</p>
        </div>

        <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;padding:20px;text-align:center;margin-bottom:16px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-weight:600;margin-bottom:6px;">Seu cupom de indicacao</div>
            <div id="codigo-cupom" style="font-size:32px;font-weight:800;color:var(--color-primary);letter-spacing:3px;margin-bottom:12px;">{{ $cupom->codigo }}</div>
            <button onclick="copiarCupom()" id="btn-copiar"
                style="padding:8px 20px;background:var(--color-primary);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;">
                <i class="fa-solid fa-copy"></i> Copiar Cupom
            </button>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;">
            <div style="background:#f0fdf4;border-radius:10px;padding:14px 8px;">
                <div style="font-size:22px;font-weight:700;color:#16a34a;">{{ $cupom->creditos_disponiveis }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Indicacoes</div>
            </div>
            <div style="background:#eff6ff;border-radius:10px;padding:14px 8px;">
                <div style="font-size:22px;font-weight:700;color:#2563eb;">{{ $cupom->creditos_pendentes }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Descontos disponiveis</div>
            </div>
            <div style="background:#fef3c7;border-radius:10px;padding:14px 8px;">
                <div style="font-size:22px;font-weight:700;color:#d97706;">{{ $cupom->creditos_utilizados }}</div>
                <div style="font-size:11px;color:#64748b;margin-top:2px;">Descontos usados</div>
            </div>
        </div>
    </div>

    {{-- Link de convite --}}
    <div class="card" style="padding:18px;margin-bottom:20px;">
        <div style="font-size:13px;font-weight:600;color:#1e293b;margin-bottom:8px;"><i class="fa-solid fa-link" style="color:var(--color-primary);"></i> Link de convite</div>
        <div style="display:flex;gap:8px;">
            <input type="text" id="link-convite" readonly
                value="{{ url('/register?cupom=' . $cupom->codigo) }}"
                style="flex:1;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#475569;background:#f8fafc;">
            <button onclick="copiarLink()" id="btn-copiar-link"
                style="padding:10px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#475569;cursor:pointer;white-space:nowrap;transition:all .2s;">
                <i class="fa-solid fa-copy"></i>
            </button>
        </div>
    </div>

    {{-- Historico de indicacoes --}}
    @if($indicacoes->isNotEmpty())
    <div class="card" style="padding:18px;">
        <div style="font-size:13px;font-weight:600;color:#1e293b;margin-bottom:12px;"><i class="fa-solid fa-clock-rotate-left" style="color:var(--color-primary);"></i> Historico de indicacoes</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($indicacoes as $ind)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#f8fafc;border-radius:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:#e0e7ff;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-user" style="font-size:12px;color:#4f46e5;"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#1e293b;">{{ $ind->tenantIndicado->nome ?? 'Usuario' }}</div>
                        <div style="font-size:11px;color:#94a3b8;">{{ $ind->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <span style="padding:4px 10px;background:#dcfce7;color:#16a34a;border-radius:6px;font-size:11px;font-weight:600;">+{{ number_format($cupom->desconto_percentual, 0) }}% desconto</span>
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
    btn.style.background = '#16a34a';
    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copiar Cupom'; btn.style.background = 'var(--color-primary)'; }, 2000);
}
function copiarLink() {
    navigator.clipboard.writeText(document.getElementById('link-convite').value);
    const btn = document.getElementById('btn-copiar-link');
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    btn.style.background = '#dcfce7';
    btn.style.color = '#16a34a';
    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-copy"></i>'; btn.style.background = '#f1f5f9'; btn.style.color = '#475569'; }, 2000);
}
</script>
@endsection
