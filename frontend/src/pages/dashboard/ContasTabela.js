import React, { useState, useMemo } from 'react';
import './ContasTabela.css';

function ContasTabela({ contas, onEdit, onDelete, exibirAcoes = true }) {
  const [sortConfig, setSortConfig] = useState({ key: 'vencimento', direction: 'asc' });
  const [filtroCategoria, setFiltroCategoria] = useState('todas');
  const [paginaReceitas, setPaginaReceitas] = useState(1);
  const [paginaDespesas, setPaginaDespesas] = useState(1);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const ITENS_POR_PAGINA = 10;

  const { receitasFiltradas, despesasFiltradas } = useMemo(() => {
    const receitas = contas.filter(c => c.tipo === 'receber');
    const despesas = contas.filter(c => c.tipo === 'pagar');

    const ordenarArray = (array) => {
      return [...array].sort((a, b) => {
        let aVal = a[sortConfig.key];
        let bVal = b[sortConfig.key];

        if (['vencimento', 'data_compra', 'data_recebimento'].includes(sortConfig.key)) {
          aVal = new Date(aVal);
          bVal = new Date(bVal);
          return sortConfig.direction === 'asc' ? aVal - bVal : bVal - aVal;
        }

        if (sortConfig.key === 'valor') {
          aVal = parseFloat(aVal.toString().replace(',', '.'));
          bVal = parseFloat(bVal.toString().replace(',', '.'));
          return sortConfig.direction === 'asc' ? aVal - bVal : bVal - aVal;
        }

        if (typeof aVal === 'string') aVal = aVal.toLowerCase();
        if (typeof bVal === 'string') bVal = bVal.toLowerCase();

        if (aVal < bVal) return sortConfig.direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortConfig.direction === 'asc' ? 1 : -1;
        return 0;
      });
    };

    return {
      receitasFiltradas: ordenarArray(receitas),
      despesasFiltradas: ordenarArray(despesas)
    };
  }, [contas, sortConfig]);

  const receitasPaginadas = useMemo(() => {
    const inicio = (paginaReceitas - 1) * ITENS_POR_PAGINA;
    return receitasFiltradas.slice(inicio, inicio + ITENS_POR_PAGINA);
  }, [receitasFiltradas, paginaReceitas]);

  const despesasPaginadas = useMemo(() => {
    const inicio = (paginaDespesas - 1) * ITENS_POR_PAGINA;
    return despesasFiltradas.slice(inicio, inicio + ITENS_POR_PAGINA);
  }, [despesasFiltradas, paginaDespesas]);

  const totalPaginasReceitas = Math.ceil(receitasFiltradas.length / ITENS_POR_PAGINA);
  const totalPaginasDespesas = Math.ceil(despesasFiltradas.length / ITENS_POR_PAGINA);

  const onSort = (key) => {
    setSortConfig(prev => {
      if (prev.key === key) {
        return { key, direction: prev.direction === 'asc' ? 'desc' : 'asc' };
      } else {
        return { key, direction: 'asc' };
      }
    });
  };

  const handleEdit = (conta) => {
    if (onEdit) onEdit(conta);
  };

  const handleDeleteClick = (conta) => {
    setItemToDelete(conta);
    setShowDeleteModal(true);
  };

  const confirmDelete = () => {
    if (onDelete && itemToDelete) onDelete(itemToDelete);
    setShowDeleteModal(false);
    setItemToDelete(null);
  };

  const cancelDelete = () => {
    setShowDeleteModal(false);
    setItemToDelete(null);
  };

  const renderPaginacao = (paginaAtual, totalPaginas, setPagina) => {
    if (totalPaginas <= 1) return null;

    return (
      <div className="paginacao-container">
        <button
          className="btn-paginacao"
          onClick={() => setPagina(paginaAtual - 1)}
          disabled={paginaAtual === 1}
        >
          ‹
        </button>
        <span className="paginacao-info">
          Página {paginaAtual} de {totalPaginas}
        </span>
        <button
          className="btn-paginacao"
          onClick={() => setPagina(paginaAtual + 1)}
          disabled={paginaAtual === totalPaginas}
        >
          ›
        </button>
      </div>
    );
  };

  const renderTabela = (dados, tipo, cor) => {
    if (dados.length === 0) {
      return <div className="tabela-vazia"><p>Nenhuma {tipo.toLowerCase()} encontrada.</p></div>;
    }

    return (
      <div className="card-box">
        <table className="table contas-tabela">
          <thead className={`thead-${cor}`}>
            <tr>
              <th onClick={() => onSort('categoria_nome')}>Categoria</th>
              <th onClick={() => onSort('fornecedor')}>{tipo === 'Receita' ? 'Origem' : 'Fornecedor'}</th>
              <th onClick={() => onSort('familiar')}>{tipo === 'Receita' ? 'Quem Recebeu' : 'Quem Comprou'}</th>
              <th onClick={() => onSort('valor')}>Valor</th>
              <th onClick={() => onSort('vencimento')}>{tipo === 'Receita' ? 'Data Recebimento' : 'Data Compra'}</th>
              <th>Observações</th>
              <th>Forma de {tipo === 'Receita' ? 'Recebimento' : 'Pagamento'}</th>
              {exibirAcoes && <th>Ações</th>}
            </tr>
          </thead>
          <tbody>
            {dados.map(conta => (
              <tr key={conta.id}>
                <td>{conta.categoria_nome || '-'}</td>
                <td>{conta.fornecedor || '-'}</td>
                <td>{conta.familiar || '-'}</td>
                <td className={`valor-${tipo.toLowerCase()}`}>
                  {Number(conta.valor).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                  })}
                </td>
                <td>{conta.vencimento ? new Date(conta.vencimento).toLocaleDateString('pt-BR') : '-'}</td>
                <td>{conta.observacoes || '-'}</td>
                <td>{conta.forma_pagamento || '-'}</td>
                {exibirAcoes && (
                  <td>
                    <div className="table-buttons">
                      <button
                        type="button"
                        onClick={() => handleEdit(conta)}
                        title="Editar"
                        className="btn-icon btn-edit"
                      >
                        <i className="fas fa-pen"></i>
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDeleteClick(conta)}
                        title="Excluir"
                        className="btn-icon btn-trash"
                      >
                        <i className="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    );
  };

  const contasParaExibir = () => {
    switch (filtroCategoria) {
      case 'receitas':
        return { receitas: receitasPaginadas, despesas: [] };
      case 'despesas':
        return { receitas: [], despesas: despesasPaginadas };
      default:
        return { receitas: receitasPaginadas, despesas: despesasPaginadas };
    }
  };

  const { receitas, despesas } = contasParaExibir();

  return (
    <div className="contas-tabela-container">
      <div className="filtro-container">
        <label htmlFor="filtro-categoria">Filtrar por:</label>
        <select
          id="filtro-categoria"
          value={filtroCategoria}
          onChange={(e) => {
            setFiltroCategoria(e.target.value);
            setPaginaReceitas(1);
            setPaginaDespesas(1);
          }}
          className="form-control filtro-select"
        >
          <option value="todas">Todas as Contas</option>
          <option value="receitas">Apenas Receitas</option>
          <option value="despesas">Apenas Despesas</option>
        </select>
      </div>

      {(filtroCategoria === 'todas' || filtroCategoria === 'receitas') && (
        <div className="secao-tabela">
          <h4 
              className="titulo-secao receitas" 
              onClick={() => window.location.href = '/controleflex/receitas'} 
              style={{ cursor: 'pointer', textDecoration: 'underline' }}
            >
              💰 Receitas
          </h4>
          {renderTabela(receitas, 'Receita', 'success')}
          {renderPaginacao(paginaReceitas, totalPaginasReceitas, setPaginaReceitas)}
        </div>
      )}

      {(filtroCategoria === 'todas' || filtroCategoria === 'despesas') && (
        <div className="secao-tabela">
          <h4 
              className="titulo-secao despesas" 
              onClick={() => window.location.href = '/controleflex/despesas'} 
              style={{ cursor: 'pointer', textDecoration: 'underline' }}
            >
              📤 Despesas
          </h4>

          {renderTabela(despesas, 'Despesa', 'danger')}
          {renderPaginacao(paginaDespesas, totalPaginasDespesas, setPaginaDespesas)}
        </div>
      )}
    </div>
  );
}

export default ContasTabela;
