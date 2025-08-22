import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { FaSearch, FaFilter, FaDownload, FaEye, FaEdit, FaTrash, FaCheck, FaTimes } from 'react-icons/fa';
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

// Componente de Confirmação (AGORA SÓ RENDERIZA O QUE RECEBE)
const ConfirmDialog = ({
  isOpen,
  title,
  message,
  onConfirm,
  onCancel,
  showDateInput,
  onDateChange,
  dateValue,
  showContaInput,
  contasDisponiveis, // Recebe apenas as contas válidas
  contaValue,
  onContaChange
}) => {
  const gruposContas = useMemo(() => {
    const contasParaFiltrar = Array.isArray(contasDisponiveis) ? contasDisponiveis : [];

    const dinheiro = contasParaFiltrar.filter(c => c.tipo_conta === 'Dinheiro');
    const bancarias = contasParaFiltrar.filter(c => c.tipo_conta !== 'Dinheiro');

    return { dinheiro, bancarias };
  }, [contasDisponiveis]);

  if (!isOpen) return null;

  const isConfirmDisabled = showContaInput && !contaValue;

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
        {showContaInput && (
          <div className="form-group">
            <label>Conta de Origem:</label>
            <select
              className="form-control"
              value={contaValue}
              onChange={(e) => onContaChange(e.target.value)}
              required
            >
              <option value="">Selecione a conta...</option>

              {/* Opções para Dinheiro */}
              {gruposContas.dinheiro.length > 0 && (
                <optgroup label="Dinheiro">
                  {gruposContas.dinheiro.map((conta) => (
                    <option key={conta.id} value={JSON.stringify({ id: conta.id, tipo: 'Dinheiro' })}>
                      {`${conta.nome} - Saldo: ${parseFloat(conta.saldo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`}
                    </option>
                  ))}
                </optgroup>
              )}

              {/* Opções para Contas Bancárias (Pix, Débito e Crédito) */}
              {gruposContas.bancarias.length > 0 && (
                <optgroup label="Contas Bancárias">
                  {gruposContas.bancarias.map((conta) => (
                    <React.Fragment key={conta.id}>
                      {/* Opção para Débito */}
                      {conta.tipo_conta === 'Conta Corrente' && (
                        <option value={JSON.stringify({ id: conta.id, tipo: 'Débito' })}>
                          {/* CORREÇÃO AQUI: EXIBIR SALDO TOTAL DISPONÍVEL */}
                          {`${conta.nome} (Débito) - Saldo Disponível: ${parseFloat(conta.saldo_total_disponivel).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`}
                        </option>
                      )}

                      {/* Opção para Crédito */}
                      {conta.tipo_conta === 'Cartão de Crédito' && (
                        <option value={JSON.stringify({ id: conta.id, tipo: 'Crédito' })}>
                          {`${conta.nome} (Crédito) - Limite: ${conta.limite_cartao_limpo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`}
                        </option>
                      )}
                    </React.Fragment>
                  ))}
                </optgroup>
              )}
            </select>
          </div>
        )}
        <div className="modal-buttons">
          <button className="btn btn-danger" onClick={onConfirm} disabled={isConfirmDisabled}>
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

// Componente de Filtros Avançados (sem alterações)
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

  const [sortConfig, setSortConfig] = useState({ key: null, direction: 'asc' });

  const sortedLancamentos = useMemo(() => {
    if (!sortConfig.key) {
      return lancamentos;
    }
    const sortableItems = [...lancamentos];
    sortableItems.sort((a, b) => {
      const aValue = a[sortConfig.key];
      const bValue = b[sortConfig.key];

      // Lógica para lidar com valores ausentes ou nulos
      if (aValue === null || aValue === undefined) return 1;
      if (bValue === null || bValue === undefined) return -1;

      // Lógica para ordenar por tipo (string) e outros valores
      if (typeof aValue === 'string') {
        return sortConfig.direction === 'asc'
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue);
      }

      // Lógica para ordenar valores numéricos ou de data
      if (aValue < bValue) {
        return sortConfig.direction === 'asc' ? -1 : 1;
      }
      if (aValue > bValue) {
        return sortConfig.direction === 'asc' ? 1 : -1;
      }
      return 0;
    });
    return sortableItems;
  }, [lancamentos, sortConfig]);

  const requestSort = (key) => {
    let direction = 'asc';
    if (sortConfig.key === key && sortConfig.direction === 'asc') {
      direction = 'desc';
    }
    setSortConfig({ key, direction });
  };

  const getSortIndicator = (key) => {
    if (sortConfig.key !== key) {
      return ' ↕';
    }
    return sortConfig.direction === 'asc' ? ' ↑' : ' ↓';
  };

  const [quitacaoDialog, setQuitacaoDialog] = useState({
    isOpen: false,
    item: null,
    data: '',
    contaId: '',
    contasDisponiveis: []
  });

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

  const [contasBancarias, setContasBancarias] = useState([]);

  // Função para mostrar notificação
  const showNotification = useCallback((message, type = 'success') => {
    setNotification({ message, type });
  }, []);

  // Funções de formatação e utilitários
  const formatarMoeda = useCallback((valor) => {
    return parseFloat(valor).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
  }, []);

  const formatarData = useCallback((data) => {
    if (!data) return '-';
    return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
  }, []);

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

  // Funções de Ações de Lançamentos
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
  }, [usuario, calcularDatas, paginacao.porPagina, filtros, showNotification]);

  const fetchContas = useCallback(async () => {
    if (!usuario) return;
    try {
      const response = await axios.get(`${API_BASE_URL}/bancos.php?usuario_id=${usuario.id}`);
      let contasFetched = response.data.data || response.data || [];

      // Limpeza e conversão dos valores monetários
      contasFetched = contasFetched.map(conta => {
        // Limpar limite_cartao
        let limiteCartaoLimpo = 0;
        if (conta.limite_cartao) {
          const cleanValue = String(conta.limite_cartao).replace(/[^\d,]/g, '').replace(',', '.');
          limiteCartaoLimpo = parseFloat(cleanValue) || 0;
        }

        // Limpar saldo
        let saldoLimpo = 0;
        if (conta.saldo) {
          const cleanValue = String(conta.saldo).replace(/[^\d,]/g, '').replace(',', '.');
          saldoLimpo = parseFloat(cleanValue) || 0;
        }

        // Limpar cheque_especial (NOVO)
        let limiteChequeEspecialLimpo = 0;
        if (conta.cheque_especial) {
          const cleanValue = String(conta.cheque_especial).replace(/[^\d,]/g, '').replace(',', '.');
          limiteChequeEspecialLimpo = parseFloat(cleanValue) || 0;
        }

        // Calcular saldo total disponível (Saldo + Cheque Especial)
        const saldoTotalDisponivel = saldoLimpo + limiteChequeEspecialLimpo;

        return {
          ...conta,
          limite_cartao_limpo: limiteCartaoLimpo,
          saldo_limpo: saldoLimpo,
          limite_cheque_especial_limpo: limiteChequeEspecialLimpo,
          saldo_total_disponivel: saldoTotalDisponivel
        };
      });
      setContasBancarias(contasFetched);
    } catch (error) {
      console.error("Erro ao carregar contas bancárias:", error);
      setContasBancarias([]);
    }
  }, [usuario]);

  // FUNÇÃO CORRIGIDA AQUI
  const quitarLancamento = useCallback(async (id, tipo, dataReal, contaValue) => {
    if (!contaValue) {
      showNotification('A conta de pagamento é obrigatória.', 'error');
      return;
    }

    try {
      const response = await axios.post(`${API_BASE_URL}/lancamentos.php`, {
        action: 'quitar',
        id,
        tipo,
        usuario_id: usuario.id,
        data_real: dataReal,
        conta_value: contaValue // ALTERAÇÃO: ENVIA A STRING JSON COMPLETA
      });

      if (response.data.success) {
        showNotification(response.data.message || `${tipo} quitado com sucesso!`);
        fetchLancamentos(filtros, paginacao.pagina);
        setQuitacaoDialog({ isOpen: false, item: null, data: '', contaId: '', contasDisponiveis: [] });
      } else {
        throw new Error(response.data.message || 'Erro ao quitar lançamento');
      }
    } catch (error) {
      console.error('Erro ao quitar lançamento:', error);
      showNotification('Erro ao quitar lançamento: ' + error.message, 'error');
    }
  }, [usuario, filtros, paginacao.pagina, fetchLancamentos, showNotification]);

  const desquitarLancamento = useCallback(async (id, tipo) => {
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
  }, [usuario, filtros, paginacao.pagina, fetchLancamentos, showNotification]);

  const visualizarDetalhes = useCallback(async (id, tipo) => {
    showNotification('Funcionalidade de visualização em desenvolvimento', 'info');
  }, [showNotification]);

  const editarLancamento = useCallback((id, tipo) => {
    if (tipo === 'receita') {
      navigate(`/receitas?edit=${id}`);
    } else {
      navigate(`/despesas?edit=${id}`);
    }
  }, [navigate]);

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

  const aplicarFiltros = useCallback(() => {
    fetchLancamentos(filtros, 1);
  }, [filtros, fetchLancamentos]);

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

  const exportarDados = useCallback(() => {
    showNotification('Funcionalidade de exportação em desenvolvimento', 'info');
  }, [showNotification]);

  // FUNÇÃO PARA INICIAR O DIÁLOGO DE QUITAÇÃO COM AS VALIDAÇÕES
  const handleStatusClick = useCallback((item) => {
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
      const valorLancamento = parseFloat(item.valor);

      const contasDebitoValidas = contasBancarias.filter(
        (conta) =>
          (conta.tipo_conta === 'Dinheiro' || conta.tipo_conta === 'Conta Corrente') &&
          conta.saldo_total_disponivel >= valorLancamento
      );

      const contasCreditoValidas = item.tipo === 'despesa'
        ? contasBancarias.filter(
          (conta) =>
            conta.tipo_conta === 'Cartão de Crédito' &&
            conta.limite_cartao_limpo >= valorLancamento
        )
        : [];

      const contasDisponiveis = [...contasDebitoValidas, ...contasCreditoValidas];

      if (contasDisponiveis.length === 0) {
        showNotification(
          `Você não possui nenhuma conta com saldo ou limite suficiente para pagar este lançamento de ${formatarMoeda(valorLancamento)}.`,
          'error'
        );
      } else {
        const hoje = new Date().toISOString().split('T')[0];
        setQuitacaoDialog({
          isOpen: true,
          item: item,
          data: hoje,
          contaId: '',
          contasDisponiveis: contasDisponiveis
        });
      }
    }
  }, [desquitarLancamento, contasBancarias, showNotification, formatarMoeda]);

  // EFEITOS (Hooks)
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  useEffect(() => {
    const loadInitialData = async () => {
      if (usuario) {
        await fetchContas();
        await fetchLancamentos();
      }
    };
    loadInitialData();
  }, [usuario, fetchContas, fetchLancamentos]);

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
      {notification && (
        <Notification
          message={notification.message}
          type={notification.type}
          onClose={() => setNotification(null)}
        />
      )}

      <ConfirmDialog {...confirmDialog} />

      <ConfirmDialog
        isOpen={quitacaoDialog.isOpen}
        title={`Confirmar ${quitacaoDialog.item?.tipo === 'receita' ? 'Recebimento' : 'Pagamento'}`}
        message={`Selecione a data real e a conta para o lançamento: "${quitacaoDialog.item?.descricao}"`}
        showDateInput={true}
        dateValue={quitacaoDialog.data}
        onDateChange={(data) => setQuitacaoDialog({ ...quitacaoDialog, data })}
        showContaInput={true}
        contasDisponiveis={quitacaoDialog.contasDisponiveis} // Passa a lista filtrada
        contaValue={quitacaoDialog.contaId}
        onContaChange={(contaId) => setQuitacaoDialog({ ...quitacaoDialog, contaId })}
        onConfirm={() => quitarLancamento(quitacaoDialog.item.id, quitacaoDialog.item.tipo, quitacaoDialog.data, quitacaoDialog.contaId)}
        onCancel={() => setQuitacaoDialog({ isOpen: false, item: null, data: '', contaId: '', contasDisponiveis: [] })}
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
                  <th><button className="sort-button" onClick={() => requestSort('tipo')}>
                    Tipo {getSortIndicator('tipo')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('categoria')}>
                    Descrição {getSortIndicator('categoria')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('valor')}>
                    Valor {getSortIndicator('valor')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('familiar')}>
                    Familiar {getSortIndicator('familiar')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('data_prevista')}>
                    Data Prevista {getSortIndicator('data_prevista')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('data_real')}>
                    Data Real {getSortIndicator('data_real')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('status')}>
                    Status {getSortIndicator('status')}
                  </button></th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                {!loading && sortedLancamentos.length === 0 ? (
                  <tr>
                    <td colSpan="8" className="empty-state">
                      Nenhum lançamento encontrado para os filtros aplicados.
                    </td>
                  </tr>
                ) : (
                  sortedLancamentos.map((item) => (
                    <tr key={`${item.tipo}-${item.id}`} className={getRowClass(item.status)}>
                      <td>
                        <span className={`tipo-badge ${item.tipo}`}>
                          {item.tipo === 'receita' ? '💰 Receita' : '💸 Despesa'}
                        </span>
                      </td>
                      <td className="descricao-cell">
                        {item.categoria}
                      </td>
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
                            className={`btn-status ${item.data_real ? 'btn-warning' : (item.tipo === 'despesa' ? 'btn-danger' : 'btn-success')}`}
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