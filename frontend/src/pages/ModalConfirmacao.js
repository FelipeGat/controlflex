import React from 'react';
import './ModalConfirmacao.css';

function ModalConfirmacao({
  isOpen,
  onClose,
  onConfirm,
  title,
  children,
  confirmText = 'Confirmar',
  cancelText = 'Cancelar'
}) {
  if (!isOpen) {
    return null;
  }

  // ========= INÍCIO DA CORREÇÃO =========
  // A função handleConfirm agora chama APENAS onConfirm.
  // A responsabilidade de fechar o modal foi movida para dentro
  // da própria função onConfirm no componente Despesas.js.
  const handleConfirm = () => {
    if (onConfirm) {
      onConfirm();
    }
  };
  // ========= FIM DA CORREÇÃO =========

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h3 className="modal-title">{title}</h3>
          <button className="modal-close-button" onClick={onClose}>&times;</button>
        </div>
        <div className="modal-body">
          {children}
        </div>
        <div className="modal-footer">
          <button className="btn btn-secondary" onClick={onClose}>
            {cancelText}
          </button>
          <button className="btn btn-danger" onClick={handleConfirm}>
            {confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}

export default ModalConfirmacao;
