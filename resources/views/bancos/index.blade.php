@extends('layouts.main')
@section('title', 'Contas Bancárias')
@section('page-title', 'Contas Bancárias')

@section('content')

<div class="section-header mb-4">
    <span></span>
    <button class="btn btn-primary" onclick="openModal('modal-novo-banco')">
        <i class="fa-solid fa-plus"></i> Nova Conta
    </button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-bottom:24px;">
    @forelse($bancos as $banco)
        <div class="card" style="border-top: 3px solid {{ $banco->tipo_conta === 'Cartão de Crédito' ? '#d97706' : 'var(--color-primary)' }};">
            <div class="d-flex justify-between align-center mb-3">
                <div>
                    <div class="fw-600" style="font-size:15px;">{{ $banco->nome }}</div>
                    <span class="badge {{ $banco->tipo_conta === 'Cartão de Crédito' ? 'badge-amber' : 'badge-blue' }}" style="margin-top:3px;">
                        {{ $banco->tipo_conta }}
                    </span>
                    @if($banco->titular)
                        <div style="font-size:11px;margin-top:3px;" class="text-subtle">
                            <i class="fa-solid fa-user"></i> {{ $banco->titular->nome }}
                        </div>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button onclick="editarBanco({{ $banco->id }}, {{ $banco->toJson() }})" class="btn btn-ghost btn-icon btn-sm" title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form method="POST" action="{{ route('bancos.destroy', $banco) }}" onsubmit="return confirm('Excluir esta conta?')" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-icon btn-sm text-red" title="Excluir">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div style="background:var(--color-bg);border-radius:6px;padding:10px 12px;margin-bottom:10px;">
                <div class="kpi-label">Saldo</div>
                <div class="fw-700 {{ $banco->saldo >= 0 ? 'text-green' : 'text-red' }}" style="font-size:1.4rem;">
                    R$ {{ number_format($banco->saldo, 2, ',', '.') }}
                </div>
            </div>

            @if($banco->cheque_especial > 0)
                <div style="font-size:12px;margin-bottom:8px;" class="text-muted">
                    Cheque especial: R$ {{ number_format($banco->cheque_especial, 2, ',', '.') }}
                </div>
            @endif

            @if($banco->limite_cartao > 0)
                @php $perc = $banco->limite_cartao > 0 ? ($banco->saldo_cartao / $banco->limite_cartao) * 100 : 0; @endphp
                <div style="margin-bottom:10px;">
                    <div class="d-flex justify-between" style="font-size:12px;margin-bottom:4px;">
                        <span class="text-muted">Cartão utilizado</span>
                        <span class="fw-600">R$ {{ number_format($banco->saldo_cartao, 2, ',', '.') }} / {{ number_format($banco->limite_cartao, 2, ',', '.') }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width:{{ min($perc,100) }}%;background:{{ $perc > 80 ? '#dc2626' : ($perc > 50 ? '#d97706' : '#16a34a') }};"></div>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2">
                <button onclick="ajustarSaldo({{ $banco->id }}, {{ $banco->saldo }})" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
                    <i class="fa-solid fa-sliders"></i> Saldo
                </button>
                @if($banco->limite_cartao > 0)
                    <button onclick="ajustarSaldoCartao({{ $banco->id }}, {{ $banco->saldo_cartao }})" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">
                        <i class="fa-solid fa-credit-card"></i> Cartão
                    </button>
                @endif
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <i class="fa-solid fa-building-columns"></i>
                <p>Nenhuma conta cadastrada.<br>Clique em "Nova Conta" para começar.</p>
            </div>
        </div>
    @endforelse
</div>

{{-- Modal Nova Conta --}}
<div class="modal-backdrop" id="modal-novo-banco">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-building-columns" style="color:var(--color-primary);"></i>
            <h3>Nova Conta</h3>
            <button class="modal-close" onclick="closeModal('modal-novo-banco')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('bancos.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: Sicoob, Carteira...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_conta" class="form-control" required>
                            <option value="Conta Corrente">Conta Corrente</option>
                            <option value="Poupança">Poupança</option>
                            <option value="Dinheiro">Dinheiro (Carteira)</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Titular</label>
                        <select name="titular_id" class="form-control">
                            <option value="">— Nenhum —</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Saldo Inicial</label>
                        <input type="number" name="saldo" step="0.01" value="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cheque Especial</label>
                        <input type="number" name="cheque_especial" step="0.01" value="0" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cartão</label>
                        <input type="number" name="limite_cartao" step="0.01" value="0" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Agência</label>
                        <input type="text" name="agencia" class="form-control" placeholder="0000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Conta</label>
                        <input type="text" name="conta" class="form-control" placeholder="00000-0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-novo-banco')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div class="modal-backdrop" id="modal-editar-banco">
    <div class="modal">
        <div class="modal-header">
            <i class="fa-solid fa-pen" style="color:var(--color-primary);"></i>
            <h3>Editar Conta</h3>
            <button class="modal-close" onclick="closeModal('modal-editar-banco')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-editar-banco">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" id="b-edit-nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_conta" id="b-edit-tipo" class="form-control" required>
                            <option value="Conta Corrente">Conta Corrente</option>
                            <option value="Poupança">Poupança</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Titular</label>
                        <select name="titular_id" id="b-edit-titular" class="form-control">
                            <option value="">— Nenhum —</option>
                            @foreach($familiares as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Saldo</label>
                        <input type="number" name="saldo" id="b-edit-saldo" step="0.01" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cheque Especial</label>
                        <input type="number" name="cheque_especial" id="b-edit-cheque" step="0.01" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Limite Cartão</label>
                        <input type="number" name="limite_cartao" id="b-edit-limite" step="0.01" min="0" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-editar-banco')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Atualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ajustar Saldo --}}
<div class="modal-backdrop" id="modal-ajustar-saldo">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <i class="fa-solid fa-sliders" style="color:var(--color-primary);"></i>
            <h3>Ajustar Saldo</h3>
            <button class="modal-close" onclick="closeModal('modal-ajustar-saldo')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-ajustar-saldo">
                @csrf
                <div class="form-group">
                    <label class="form-label">Novo Saldo</label>
                    <input type="number" name="saldo" id="ajuste-saldo-val" step="0.01" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-ajustar-saldo')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ajustar Cartão --}}
<div class="modal-backdrop" id="modal-ajustar-cartao">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <i class="fa-solid fa-credit-card" style="color:#d97706;"></i>
            <h3>Ajustar Saldo do Cartão</h3>
            <button class="modal-close" onclick="closeModal('modal-ajustar-cartao')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="form-ajustar-cartao">
                @csrf
                <div class="form-group">
                    <label class="form-label">Valor Utilizado no Cartão</label>
                    <input type="number" name="saldo_cartao" id="ajuste-cartao-val" step="0.01" min="0" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal('modal-ajustar-cartao')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
                </div>
            </form>
        </div>
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
