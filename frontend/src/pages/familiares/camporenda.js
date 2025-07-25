import React from 'react';

export default function CampoRenda({ renda, onChange, onRemove }) {
  // renda = { id, tipoRenda, valor }

  return (
    <div className="renda-item">
      <input
        type="text"
        name="tipoRenda"
        placeholder="Tipo de Renda"
        value={renda.tipoRenda || ''}
        onChange={(e) => onChange(renda.id, 'tipoRenda', e.target.value)}
        className="form-control"
      />
      <input
        type="number"
        name="valor"
        placeholder="Valor"
        value={renda.valor || ''}
        onChange={(e) => onChange(renda.id, 'valor', e.target.value)}
        className="form-control"
        min="0"
        step="0.01"
      />
      <button
        type="button"
        className="btn-trash-renda"
        onClick={() => onRemove(renda.id)}
        title="Remover Renda"
      >
        🗑️
      </button>
    </div>
  );
}
