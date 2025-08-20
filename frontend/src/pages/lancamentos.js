import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { FaCheckCircle, FaSearch, FaFilter, FaDownload, FaEye, FaEdit, FaTrash, FaCheck } from 'react-icons/fa';
import './lancamentos.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import { FaTimes } from 'react-icons/fa';

// Componente de Notificação
const Notification = ({ message, type, onClose }) => {
  useEffect(() => {
    const timer = setTimeout(onClose, 5000);
    return () => clearTimeout(timer);
  }, [onClose]);

  return (
    <div className={`notification ${type}`}>
      {message}
      <button onClick={onClose} className="notification-close">×</button>
    </div>
  );
};

// Componente de Confirmação
// Adicionei um input para a data aqui
const ConfirmDialog = ({ isOpen, title, message, onConfirm, onCancel, showDateInput, onDateChange, dateValue }) => {
  if (!isOpen) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <h3>{title}</h3>
        <p>{message}</p>
        {showDateInput && (
          <div className="form-group">
            <label>Data de Pagamento/Recebimento:</label>
            <input
              type="date"
              className="form-control"
              value={dateValue}
              onChange={(e) => onDateChange(e.target.value)}
            />
          </div>
        )}
        <div className="modal-buttons">
          <button className="btn btn-danger" onClick={onConfirm}>
            Confirmar
          </button>
          <button className="btn btn-secondary" onClick={onCancel}>
            Cancelar
          </button>
        </div>
      </div>
    </div>
  );
};

// Componente de Filtros Avançados
const FiltrosAvancados = ({ filtros, onFiltrosChange, onBuscar, onLimpar }) => {
  return (
    <div className="filtros-avancados">
      <div className="filtros-linha">
        <div className="filtro-grupo">
          <label>Período:</label>
          <select
            value={filtros.periodo}
            onChange={(e) => onFiltrosChange({ ...filtros, periodo: e.target.value })}
            className="form-control"
          >
            <option value="today">Hoje</option>
            <option value="yesterday">Ontem</option>
            <option value="tomorrow">Amanhã</option>
            <option value="this_week">Esta Semana</option>
            <option value="last_week">Última Semana</option>
            <option value="this_month">Este Mês</option>
            <option value="last_month">Último Mês</option>
            <option value="next_month">Próximo Mês</option>
            <option value="this_year">Este Ano</option>
            <option value="last_year">Último Ano</option>
            <option value="next_year">Próximo Ano</option>
            <option value="personalizado">Período Personalizado</option>
          </select>
        </div>

        {filtros.periodo === 'personalizado' && (
          <>
            <div className="filtro-grupo">
              <label>Data Início:</label>
              <input
                type="date"
                value={filtros.dataInicio}
                onChange={(e) => onFiltrosChange({ ...filtros, dataInicio: e.target.value })}
                className="form-control"
              />
            </div>
            <div className="filtro-grupo">
              <label>Data Fim:</label>
              <input
                type="date"
                value={filtros.dataFim}
                onChange={(e) => onFiltrosChange({ ...filtros, dataFim: e.target.value })}
                className="form-control"
              />
            </div>
          </>
        )}

        <div className="filtro-grupo">
          <label>Tipo:</label>
          <select
            value={filtros.tipo}
            onChange={(e) => onFiltrosChange({ ...filtros, tipo: e.target.value })}
            className="form-control"
          >
            <option value="">Todos</option>
            <option value="receita">Receitas</option>
            <option value="despesa">Despesas</option>
          </select>
        </div>

        <div className="filtro-grupo">
          <label>Status:</label>
          <select
            value={filtros.status}
            onChange={(e) => onFiltrosChange({ ...filtros, status: e.target.value })}
            className="form-control"
          >
            <option value="">Todos</option>
            <option value="pago">Quitados</option>
            <option value="pendente">Pendentes</option>
            <option value="atrasado">Atrasados</option>
            <option value="hoje">Vencem Hoje</option>
          </select>
        </div>
      </div>

      <div className="filtros-linha">
        <div className="filtro-grupo filtro-busca">
          <label>Buscar:</label>
          <div className="input-with-icon">
            <FaSearch className="input-icon" />
            <input
              type="text"
              placeholder="Buscar por descrição..."
              value={filtros.busca}
              onChange={(e) => onFiltrosChange({ ...filtros, busca: e.target.value })}
              className="form-control"
            />
          </div>
        </div>

        <div className="filtros-acoes">
          <button className="btn btn-primary" onClick={onBuscar}>
            <FaFilter /> Filtrar
          </button>
          <button className="btn btn-secondary" onClick={onLimpar}>
            Limpar
          </button>
        </div>
      </div>
    </div>
  );
};

