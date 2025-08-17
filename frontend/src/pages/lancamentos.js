import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { FaCheckCircle, FaSearch, FaFilter, FaDownload, FaEye, FaEdit, FaTrash } from 'react-icons/fa';
import './lancamentos.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

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
const ConfirmDialog = ({ isOpen, title, message, onConfirm, onCancel }) => {
  if (!isOpen) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <h3>{title}</h3>
        <p>{message}</p>
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

// Componente de Filtros Avançados - CORRIGIDO
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
  const [loading, setLoading] = useState(false);
  const [notification, setNotification] = useState(null);
  const [confirmDialog, setConfirmDialog] = useState({ isOpen: false });
  const [paginacao, setPaginacao] = useState({
    pagina: 1,
    porPagina: 20,
    total: 0
  });

  // Estados de filtros - CORRIGIDO
  const [filtros, setFiltros] = useState({
    periodo: 'this_month',
    dataInicio: '',
    dataFim: '',
    tipo: '',
    status: '',
    busca: ''
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

  // Função para calcular datas baseado no período - NOVA
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

  // Função para buscar lançamentos - CORRIGIDA
  const fetchLancamentos = useCallback(async (filtrosAtuais = filtros, paginaAtual = 1) => {
    if (!usuario) return;

    try {
      setLoading(true);
      
      // Calcular datas se não for período personalizado
      let dataInicio = filtrosAtuais.dataInicio;
      let dataFim = filtrosAtuais.dataFim;
      
      if (filtrosAtuais.periodo !== 'personalizado') {
        const datas = calcularDatas(filtrosAtuais.periodo);
        dataInicio = datas.inicio;
        dataFim = datas.fim;
      }
      
      const params = {
        action: 'list',
        usuario_id: usuario.id,
        pagina: paginaAtual,
        por_pagina: paginacao.porPagina,
        periodo: filtrosAtuais.periodo === 'personalizado' ? 'personalizado' : 'personalizado',
        dataInicio: dataInicio,
        dataFim: dataFim,
        tipo: filtrosAtuais.tipo,
        status: filtrosAtuais.status,
        busca: filtrosAtuais.busca
      };

      const response = await axios.get(`${API_BASE_URL}/lancamentos.php`, { params });
      
      if (response.data.success) {
        setLancamentos(response.data.data || []);
        setPaginacao(prev => ({
          ...prev,
          pagina: response.data.pagination?.pagina || paginaAtual,
          total: response.data.pagination?.total || 0
        }));
      } else {
        throw new Error(response.data.message || 'Erro ao carregar lançamentos');
      }
    } catch (error) {
      console.error('Erro ao carregar lançamentos:', error);
      showNotification('Erro ao carregar lançamentos: ' + error.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [usuario, calcularDatas, paginacao.porPagina]);

  // Carregar dados iniciais
  useEffect(() => {
    if (usuario) {
      fetchLancamentos();
    }
  }, [usuario]);

  // Função para mostrar notificação
  const showNotification = useCallback((message, type = 'success') => {
    setNotification({ message, type });
  }, []);

  // Função para quitar lançamento - CORRIGIDA
  const quitarLancamento = useCallback(async (id, tipo) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/lancamentos.php`, {
        action: 'quitar',
        id,
        tipo,
        usuario_id: usuario.id
      });

      if (response.data.success) {
        showNotification(response.data.message || `${tipo} quitada com sucesso!`);
        fetchLancamentos(filtros, paginacao.pagina);
      } else {
        throw new Error(response.data.message || 'Erro ao quitar lançamento');
      }
    } catch (error) {
      console.error('Erro ao quitar lançamento:', error);
      showNotification('Erro ao quitar lançamento: ' + error.message, 'error');
    }
  }, [usuario, filtros, paginacao.pagina, fetchLancamentos, showNotification]);

  // Função para visualizar detalhes - NOVA
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
        // Implementar modal de detalhes ou navegar para página de detalhes
        showNotification('Funcionalidade de visualização em desenvolvimento', 'info');
      } else {
        throw new Error(response.data.message || 'Erro ao carregar detalhes');
      }
    } catch (error) {
      console.error('Erro ao carregar detalhes:', error);
      showNotification('Erro ao carregar detalhes: ' + error.message, 'error');
    }
  }, [usuario, showNotification]);

  // Função para editar lançamento - NOVA
  const editarLancamento = useCallback((id, tipo) => {
    // Navegar para a tela de edição correspondente
    if (tipo === 'receita') {
      navigate(`/receitas?edit=${id}`);
    } else {
      navigate(`/despesas?edit=${id}`);
    }
  }, [navigate]);

  // Função para excluir lançamento - NOVA
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
        fetchLancamentos(filtros, paginacao.pagina);
      } else {
        throw new Error(response.data.message || 'Erro ao excluir lançamento');
      }
    } catch (error) {
      console.error('Erro ao excluir lançamento:', error);
      showNotification('Erro ao excluir lançamento: ' + error.message, 'error');
    }
  }, [usuario, filtros, paginacao.pagina, fetchLancamentos, showNotification]);

  // Função para confirmar quitação
  const confirmarQuitacao = useCallback((id, tipo, descricao) => {
    setConfirmDialog({
      isOpen: true,
      title: `Confirmar ${tipo === 'receita' ? 'Recebimento' : 'Pagamento'}`,
      message: `Deseja realmente ${tipo === 'receita' ? 'receber' : 'pagar'} "${descricao}"?`,
      onConfirm: () => {
        quitarLancamento(id, tipo);
        setConfirmDialog({ isOpen: false });
      },
      onCancel: () => setConfirmDialog({ isOpen: false })
    });
  }, [quitarLancamento]);

  // Função para confirmar exclusão - NOVA
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

  // Função para limpar filtros - CORRIGIDA
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
    return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
  }, []);

  // Função para exportar dados
  const exportarDados = useCallback(() => {
    // Implementar exportação para CSV/Excel
    showNotification('Funcionalidade de exportação em desenvolvimento', 'info');
  }, [showNotification]);

  // Calcular totais
  const totais = useMemo(() => {
    return lancamentos.reduce((acc, item) => {
      if (item.tipo === 'receita') {
        acc.receitas += parseFloat(item.valor);
      } else {
        acc.despesas += parseFloat(item.valor);
      }
      return acc;
    }, { receitas: 0, despesas: 0 });
  }, [lancamentos]);

  // Calcular páginas
  const totalPaginas = Math.ceil(paginacao.total / paginacao.porPagina);

  if (loading && lancamentos.length === 0) {
    return (
      <div className="page-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="page-container">
      {/* Notificação */}
      {notification && (
        <Notification
          message={notification.message}
          type={notification.type}
          onClose={() => setNotification(null)}
        />
      )}

      {/* Dialog de Confirmação */}
      <ConfirmDialog {...confirmDialog} />

      <div className="content-card">
        <div className="page-header">
          <h1 className="page-title">📌 Lançamentos</h1>
          <div className="page-actions">
            <button className="btn btn-secondary" onClick={exportarDados}>
              <FaDownload /> Exportar
            </button>
          </div>
        </div>

        {/* Filtros Avançados */}
        <FiltrosAvancados
          filtros={filtros}
          onFiltrosChange={setFiltros}
          onBuscar={aplicarFiltros}
          onLimpar={limparFiltros}
        />

        {/* Resumo */}
        <div className="resumo-cards">
          <div className="resumo-card receitas">
            <h3>Total Receitas</h3>
            <span>{formatarMoeda(totais.receitas)}</span>
          </div>
          <div className="resumo-card despesas">
            <h3>Total Despesas</h3>
            <span>{formatarMoeda(totais.despesas)}</span>
          </div>
          <div className="resumo-card saldo">
            <h3>Saldo</h3>
            <span className={totais.receitas - totais.despesas >= 0 ? 'positivo' : 'negativo'}>
              {formatarMoeda(totais.receitas - totais.despesas)}
            </span>
          </div>
        </div>

        {/* Tabela */}
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
                {lancamentos.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="empty-state">
                      {loading ? 'Carregando...' : 'Nenhum lançamento encontrado'}
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
                      <td>{item.data_real ? formatarData(item.data_real) : '-'}</td>
                      <td>
                        <span className={`status-badge ${item.status}`}>
                          {item.status.toUpperCase()}
                        </span>
                      </td>
                      <td>
                        <div className="table-actions">
                          {item.status !== 'pago' && (
                            <button
                              className="btn-action btn-success"
                              onClick={() => confirmarQuitacao(item.id, item.tipo, item.descricao)}
                              title={item.tipo === 'despesa' ? 'Pagar' : 'Receber'}
                            >
                              <FaCheckCircle />
                            </button>
                          )}
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

          {/* Paginação */}
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

