import React from 'react';

export default function CampoBanco({ banco, onChange, onRemove }) {
  // banco = { id, nomeBanco, agencia, conta, limiteCheque, limiteCartao }

  return (
    <div className="banco-item">
      <input
        type="text"
        name="nomeBanco"
        placeholder="Banco"
        value={banco.nomeBanco || ''}
        onChange={(e) => onChange(banco.id, 'nomeBanco', e.target.value)}
        className="form-control"
      />
      <input
        type="text"
        name="agencia"
        placeholder="Agência"
        value={banco.agencia || ''}
        onChange={(e) => onChange(banco.id, 'agencia', e.target.value)}
        className="form-control"
      />
      <input
        type="text"
        name="conta"
        placeholder="Conta"
        value={banco.conta || ''}
        onChange={(e) => onChange(banco.id, 'conta', e.target.value)}
        className="form-control"
      />
      <input
        type="number"
        name="limiteCheque"
        placeholder="Limite Cheque"
        value={banco.limiteCheque || ''}
        onChange={(e) => onChange(banco.id, 'limiteCheque', e.target.value)}
        className="form-control"
        min="0"
      />
      <input
        type="number"
        name="limiteCartao"
        placeholder="Limite Cartão"
        value={banco.limiteCartao || ''}
        onChange={(e) => onChange(banco.id, 'limiteCartao', e.target.value)}
        className="form-control"
        min="0"
      />
      <button
        type="button"
        className="btn-trash-banco"
        onClick={() => onRemove(banco.id)}
        title="Remover Banco"
      >
        🗑️
      </button>
    </div>
  );
}