// Componente principal
export default function Lancamentos() {
  const navigate = useNavigate();
  const [usuario, setUsuario] = useState(null);
  const [lancamentos, setLancamentos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [notification, setNotification] = useState(null);
  const [confirmDialog, setConfirmDialog] = useState({ isOpen: false });
  const [paginacao, setPaginacao] = useState({
    pagina: 1,
    porPagina: 20,
    total: 0
  });
  // Adicionei um novo estado para o modal de quitação
  const [quitacaoDialog, setQuitacaoDialog] = useState({ isOpen: false, item: null, data: '' });

  const [filtros, setFiltros] = useState({
    periodo: 'this_month',
    dataInicio: '',
    dataFim: '',
    tipo: '',
    status: '',
    busca: ''
  });

  const [resumoTotais, setResumoTotais] = useState({
    total_receitas: 0,
    total_despesas: 0,
    saldo: 0,
  });

  // Verificar usuário logado
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  // Função para calcular datas baseado no período
  const calcularDatas = useCallback((periodo) => {
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    let inicio, fim;

    switch (periodo) {
      case 'today':
        inicio = fim = hoje.toISOString().split('T')[0];
        break;
      case 'yesterday':
        const ontem = new Date(hoje);
        ontem.setDate(hoje.getDate() - 1);
        inicio = fim = ontem.toISOString().split('T')[0];
        break;
      case 'tomorrow':
        const amanha = new Date(hoje);
        amanha.setDate(hoje.getDate() + 1);
        inicio = fim = amanha.toISOString().split('T')[0];
        break;
      case 'this_week':
        const primeiroDiaSemana = new Date(hoje);
        const diaSemana = hoje.getDay();
        primeiroDiaSemana.setDate(hoje.getDate() - diaSemana);
        const ultimoDiaSemana = new Date(primeiroDiaSemana);
        ultimoDiaSemana.setDate(primeiroDiaSemana.getDate() + 6);
        inicio = primeiroDiaSemana.toISOString().split('T')[0];
        fim = ultimoDiaSemana.toISOString().split('T')[0];
        break;
      case 'last_week':
        const inicioSemanaPassada = new Date(hoje);
        inicioSemanaPassada.setDate(hoje.getDate() - hoje.getDay() - 7);
        const fimSemanaPassada = new Date(inicioSemanaPassada);
        fimSemanaPassada.setDate(inicioSemanaPassada.getDate() + 6);
        inicio = inicioSemanaPassada.toISOString().split('T')[0];
        fim = fimSemanaPassada.toISOString().split('T')[0];
        break;
      case 'this_month':
        inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1).toISOString().split('T')[0];
        fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0).toISOString().split('T')[0];
        break;
      case 'last_month':
        inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1).toISOString().split('T')[0];
        fim = new Date(hoje.getFullYear(), hoje.getMonth(), 0).toISOString().split('T')[0];
        break;
      case 'next_month':
        inicio = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 1).toISOString().split('T')[0];
        fim = new Date(hoje.getFullYear(), hoje.getMonth() + 2, 0).toISOString().split('T')[0];
        break;
      case 'this_year':
        inicio = new Date(hoje.getFullYear(), 0, 1).toISOString().split('T')[0];
        fim = new Date(hoje.getFullYear(), 11, 31).toISOString().split('T')[0];
        break;
      case 'last_year':
        inicio = new Date(hoje.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
        fim = new Date(hoje.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
        break;
      case 'next_year':
        inicio = new Date(hoje.getFullYear() + 1, 0, 1).toISOString().split('T')[0];
        fim = new Date(hoje.getFullYear() + 1, 11, 31).toISOString().split('T')[0];
        break;
      default:
        inicio = fim = hoje.toISOString().split('T')[0];
        break;
    }

    return { inicio, fim };
  }, []);

  const fetchLancamentos = useCallback(async (filtrosAtuais = filtros, paginaAtual = 1) => {
    if (!usuario) return;

    setLoading(true);
    try {
      let dataInicio = filtrosAtuais.dataInicio;
      let dataFim = filtrosAtuais.dataFim;

      if (filtrosAtuais.periodo !== 'personalizado') {
        const datas = calcularDatas(filtrosAtuais.periodo);
        dataInicio = datas.inicio;
        dataFim = datas.fim;
      }

      const baseParams = {
        usuario_id: usuario.id,
        dataInicio: dataInicio,
        dataFim: dataFim,
        tipo: filtrosAtuais.tipo,
        status: filtrosAtuais.status,
        busca: filtrosAtuais.busca
      };

      const listPromise = axios.get(`${API_BASE_URL}/lancamentos.php`, {
        params: { ...baseParams, action: 'list', pagina: paginaAtual, por_pagina: paginacao.porPagina }
      });

      const resumoPromise = axios.get(`${API_BASE_URL}/lancamentos.php`, {
        params: { ...baseParams, action: 'resumo' }
      });

      const [listResponse, resumoResponse] = await Promise.all([listPromise, resumoPromise]);

      if (listResponse.data.success) {
        setLancamentos(listResponse.data.data || []);
        setPaginacao(prev => ({
          ...prev,
          pagina: listResponse.data.pagination?.pagina || paginaAtual,
          total: listResponse.data.pagination?.total || 0
        }));
      } else {
        throw new Error(listResponse.data.message || 'Erro ao carregar lançamentos');
      }

      if (resumoResponse.data.success) {
        setResumoTotais(resumoResponse.data.data);
      } else {
        console.error(resumoResponse.data.message || 'Erro ao carregar resumo de totais');
        setResumoTotais({ total_receitas: 0, total_despesas: 0, saldo: 0 });
      }

    } catch (error) {
      console.error('Erro ao carregar dados:', error);
      showNotification('Erro ao carregar dados: ' + error.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [usuario, calcularDatas, paginacao.porPagina, filtros]);

  // Carregar dados iniciais
  useEffect(() => {
    if (usuario) {
      fetchLancamentos();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [usuario]);

  // Função para mostrar notificação
  const showNotification = useCallback((message, type = 'success') => {
    setNotification({ message, type });
  }, []);

  // Função para quitar lançamento
  const quitarLancamento = async (id, tipo, dataReal) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/lancamentos.php`, {
        action: 'quitar',
        id,
        tipo,
        usuario_id: usuario.id,
        data_real: dataReal
      });

      if (response.data.success) {
        showNotification(response.data.message || `${tipo} quitado com sucesso!`);
        fetchLancamentos(filtros, paginacao.pagina);
        setQuitacaoDialog({ isOpen: false, item: null, data: '' }); // Fechar modal de quitação
      } else {
        throw new Error(response.data.message || 'Erro ao quitar lançamento');
      }
    } catch (error) {
      console.error('Erro ao quitar lançamento:', error);
      showNotification('Erro ao quitar lançamento: ' + error.message, 'error');
    }
  };

  // Função para visualizar detalhes
  const visualizarDetalhes = useCallback(async (id, tipo) => {
    try {
      const response = await axios.get(`${API_BASE_URL}/lancamentos.php`, {
        params: {
          action: 'detalhes',
          id,
          tipo,
          usuario_id: usuario.id
        }
      });

      if (response.data.success) {
        showNotification('Funcionalidade de visualização em desenvolvimento', 'info');
      } else {
        throw new Error(response.data.message || 'Erro ao carregar detalhes');
      }
    } catch (error) {
      console.error('Erro ao carregar detalhes:', error);
      showNotification('Erro ao carregar detalhes: ' + error.message, 'error');
    }
  }, [usuario, showNotification]);

  // Função para editar lançamento
  const editarLancamento = useCallback((id, tipo) => {
    if (tipo === 'receita') {
      navigate(`/receitas?edit=${id}`);
    } else {
      navigate(`/despesas?edit=${id}`);
    }
  }, [navigate]);

  // Função para excluir lançamento
  const excluirLancamento = useCallback(async (id, tipo) => {
    try {
      const response = await axios.delete(`${API_BASE_URL}/lancamentos.php`, {
        params: {
          action: 'excluir',
          id,
          tipo,
          usuario_id: usuario.id
        }
      });

      if (response.data.success) {
        showNotification(response.data.message || `${tipo} excluída com sucesso!`);
        fetchLancamentos(filtros, 1);
      } else {
        throw new Error(response.data.message || 'Erro ao excluir lançamento');
      }
    } catch (error) {
      console.error('Erro ao excluir lançamento:', error);
      showNotification('Erro ao excluir lançamento: ' + error.message, 'error');
    }
  }, [usuario, filtros, fetchLancamentos, showNotification]);

  // Função para confirmar quitação, agora usando o modal
  const confirmarQuitacao = useCallback((item) => {
    const hoje = new Date().toISOString().split('T')[0];
    const isVencimentoHoje = item.data_prevista === hoje;

    if (isVencimentoHoje) {
      // Se for hoje, quita diretamente sem o modal de data
      setConfirmDialog({
        isOpen: true,
        title: `Confirmar Quitação`,
        message: `Deseja quitar "${item.descricao}"?`,
        onConfirm: () => {
          quitarLancamento(item.id, item.tipo, hoje);
          setConfirmDialog({ isOpen: false });
        },
        onCancel: () => setConfirmDialog({ isOpen: false })
      });
    } else {
      // Abre o modal com o input de data
      setQuitacaoDialog({
        isOpen: true,
        item: item,
        data: hoje, // Data padrão
      });
    }
  }, [quitarLancamento]);

  // Função para confirmar exclusão
  const confirmarExclusao = useCallback((id, tipo, descricao) => {
    setConfirmDialog({
      isOpen: true,
      title: `Confirmar Exclusão`,
      message: `Deseja realmente excluir "${descricao}"?`,
      onConfirm: () => {
        excluirLancamento(id, tipo);
        setConfirmDialog({ isOpen: false });
      },
      onCancel: () => setConfirmDialog({ isOpen: false })
    });
  }, [excluirLancamento]);

  // Função para aplicar filtros
  const aplicarFiltros = useCallback(() => {
    fetchLancamentos(filtros, 1);
  }, [filtros, fetchLancamentos]);

  // Função para limpar filtros
  const limparFiltros = useCallback(() => {
    const filtrosLimpos = {
      periodo: 'this_month',
      dataInicio: '',
      dataFim: '',
      tipo: '',
      status: '',
      busca: ''
    };
    setFiltros(filtrosLimpos);
    fetchLancamentos(filtrosLimpos, 1);
  }, [fetchLancamentos]);

  // Função para determinar classe da linha
  const getRowClass = useCallback((status) => {
    switch (status) {
      case 'pago':
        return 'linha-verde';
      case 'hoje':
        return 'linha-amarela';
      case 'atrasado':
        return 'linha-vermelha';
      default:
        return 'linha-normal';
    }
  }, []);

  // Função para formatar moeda
  const formatarMoeda = useCallback((valor) => {
    return parseFloat(valor).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
  }, []);

  // Função para formatar data
  const formatarData = useCallback((data) => {
    if (!data) return '-';
    return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
  }, []);

  // Função para exportar dados
  const exportarDados = useCallback(() => {
    showNotification('Funcionalidade de exportação em desenvolvimento', 'info');
  }, [showNotification]);

  // Calcular páginas
  const totalPaginas = Math.ceil(paginacao.total / paginacao.porPagina);

  // Função para lidar com o clique no status
  const handleStatusClick = (item) => {
    if (item.data_real) {
      setConfirmDialog({
        isOpen: true,
        title: 'Confirmação',
        message: `Este lançamento já está marcado como ${item.tipo === 'receita' ? 'Recebido' : 'Pago'}. Deseja cancelar?`,
        onConfirm: () => {
          desquitarLancamento(item.id, item.tipo);
          setConfirmDialog({ isOpen: false });
        },
        onCancel: () => setConfirmDialog({ isOpen: false })
      });
    } else {
      confirmarQuitacao(item);
    }
  };

  // Função para desquitar lançamento
  const desquitarLancamento = async (id, tipo) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/lancamentos.php`, {
        action: 'desquitar',
        id,
        tipo,
        usuario_id: usuario.id
      });

      if (response.data.success) {
        showNotification(response.data.message || `${tipo} desquitado com sucesso!`);
        fetchLancamentos(filtros, paginacao.pagina);
      } else {
        throw new Error(response.data.message || 'Erro ao desquitar lançamento');
      }
    } catch (error) {
      console.error('Erro ao desquitar lançamento:', error);
      showNotification('Erro ao desquitar lançamento: ' + error.message, 'error');
    }
  };


  if (loading && lancamentos.length === 0) {
    return (
      <div className="page-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="page-container">
      {notification && (
        <Notification
          message={notification.message}
          type={notification.type}
          onClose={() => setNotification(null)}
        />
      )}

      {/* Confirmação de Exclusão (o ConfirmDialog antigo) */}
      <ConfirmDialog {...confirmDialog} />

      {/* Novo modal para Quitação (com input de data) */}
      <ConfirmDialog
        isOpen={quitacaoDialog.isOpen}
        title={`Confirmar ${quitacaoDialog.item?.tipo === 'receita' ? 'Recebimento' : 'Pagamento'}`}
        message={`Selecione a data real para o lançamento: "${quitacaoDialog.item?.descricao}"`}
        showDateInput={true}
        dateValue={quitacaoDialog.data}
        onDateChange={(data) => setQuitacaoDialog({ ...quitacaoDialog, data })}
        onConfirm={() => quitarLancamento(quitacaoDialog.item.id, quitacaoDialog.item.tipo, quitacaoDialog.data)}
        onCancel={() => setQuitacaoDialog({ isOpen: false, item: null, data: '' })}
      />

      <div className="content-card">
        <div className="page-header">
          <h1 className="page-title">📌 Lançamentos</h1>
          <div className="page-actions">
            <button className="btn btn-secondary" onClick={exportarDados}>
              <FaDownload /> Exportar
            </button>
          </div>
        </div>

        <FiltrosAvancados
          filtros={filtros}
          onFiltrosChange={setFiltros}
          onBuscar={aplicarFiltros}
          onLimpar={limparFiltros}
        />

        <div className="resumo-cards">
          <div className="resumo-card receitas">
            <h3>Total Receitas</h3>
            <span>{formatarMoeda(resumoTotais.total_receitas)}</span>
          </div>
          <div className="resumo-card despesas">
            <h3>Total Despesas</h3>
            <span>{formatarMoeda(resumoTotais.total_despesas)}</span>
          </div>
          <div className="resumo-card saldo">
            <h3>Saldo</h3>
            <span className={resumoTotais.saldo >= 0 ? 'positivo' : 'negativo'}>
              {formatarMoeda(resumoTotais.saldo)}
            </span>
          </div>
        </div>

        <div className="table-container">
          {loading && (
            <div className="loading-overlay">
              <Spinner />
            </div>
          )}

          <div className="table-wrapper">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Tipo</th>
                  <th>Descrição</th>
                  <th>Valor</th>
                  <th>Familiar</th>
                  <th>Data Prevista</th>
                  <th>Data Real</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                {!loading && lancamentos.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="empty-state">
                      Nenhum lançamento encontrado para os filtros aplicados.
                    </td>
                  </tr>
                ) : (
                  lancamentos.map((item) => (
                    <tr key={`${item.tipo}-${item.id}`} className={getRowClass(item.status)}>
                      <td>
                        <span className={`tipo-badge ${item.tipo}`}>
                          {item.tipo === 'receita' ? '💰 Receita' : '💸 Despesa'}
                        </span>
                      </td>
                      <td className="descricao-cell">{item.descricao}</td>
                      <td className={`valor-cell ${item.tipo}`}>
                        {formatarMoeda(item.valor)}
                      </td>
                      <td>{item.familiar || '-'}</td>
                      <td>{formatarData(item.data_prevista)}</td>
                      <td>{formatarData(item.data_real)}</td>
                      <td>
                        <span className={`status-badge ${item.status}`}>
                          {item.status.toUpperCase()}
                        </span>
                      </td>
                      <td>
                        <div className="table-actions">
                          <button
                            className={`btn-status ${item.data_real ? 'btn-warning' : 'btn-success'}`}
                            onClick={() => handleStatusClick(item)}
                            title={
                              item.data_real
                                ? `Cancelar ${item.tipo === 'receita' ? 'Recebimento' : 'Pagamento'}`
                                : item.tipo === 'receita'
                                  ? 'Marcar como Recebido'
                                  : 'Marcar como Pago'
                            }
                          >
                            {item.data_real ? (
                              <>
                                <FaTimes style={{ marginRight: '6px' }} />
                                {item.tipo === 'receita' ? 'Recebido' : 'Pago'}
                              </>
                            ) : (
                              <>
                                <FaCheck style={{ marginRight: '6px' }} />
                                {item.tipo === 'receita' ? 'Receber' : 'Pagar'}
                              </>
                            )}
                          </button>
                          <button
                            className="btn-action btn-info"
                            onClick={() => visualizarDetalhes(item.id, item.tipo)}
                            title="Visualizar"
                          >
                            <FaEye />
                          </button>

                          <button
                            className="btn-action btn-warning"
                            onClick={() => editarLancamento(item.id, item.tipo)}
                            title="Editar"
                          >
                            <FaEdit />
                          </button>

                          <button
                            className="btn-action btn-danger"
                            onClick={() => confirmarExclusao(item.id, item.tipo, item.descricao)}
                            title="Excluir"
                          >
                            <FaTrash />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {totalPaginas > 1 && (
            <div className="pagination">
              <button
                className="btn btn-secondary"
                disabled={paginacao.pagina === 1}
                onClick={() => fetchLancamentos(filtros, paginacao.pagina - 1)}
              >
                Anterior
              </button>

              <span className="pagination-info">
                Página {paginacao.pagina} de {totalPaginas}
                ({paginacao.total} registros)
              </span>

              <button
                className="btn btn-secondary"
                disabled={paginacao.pagina === totalPaginas}
                onClick={() => fetchLancamentos(filtros, paginacao.pagina + 1)}
              >
                Próxima
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}