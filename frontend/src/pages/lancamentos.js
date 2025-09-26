import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { FaSearch, FaFilter, FaDownload, FaEye, FaEdit, FaTrash, FaCheck, FaTimes } from 'react-icons/fa';
import './lancamentos.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import { ReceitaForm } from './receitas';
import { DespesaForm } from './despesas';
import ToggleSwitch from '../components/ToggleSwitch';
import Modal from '../components/Modal';

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
  contasDisponiveis,
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

              {gruposContas.dinheiro.length > 0 && (
                <optgroup label="Dinheiro">
                  {gruposContas.dinheiro.map((conta) => (
                    <option key={conta.id} value={JSON.stringify({ id: conta.id, tipo: 'Dinheiro' })}>
                      {`${conta.nome} - Saldo: ${parseFloat(conta.saldo).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`}
                    </option>
                  ))}
                </optgroup>
              )}

              {gruposContas.bancarias.length > 0 && (
                <optgroup label="Contas Bancárias">
                  {gruposContas.bancarias.map((conta) => (
                    <React.Fragment key={conta.id}>
                      {conta.tipo_conta === 'Conta Corrente' && (
                        <option value={JSON.stringify({ id: conta.id, tipo: 'Débito' })}>
                          {`${conta.nome} (Débito) - Saldo Disponível: ${parseFloat(conta.saldo_total_disponivel).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}`}
                        </option>
                      )}

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


// Componente de Filtros Avançados CORRIGIDO E MELHORADO
const FiltrosAvancados = ({ filtros, onFiltrosChange, onBuscar, onLimpar }) => {
  return (
    <div className="filtros-avancados">
      {/* LINHA 1: PERÍODO, TIPO, STATUS E O NOVO TOGGLE SWITCH */}
      <div className="filtros-linha">
        {/* Período */}
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

        {/* Datas Personalizadas */}
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

        {/* Tipo */}
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

        {/* Status */}
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

        {/* NOVO TOGGLE SWITCH ALINHADO */}
        {/* Usamos o componente ToggleSwitch com o label para 'Incluir Quitados' */}
        <div className="filtro-grupo filtro-toggle">
          <ToggleSwitch
            label="Incluir Quitados"
            checked={filtros.mostrarQuitados}
            onChange={(e) => onFiltrosChange({ ...filtros, mostrarQuitados: e.target.checked })}
          />
        </div>
      </div>

      {/* LINHA 2: BUSCA E AÇÕES */}
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
    porPagina: 50,
    total: 0
  });

  const [sortConfig, setSortConfig] = useState({ key: null, direction: 'asc' });

  // Estados para os modais e formulários
  const [showFormReceita, setShowFormReceita] = useState(false);
  const [showFormDespesa, setShowFormDespesa] = useState(false);
  const [selectsDataReceita, setSelectsDataReceita] = useState({ familiares: [], categorias: [], bancos: [] });
  const [selectsDataDespesa, setSelectsDataDespesa] = useState({ familiares: [], fornecedores: [], categorias: [] });
  const [editingReceita, setEditingReceita] = useState(null);
  const [editingDespesa, setEditingDespesa] = useState(null);

  const sortedLancamentos = useMemo(() => {
    if (!sortConfig.key) {
      return lancamentos;
    }
    const sortableItems = [...lancamentos];
    sortableItems.sort((a, b) => {
      const aValue = a[sortConfig.key];
      const bValue = b[sortConfig.key];

      if (aValue === null || aValue === undefined) return 1;
      if (bValue === null || bValue === undefined) return -1;

      if (typeof aValue === 'string') {
        return sortConfig.direction === 'asc'
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue);
      }

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
    busca: '',
    mostrarQuitados: false
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

  // Função para buscar lançamentos
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
        mostrarQuitados: filtrosAtuais.mostrarQuitados ? 1 : 0,
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
          pagina: paginaAtual,
          total: listResponse.data.total || 0
        }));
      } else {
        throw new Error(listResponse.data.message || 'Erro ao carregar lançamentos');
      }

      if (resumoResponse.data.success) {
        setResumoTotais(resumoResponse.data.data || {
          total_receitas: 0,
          total_despesas: 0,
          saldo: 0
        });
      }
    } catch (error) {
      console.error('Erro ao carregar lançamentos:', error);
      showNotification('Erro ao carregar lançamentos: ' + error.message, 'error');
    } finally {
      setLoading(false);
    }
  }, [usuario, calcularDatas, paginacao.porPagina, showNotification]);

  // Função para buscar contas bancárias
  const fetchContas = useCallback(async () => {
    if (!usuario) return;

    try {
      const response = await axios.get(`${API_BASE_URL}/bancos.php`, {
        params: { usuario_id: usuario.id }
      });

      const contasFetched = response.data.data.map((conta) => {
        let limiteCartaoLimpo = 0;
        if (conta.limite_cartao) {
          const cleanValue = String(conta.limite_cartao).replace(/[^\d,]/g, '').replace(',', '.');
          limiteCartaoLimpo = parseFloat(cleanValue) || 0;
        }

        let saldoLimpo = 0;
        if (conta.saldo) {
          const cleanValue = String(conta.saldo).replace(/[^\d,]/g, '').replace(',', '.');
          saldoLimpo = parseFloat(cleanValue) || 0;
        }

        let limiteChequeEspecialLimpo = 0;
        if (conta.cheque_especial) {
          const cleanValue = String(conta.cheque_especial).replace(/[^\d,]/g, '').replace(',', '.');
          limiteChequeEspecialLimpo = parseFloat(cleanValue) || 0;
        }

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

  // Função para quitar lançamento
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
        conta_value: contaValue
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

  const editarLancamento = useCallback(async (id, tipo) => {
    try {
      // Buscar os dados do lançamento para edição
      const response = await axios.get(`${API_BASE_URL}/lancamentos.php`, {
        params: {
          action: 'detalhes',
          id: id,
          tipo: tipo,
          usuario_id: usuario.id
        }
      });

      if (response.data.success) {
        const dadosLancamento = response.data.data;

        if (tipo === 'receita') {
          // Carregar dados necessários para o formulário de receita
          try {
            const [familiares, categorias, bancos] = await Promise.all([
              axios.get(`${API_BASE_URL}/familiares.php`, { params: { usuario_id: usuario.id } }),
              axios.get(`${API_BASE_URL}/categorias.php`, { params: { usuario_id: usuario.id } }),
              axios.get(`${API_BASE_URL}/bancos.php`, { params: { usuario_id: usuario.id } })
            ]);

            setSelectsDataReceita({
              familiares: familiares.data.data || [],
              categorias: categorias.data.data || [],
              bancos: bancos.data.data || []
            });
          } catch (error) {
            console.error('Erro ao carregar dados para receita:', error);
          }

          // Definir dados de edição
          setEditingReceita(dadosLancamento);
          // Abrir modal de receita com dados preenchidos
          setShowFormReceita(true);
        } else {
          // Carregar dados necessários para o formulário de despesa
          try {
            const [familiares, fornecedores, categorias] = await Promise.all([
              axios.get(`${API_BASE_URL}/familiares.php`, { params: { usuario_id: usuario.id } }),
              axios.get(`${API_BASE_URL}/fornecedores.php`, { params: { usuario_id: usuario.id } }),
              axios.get(`${API_BASE_URL}/categorias.php`, { params: { usuario_id: usuario.id } })
            ]);

            setSelectsDataDespesa({
              familiares: familiares.data.data || [],
              fornecedores: fornecedores.data.data || [],
              categorias: categorias.data.data || []
            });
          } catch (error) {
            console.error('Erro ao carregar dados para despesa:', error);
          }

          // Definir dados de edição
          setEditingDespesa(dadosLancamento);
          // Abrir modal de despesa com dados preenchidos
          setShowFormDespesa(true);
        }
      } else {
        throw new Error(response.data.message || 'Erro ao carregar dados do lançamento');
      }
    } catch (error) {
      console.error('Erro ao editar lançamento:', error);
      showNotification('Erro ao carregar dados para edição: ' + error.message, 'error');
    }
  }, [usuario, showNotification]);

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
      busca: '',
      mostrarQuitados: false
    };
    setFiltros(filtrosLimpos);
    fetchLancamentos(filtrosLimpos, 1);
  }, [fetchLancamentos]);

  const exportarDados = useCallback(() => {
    showNotification('Funcionalidade de exportação em desenvolvimento', 'info');
  }, [showNotification]);

  // Função para iniciar o diálogo de quitação
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

  // FUNÇÕES PARA CARREGAR DADOS DOS FORMULÁRIOS
  const carregarDadosReceita = useCallback(async () => {
    try {
      const [familiares, categorias, bancos] = await Promise.all([
        axios.get(`${API_BASE_URL}/familiares.php`, { params: { usuario_id: usuario.id } }),
        axios.get(`${API_BASE_URL}/categorias.php`, { params: { usuario_id: usuario.id } }),
        axios.get(`${API_BASE_URL}/bancos.php`, { params: { usuario_id: usuario.id } })
      ]);

      setSelectsDataReceita({
        familiares: familiares.data.data || [],
        categorias: categorias.data.data || [],
        bancos: bancos.data.data || []
      });
    } catch (error) {
      console.error('Erro ao carregar dados para receita:', error);
      showNotification('Erro ao carregar dados do formulário', 'error');
    }
  }, [usuario, showNotification]);

  const carregarDadosDespesa = useCallback(async () => {
    try {
      const [familiares, fornecedores, categorias] = await Promise.all([
        axios.get(`${API_BASE_URL}/familiares.php`, { params: { usuario_id: usuario.id } }),
        axios.get(`${API_BASE_URL}/fornecedores.php`, { params: { usuario_id: usuario.id } }),
        axios.get(`${API_BASE_URL}/categorias.php`, { params: { usuario_id: usuario.id } })
      ]);

      setSelectsDataDespesa({
        familiares: familiares.data.data || [],
        fornecedores: fornecedores.data.data || [],
        categorias: categorias.data.data || []
      });
    } catch (error) {
      console.error('Erro ao carregar dados para despesa:', error);
      showNotification('Erro ao carregar dados do formulário', 'error');
    }
  }, [usuario, showNotification]);

  // FUNÇÕES MELHORADAS PARA SALVAMENTO
  const handleSaveReceita = useCallback(async (form) => {
    try {
      // Validação básica
      if (!form.quem_recebeu || !form.categoria_id || !form.forma_recebimento || !form.valor || !form.data_prevista_recebimento) {
        showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
      }

      if (parseFloat(form.valor) <= 0) {
        showNotification('O valor deve ser maior que zero.', 'error');
        return;
      }

      const endpoint = `${API_BASE_URL}/receitas.php`;
      const payload = {
        ...form,
        usuario_id: usuario.id,
        action: form.id ? 'update' : 'create',
      };

      const response = await axios.post(endpoint, payload);

      if (response.data.success !== false) {
        showNotification('Receita salva com sucesso!');
        setShowFormReceita(false);
        setEditingReceita(null);
        fetchLancamentos();
      } else {
        throw new Error(response.data.message || 'Erro ao salvar receita');
      }
    } catch (error) {
      console.error("Erro ao salvar a receita:", error);
      const errorMessage = error.response?.data?.message || error.message || 'Erro ao salvar a receita.';
      showNotification(errorMessage, 'error');
    }
  }, [usuario, fetchLancamentos, showNotification]);

  const handleSaveDespesa = useCallback(async (form) => {
    try {
      // Validação básica
      if (!form.quem_comprou || !form.onde_comprou || !form.categoria_id || !form.forma_pagamento || !form.valor || !form.data_compra) {
        showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
      }

      if (parseFloat(form.valor) <= 0) {
        showNotification('O valor deve ser maior que zero.', 'error');
        return;
      }

      const endpoint = `${API_BASE_URL}/despesas.php`;
      const payload = {
        ...form,
        usuario_id: usuario.id,
        action: form.id ? 'update' : 'create',
      };

      const response = await axios.post(endpoint, payload);

      if (response.data.success !== false) {
        showNotification('Despesa salva com sucesso!');
        setShowFormDespesa(false);
        setEditingDespesa(null);
        fetchLancamentos();
      } else {
        throw new Error(response.data.message || 'Erro ao salvar despesa');
      }
    } catch (error) {
      console.error("Erro ao salvar a despesa:", error);
      const errorMessage = error.response?.data?.message || error.message || 'Erro ao salvar a despesa.';
      showNotification(errorMessage, 'error');
    }
  }, [usuario, fetchLancamentos, showNotification]);

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

  // USEEFFECT MELHORADO PARA CARREGAR DADOS DOS SELECTS
  useEffect(() => {
    const fetchSelectsData = async () => {
      if (!usuario) return;

      try {
        // Carregar dados para receitas
        if (showFormReceita) {
          setSelectsDataReceita({ familiares: [], categorias: [], bancos: [] }); // Reset

          const [respFamiliares, respCategorias, respBancos] = await Promise.all([
            axios.get(`${API_BASE_URL}/familiares.php?usuario_id=${usuario.id}`),
            axios.get(`${API_BASE_URL}/categorias.php?tipo=RECEITA`),
            axios.get(`${API_BASE_URL}/bancos.php?usuario_id=${usuario.id}`)
          ]);

          setSelectsDataReceita({
            familiares: Array.isArray(respFamiliares.data) ? respFamiliares.data : [],
            categorias: Array.isArray(respCategorias.data) ? respCategorias.data : [],
            bancos: Array.isArray(respBancos.data?.data) ? respBancos.data.data : []
          });
        }

        // Carregar dados para despesas
        if (showFormDespesa) {
          setSelectsDataDespesa({ familiares: [], fornecedores: [], categorias: [] }); // Reset

          const [respFamiliares, respFornecedores, respCategorias] = await Promise.all([
            axios.get(`${API_BASE_URL}/familiares.php?usuario_id=${usuario.id}`),
            axios.get(`${API_BASE_URL}/fornecedores.php?usuario_id=${usuario.id}`),
            axios.get(`${API_BASE_URL}/categorias.php?tipo=DESPESA`)
          ]);

          setSelectsDataDespesa({
            familiares: Array.isArray(respFamiliares.data) ? respFamiliares.data : [],
            fornecedores: Array.isArray(respFornecedores.data) ? respFornecedores.data : [],
            categorias: Array.isArray(respCategorias.data) ? respCategorias.data : []
          });
        }
      } catch (error) {
        console.error("Erro ao carregar dados para selects:", error);
        showNotification('Erro ao carregar dados do formulário. Tente novamente.', 'error');
      }
    };

    fetchSelectsData();
  }, [showFormReceita, showFormDespesa, usuario, showNotification]);

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
        contasDisponiveis={quitacaoDialog.contasDisponiveis}
        contaValue={quitacaoDialog.contaId}
        onContaChange={(contaId) => setQuitacaoDialog({ ...quitacaoDialog, contaId })}
        onConfirm={() => quitarLancamento(quitacaoDialog.item.id, quitacaoDialog.item.tipo, quitacaoDialog.data, quitacaoDialog.contaId)}
        onCancel={() => setQuitacaoDialog({ isOpen: false, item: null, data: '', contaId: '', contasDisponiveis: [] })}
      />

      {/* MODAIS MELHORADOS COM CONFIRMAÇÃO DE FECHAMENTO */}
      <Modal
        title="Cadastrar Receita"
        isOpen={showFormReceita}
        onClose={() => {
          if (window.confirm('Deseja realmente fechar? Os dados não salvos serão perdidos.')) {
            setShowFormReceita(false);
            setEditingReceita(null);
          }
        }}
      >
        {selectsDataReceita.familiares.length > 0 ? (
          <ReceitaForm
            onSave={handleSaveReceita}
            onCancel={() => {
              if (window.confirm('Deseja realmente cancelar? Os dados não salvos serão perdidos.')) {
                setShowFormReceita(false);
                setEditingReceita(null);
              }
            }}
            editingReceita={editingReceita}
            initialFormState={editingReceita || {
              quem_recebeu: '', categoria_id: '', forma_recebimento: '',
              valor: '', data_prevista_recebimento: new Date().toISOString().split('T')[0],
              data_recebimento: '', recorrente: false, parcelas: 1, frequencia: 'mensal',
              observacoes: '', usuario_id: usuario?.id
            }}
            selectsData={selectsDataReceita}
          />
        ) : (
          <div className="loading-form">
            <Spinner />
            <p>Carregando formulário...</p>
          </div>
        )}
      </Modal>

      <Modal
        title="Cadastrar Despesa"
        isOpen={showFormDespesa}
        onClose={() => {
          if (window.confirm('Deseja realmente fechar? Os dados não salvos serão perdidos.')) {
            setShowFormDespesa(false);
            setEditingDespesa(null);
          }
        }}
      >
        {selectsDataDespesa.familiares.length > 0 ? (
          <DespesaForm
            onSave={handleSaveDespesa}
            onCancel={() => {
              if (window.confirm('Deseja realmente cancelar? Os dados não salvos serão perdidos.')) {
                setShowFormDespesa(false);
                setEditingDespesa(null);
              }
            }}
            editingDespesa={editingDespesa}
            initialFormState={editingDespesa || {
              quem_comprou: '', onde_comprou: '', categoria_id: '', forma_pagamento: '',
              valor: '', data_compra: new Date().toISOString().split('T')[0],
              data_pagamento: '', recorrente: false, parcelas: 1, frequencia: 'mensal',
              observacoes: '', usuario_id: usuario?.id
            }}
            selectsData={selectsDataDespesa}
          />
        ) : (
          <div className="loading-form">
            <Spinner />
            <p>Carregando formulário...</p>
          </div>
        )}
      </Modal>

      <div className="content-card">
        <div className="page-header">
          <h1 className="page-title">📌 Lançamentos</h1>
          <div className="page-actions">
            {/* BOTÕES MELHORADOS COM PREVENÇÃO DE ABERTURA MÚLTIPLA */}
            <button
              className="btn btn-success"
              onClick={() => {
                setEditingReceita(null);
                setShowFormReceita(true);
                setShowFormDespesa(false);
              }}
              disabled={showFormReceita || showFormDespesa}
            >
              + Nova Receita
            </button>
            <button
              className="btn btn-danger"
              onClick={() => {
                setEditingDespesa(null);
                setShowFormDespesa(true);
                setShowFormReceita(false);
              }}
              disabled={showFormReceita || showFormDespesa}
            >
              + Nova Despesa
            </button>
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
                  <th><button className="sort-button" onClick={() => requestSort('tipo')} style={{ whiteSpace: 'nowrap' }}>
                    Tipo {getSortIndicator('tipo')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('descricao')} style={{ whiteSpace: 'nowrap' }}>
                    Observações {getSortIndicator('descricao')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('fornecedor')} style={{ whiteSpace: 'nowrap' }}>
                    Fornecedor {getSortIndicator('fornecedor')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('valor')} style={{ whiteSpace: 'nowrap' }}>
                    Valor {getSortIndicator('valor')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('familiar')} style={{ whiteSpace: 'nowrap' }}>
                    Familiar {getSortIndicator('familiar')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('data_prevista')} style={{ whiteSpace: 'nowrap' }}>
                    Data Prevista {getSortIndicator('data_prevista')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('data_real')} style={{ whiteSpace: 'nowrap' }}>
                    Data Pagto {getSortIndicator('data_real')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('tipo_pagamento')} style={{ whiteSpace: 'nowrap' }}>
                    Forma Pagto {getSortIndicator('tipo_pagamento')}
                  </button></th>
                  <th><button className="sort-button" onClick={() => requestSort('status')} style={{ whiteSpace: 'nowrap' }}>
                    Status {getSortIndicator('status')}
                  </button></th>
                  <th style={{ whiteSpace: 'nowrap' }}>Ações</th>
                </tr>
              </thead>
              <tbody>
                {!loading && sortedLancamentos.length === 0 ? (
                  <tr>
                    <td colSpan="10" className="empty-state">
                      Nenhum lançamento encontrado para os filtros aplicados.
                    </td>
                  </tr>
                ) : (
                  sortedLancamentos.map((item) => (
                    <tr key={`${item.tipo}-${item.id}`} className={getRowClass(item.status)}>
                      <td>
                        <span className={`tipo-badge ${item.tipo}`}>
                          {item.tipo === 'receita' ? '💰 Receita' : '💳 Despesa'}
                        </span>
                      </td>
                      <td>{item.descricao}</td>
                      <td>{item.fornecedor}</td>
                      <td className={item.tipo === 'receita' ? 'valor-positivo' : 'valor-negativo'}>
                        {formatarMoeda(item.valor)}
                      </td>
                      <td>{item.familiar}</td>
                      <td>{formatarData(item.data_prevista)}</td>
                      <td>{formatarData(item.data_real)}</td>
                      <td>{item.tipo_pagamento || '-'}</td>
                      <td>
                        {/* CORREÇÃO: Mostrar apenas o status em texto */}
                        <span className={`status-text ${item.status}`}>
                          {item.data_real ? (
                            item.tipo === 'receita' ? 'RECEBIDO' : 'PAGO'
                          ) : (
                            item.status === 'pendente' ? 'PENDENTE' :
                              item.status === 'atrasado' ? 'ATRASADO' :
                                item.status === 'hoje' ? 'VENCE HOJE' : 'PENDENTE'
                          )}
                        </span>
                      </td>
                      <td>
                        <div className="action-buttons" style={{ display: 'flex', gap: '5px', alignItems: 'center', justifyContent: 'center', flexWrap: 'nowrap' }}>
                          {/* CORREÇÃO: Mover o botão de Pagar/Receber para as ações */}
                          <button
                            className={`btn-action ${item.data_real ? 'btn-secondary' : (item.tipo === 'receita' ? 'btn-success' : 'btn-danger')}`}
                            onClick={() => handleStatusClick(item)}
                            title={item.data_real ? 'Clique para desquitar' : 'Clique para quitar'}
                            style={{ minWidth: '60px', fontSize: '12px' }}
                          >
                            {item.data_real ? (
                              '✓'
                            ) : (
                              item.tipo === 'receita' ? 'Receber' : 'Pagar'
                            )}
                          </button>

                          {/* <button
                            className="btn-action btn-info"
                            onClick={() => visualizarDetalhes(item.id, item.tipo)}
                            title="Visualizar"
                            style={{ minWidth: '35px', padding: '5px' }}
                          >
                            <FaEye />
                          </button> */}

                          <button
                            className="btn-action btn-warning"
                            onClick={() => editarLancamento(item.id, item.tipo)}
                            title="Editar"
                            style={{ minWidth: '35px', padding: '5px' }}
                          >
                            <FaEdit />
                          </button>

                          <button
                            className="btn-action btn-danger"
                            onClick={() => confirmarExclusao(item.id, item.tipo, item.descricao)}
                            title="Excluir"
                            style={{ minWidth: '35px', padding: '5px' }}
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

