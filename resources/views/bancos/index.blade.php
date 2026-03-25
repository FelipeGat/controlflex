@extends('layouts.main')
@section('title', 'Contas Bancárias')
@section('page-title', 'Contas Bancárias')

@section('content')
<div style="display:flex;justify-content:flex-end;margin-bottom:20px;">
    <button class="btn-primary" onclick="openModal('modal-novo-banco')">
        <i class="fa-solid fa-plus"></i> Nova Conta
    </button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:24px;">
    @forelse($bancos as $banco)
        <div class="card" style="border-top: 4px solid {{ $banco->tipo_conta === 'Cartão de Crédito' ? '#f59e0b' : '#6366f1' }};">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                <div>
                    <div style="font-weight:700;font-size:16px;color:#1e293b;">{{ $banco->nome }}</div>
                    <span class="badge {{ $banco->tipo_conta === 'Cartão de Crédito' ? 'badge-warning' : 'badge-info' }}">{{ $banco->tipo_conta }}</span>
                    @if($banco->titular)
                        <div style="font-size:12px;color:#94a3b8;margin-top:4px;"><i class="fa-solid fa-user"></i> {{ $banco->titular->nome }}</div>
                    @endif
                </div>
                <div style="display:flex;gap:6px;">
                    <button onclick="editarBanco({{ $banco->id }}, {{ $banco->toJson() }})" class="btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="{{ route('bancos.destroy', $banco) }}" onsubmit="return confirm('Excluir esta conta?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>

            <div style="background:#f8fafc;border-radius:10px;padding:12px;margin-bottom:12px;">
                <div style="font-size:12px;color:#94a3b8;text-transform:uppercase;font-weight:700;letter-spacing:.04em;">Saldo</div>
                <div style="font-size:24px;font-weight:800;color:{{ $banco->saldo >= 0 ? '#16a34a' : '#dc2626' }};">
                    R$ {{ number_format($banco->saldo, 2, ',', '.') }}
                </div>
            </div>

            @if($banco->cheque_especial > 0)
                <div style="font-size:13px;color:#64748b;margin-bottom:8px;">
                    Cheque Especial: R$ {{ number_format($banco->cheque_especial, 2, ',', '.') }}
                </div>
            @endif

            @if($banco->limite_cartao > 0)
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span style="color:#64748b;">Cartão Utilizado</span>
                        <span style="font-weight:600;">R$ {{ number_format($banco->saldo_cartao, 2, ',', '.') }} / {{ number_format($banco->limite_cartao, 2, ',', '.') }}</span>
                    </div>
                    @php $perc = $banco->limite_cartao > 0 ? ($banco->saldo_cartao / $banco->limite_cartao) * 100 : 0; @endphp
                    <div style="height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;">
                        <div style="height:100%;width:{{ min($perc, 100) }}%;background:{{ $perc > 80 ? '#ef4444' : ($perc > 50 ? '#f59e0b' : '#10b981') }};border-radius:999px;transition:width .3s;"></div>
                    </div>
                </div>
            @endif

            <div style="display:flex;gap:6px;">
                <button onclick="ajustarSaldo({{ $banco->id }}, {{ $banco->saldo }})" class="btn-secondary" style="font-size:12px;flex:1;justify-content:center;">
                    <i class="fa-solid fa-sliders"></i> Ajustar Saldo
                </button>
                @if($banco->limite_cartao > 0)
                    <button onclick="ajustarSaldoCartao({{ $banco->id }}, {{ $banco->saldo_cartao }})" class="btn-secondary" style="font-size:12px;flex:1;justify-content:center;">
                        <i class="fa-solid fa-credit-card"></i> Ajustar Cartão
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="card" style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="fa-solid fa-building-columns" style="font-size:40px;display:block;margin-bottom:12px;"></i>
            Nenhuma conta cadastrada. Clique em "Nova Conta" para começar.
        </div>
    @endforelse
</div>

{{-- Modal Nova Conta --}}
<div class="modal-backdrop" id="modal-novo-banco">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-building-columns" style="color:#6366f1;"></i> Nova Conta
            <button onclick="closeModal('modal-novo-banco')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="{{ route('bancos.store') }}">
            @csrf
            <div class="grid-2" style="gap:12px;">
                <div>
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required placeholder="Ex: Nubank, Itaú...">
                </div>
                <div>
                    <label class="form-label">Tipo *</label>
                    <select name="tipo_conta" class="form-control" required>
                        <option value="Conta Corrente">Conta Corrente</option>
                        <option value="Poupança">Poupança</option>
                        <option value="Dinheiro">Dinheiro (Carteira)</option>
                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Titular</label>
                    <select name="titular_id" class="form-control">
                        <option value="">— Nenhum —</option>
                        @foreach($familiares as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Saldo Inicial</label>
                    <input type="number" name="saldo" step="0.01" value="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Cheque Especial</label>
                    <input type="number" name="cheque_especial" step="0.01" value="0" min="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Limite Cartão</label>
                    <input type="number" name="limite_cartao" step="0.01" value="0" min="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Agência</label>
                    <input type="text" name="agencia" class="form-control" placeholder="0000">
                </div>
                <div>
                    <label class="form-label">Conta</label>
                    <input type="text" name="conta" class="form-control" placeholder="00000-0">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-novo-banco')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-banco">
    <div class="modal">
        <div class="modal-title">
            <i class="fa-solid fa-pen" style="color:#6366f1;"></i> Editar Conta
            <button onclick="closeModal('modal-editar-banco')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-editar-banco">
            @csrf @method('PUT')
            <div class="grid-2" style="gap:12px;">
                <div>
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" id="b-edit-nome" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Tipo *</label>
                    <select name="tipo_conta" id="b-edit-tipo" class="form-control" required>
                        <option value="Conta Corrente">Conta Corrente</option>
                        <option value="Poupança">Poupança</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Titular</label>
                    <select name="titular_id" id="b-edit-titular" class="form-control">
                        <option value="">— Nenhum —</option>
                        @foreach($familiares as $f)
                            <option value="{{ $f->id }}">{{ $f->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Saldo</label>
                    <input type="number" name="saldo" id="b-edit-saldo" step="0.01" class="form-control">
                </div>
                <div>
                    <label class="form-label">Cheque Especial</label>
                    <input type="number" name="cheque_especial" id="b-edit-cheque" step="0.01" min="0" class="form-control">
                </div>
                <div>
                    <label class="form-label">Limite Cartão</label>
                    <input type="number" name="limite_cartao" id="b-edit-limite" step="0.01" min="0" class="form-control">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-editar-banco')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Atualizar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Ajustar Saldo --}}
<div class="modal-backdrop" id="modal-ajustar-saldo">
    <div class="modal" style="max-width:360px;">
        <div class="modal-title">
            <i class="fa-solid fa-sliders" style="color:#6366f1;"></i> Ajustar Saldo
            <button onclick="closeModal('modal-ajustar-saldo')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-ajustar-saldo">
            @csrf
            <div>
                <label class="form-label">Novo Saldo</label>
                <input type="number" name="saldo" id="ajuste-saldo-val" step="0.01" class="form-control" required>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-ajustar-saldo')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="modal-ajustar-cartao">
    <div class="modal" style="max-width:360px;">
        <div class="modal-title">
            <i class="fa-solid fa-credit-card" style="color:#f59e0b;"></i> Ajustar Saldo do Cartão
            <button onclick="closeModal('modal-ajustar-cartao')" style="margin-left:auto;background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="" id="form-ajustar-cartao">
            @csrf
            <div>
                <label class="form-label">Valor Utilizado no Cartão</label>
                <input type="number" name="saldo_cartao" id="ajuste-cartao-val" step="0.01" min="0" class="form-control" required>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                <button type="button" onclick="closeModal('modal-ajustar-cartao')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editarBanco(id, data) {
    document.getElementById('form-editar-banco').action = `/bancos/${id}`;
    document.getElementById('b-edit-nome').value = data.nome;
    document.getElementById('b-edit-tipo').value = data.tipo_conta;
    document.getElementById('b-edit-titular').value = data.titular_id || '';
    document.getElementById('b-edit-saldo').value = data.saldo;
    document.getElementById('b-edit-cheque').value = data.cheque_especial;
    document.getElementById('b-edit-limite').value = data.limite_cartao;
    openModal('modal-editar-banco');
}
function ajustarSaldo(id, saldo) {
    document.getElementById('form-ajustar-saldo').action = `/bancos/${id}/ajustar-saldo`;
    document.getElementById('ajuste-saldo-val').value = saldo;
    openModal('modal-ajustar-saldo');
}
function ajustarSaldoCartao(id, saldo) {
    document.getElementById('form-ajustar-cartao').action = `/bancos/${id}/ajustar-saldo-cartao`;
    document.getElementById('ajuste-cartao-val').value = saldo;
    openModal('modal-ajustar-cartao');
}
</script>
@endpush
