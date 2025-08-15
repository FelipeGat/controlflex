import React from 'react';
import './ModalConfirmacao.css';

function ModalConfirmacao({
  isOpen,
  onClose,
  onConfirm,
  onCancel,
  title,
  children,
  confirmText = 'Confirmar',
  cancelText = 'Cancelar'
}) {
  if (!isOpen) {
    return null;
  }

  // Ação do botão principal (ex: Excluir Todas)
  const handleConfirm = () => {
    if (onConfirm) onConfirm();
  };

  // Ação do botão secundário (ex: Excluir Esta)
  const handleCancel = () => {
    if (onCancel) onCancel();
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h3 className="modal-title">{title}</h3>
        {/* <button className="modal-close-button" onClick={onClose}>&times;</button> */}
        </div>
        <div className="modal-body">
          {children}
        </div>
        <div className="modal-footer">
          {/* Botão Secundário/Cancelar */}
          <button className="btn btn-secondary" onClick={handleCancel}>
            {cancelText}
          </button>
          {/* Botão Principal/Confirmar */}
          <button className="btn btn-danger" onClick={handleConfirm}>
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}

export default ModalConfirmacao;
