import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './despesas.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import ModalConfirmacao from './ModalConfirmacao';
import './ModalConfirmacao.css';
import ToggleSwitch from '../components/ToggleSwitch';

// --- COMPONENTE DO FORMULÁRIO DE DESPESA ---
export const DespesaForm = ({ onSave, onCancel, editingDespesa, initialFormState, selectsData }) => {
    const [form, setForm] = useState(initialFormState);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        setForm(editingDespesa ? { ...editingDespesa } : initialFormState);
    }, [editingDespesa, initialFormState]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        const val = type === 'checkbox' ? checked : value;
        setForm(prev => ({ ...prev, [name]: val }));
    };

    const handleToggleChange = (e) => {
        setForm(prev => ({ ...prev, recorrente: e.target.checked }));
    };

    const validateForm = () => {
        const requiredFields = ['quem_comprou', 'onde_comprou', 'categoria_id', 'forma_pagamento', 'valor', 'data_compra'];

        for (let field of requiredFields) {
            if (!form[field] || form[field] === '') {
                return false;
            }
        }

        if (parseFloat(form.valor) <= 0) {
            return false;
        }

        return true;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validateForm()) {
            alert('Por favor, preencha todos os campos obrigatórios corretamente.');
            return;
        }

        setIsSubmitting(true);
        try {
            await onSave(form);
        } catch (error) {
            console.error('Erro ao salvar despesa:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    // Verificar se os dados dos selects estão carregados
    const isDataLoaded = selectsData &&
        Array.isArray(selectsData.familiares) &&
        Array.isArray(selectsData.fornecedores) &&
        Array.isArray(selectsData.categorias);

    if (!isDataLoaded) {
        return (
            <div className="form-loading">
                <Spinner />
                <p>Carregando dados do formulário...</p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingDespesa ? 'Editar Despesa' : 'Cadastrar Despesa'}</h2>
            <div className="form-grid">
                <div className="form-group">
                    <label htmlFor="quem_comprou">Quem Comprou *</label>
                    <select
                        id="quem_comprou"
                        name="quem_comprou"
                        value={form.quem_comprou}
                        onChange={handleChange}
                        className="form-control"
                        required
                        disabled={isSubmitting}
                    >
                        <option value="">Selecione...</option>
                        {selectsData.familiares.map(f => (
                            <option key={f.id} value={f.id}>{f.nome}</option>
                        ))}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="onde_comprou">Fornecedor *</label>
                    <select
                        id="onde_comprou"
                        name="onde_comprou"
                        value={form.onde_comprou}
                        onChange={handleChange}
                        className="form-control"
                        required
                        disabled={isSubmitting}
                    >
                        <option value="">Selecione...</option>
                        {selectsData.fornecedores.map(f => (
                            <option key={f.id} value={f.id}>{f.nome}</option>
                        ))}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="categoria_id">Categoria *</label>
                    <select
                        id="categoria_id"
                        name="categoria_id"
                        value={form.categoria_id}
                        onChange={handleChange}
                        className="form-control"
                        required
                        disabled={isSubmitting}
                    >
                        <option value="">Selecione...</option>
                        {selectsData.categorias.map(c => (
                            <option key={c.id} value={c.id}>{c.nome}</option>
                        ))}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="forma_pagamento">Forma de Pagamento *</label>
                    <select
                        id="forma_pagamento"
                        name="forma_pagamento"
                        value={form.forma_pagamento}
                        onChange={handleChange}
                        className="form-control"
                        required
                        disabled={isSubmitting}
                    >
                        <option value="">Selecione...</option>
                        <option value="DINHEIRO">Dinheiro</option>
                        <option value="PIX">PIX</option>
                        <option value="CARTAO_CREDITO">Cartão de Crédito</option>
                        <option value="CARTAO_DEBITO">Cartão de Débito</option>
                        <option value="BOLETO">Boleto</option>
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="valor">Valor (R$) *</label>
                    <input
                        id="valor"
                        name="valor"
                        type="number"
                        step="0.01"
                        min="0.01"
                        value={form.valor}
                        onChange={handleChange}
                        className="form-control"
                        placeholder="0.00"
                        required
                        disabled={isSubmitting}
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="data_compra">Data da Compra *</label>
                    <input
                        id="data_compra"
                        name="data_compra"
                        type="date"
                        value={form.data_compra}
                        onChange={handleChange}
                        className="form-control"
                        required
                        disabled={isSubmitting}
                    />
                </div>
                <div className="form-group">
                    <label htmlFor="data_pagamento">Data do Pagamento</label>
                    <input
                        id="data_pagamento"
                        name="data_pagamento"
                        type="date"
                        value={form.data_pagamento || ''}
                        onChange={handleChange}
                        className="form-control"
                        disabled={isSubmitting}
                    />
                    <small className="form-text">Deixe em branco se ainda não foi pago</small>
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="observacoes">Observações</label>
                    <textarea
                        id="observacoes"
                        name="observacoes"
                        value={form.observacoes}
                        onChange={handleChange}
                        className="form-control"
                        rows="3"
                        disabled={isSubmitting}
                    />
                </div>
                <div className="form-group form-group-full-width">
                    <ToggleSwitch
                        label="É uma conta recorrente?"
                        checked={form.recorrente}
                        onChange={handleToggleChange}
                        disabled={isSubmitting}
                    />
                </div>
                {form.recorrente && (
                    <div className="form-grid form-group-full-width">
                        <div className="form-group">
                            <label htmlFor="frequencia">Frequência</label>
                            <select
                                id="frequencia"
                                name="frequencia"
                                value={form.frequencia}
                                onChange={handleChange}
                                className="form-control"
                                disabled={isSubmitting}
                            >
                                <option value="diaria">Diária</option>
                                <option value="semanal">Semanal</option>
                                <option value="quinzenal">Quinzenal</option>
                                <option value="mensal">Mensal</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div className="form-group">
                            <label htmlFor="parcelas">Repetir por (vezes)</label>
                            <input
                                id="parcelas"
                                name="parcelas"
                                type="number"
                                min="0"
                                value={form.parcelas}
                                onChange={handleChange}
                                className="form-control"
                                title="Use 0 para recorrência 'infinita'"
                                disabled={isSubmitting}
                            />
                        </div>
                    </div>
                )}
            </div>
            <div className="form-buttons">
                <button
                    type="button"
                    className="btn btn-cancel"
                    onClick={onCancel}
                    disabled={isSubmitting}
                >
                    {editingDespesa ? 'Cancelar' : 'Limpar'}
                </button>
                <button
                    type="submit"
                    className="btn btn-save"
                    disabled={isSubmitting}
                >
                    {isSubmitting ? 'Salvando...' : (editingDespesa ? 'Salvar Alterações' : 'Adicionar Despesa')}
                </button>
            </div>
        </form>
    );
};

// --- COMPONENTE DE CABEÇALHO DE TABELA ORDENÁVEL ---
const SortableHeader = ({ children, name, sortConfig, onSort }) => {
    const isSorted = sortConfig.key === name;
    const direction = isSorted ? sortConfig.direction : 'none';

    const handleClick = () => {
        onSort(name);
    };

    return (
        <th onClick={handleClick} className="sortable-header">
            {children}
            {isSorted && (direction === 'asc' ? ' ▲' : ' ▼')}
        </th>
    );
};

// --- COMPONENTE PRINCIPAL DA PÁGINA DE DESPESAS ---
export default function Despesas() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [despesas, setDespesas] = useState([]);
    const [editingDespesa, setEditingDespesa] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });

    const [selectsData, setSelectsData] = useState({ familiares: [], fornecedores: [], categorias: [] });

    const [filtroData, setFiltroData] = useState({ inicio: '', fim: '' });
    const [limit, setLimit] = useState(10);
    const [sortConfig, setSortConfig] = useState({ key: 'data_compra', direction: 'desc' });

    const [modalState, setModalState] = useState({ isOpen: false, title: '', message: '', onConfirm: () => { }, onCancel: () => { }, confirmText: 'Confirmar', cancelText: 'Cancelar' });
    const [modalEditState, setModalEditState] = useState({ isOpen: false, title: '', message: '', onConfirm: () => { }, onCancel: () => { }, confirmText: 'Confirmar', cancelText: 'Cancelar' });

    const DESPESAS_API_URL = `${API_BASE_URL}/despesas.php`;

    const initialFormState = useMemo(() => ({
        quem_comprou: '', onde_comprou: '', categoria_id: '', forma_pagamento: '',
        valor: '', data_compra: new Date().toISOString().split('T')[0],
        data_pagamento: '', recorrente: false, parcelas: 1, frequencia: 'mensal', observacoes: ''
    }), []);

    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user) navigate('/');
        else setUsuario(user);
    }, [navigate]);

    const fetchDespesas = useCallback(async () => {
        if (!usuario) return;
        setIsLoading(true);
        try {
            const params = {
                usuario_id: usuario.id,
                ...filtroData,
                limit: limit,
                sortBy: sortConfig.key,
                sortOrder: sortConfig.direction
            };
            const response = await axios.get(DESPESAS_API_URL, { params });
            setDespesas(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar despesas.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, filtroData, limit, sortConfig, DESPESAS_API_URL]);

    const fetchSelectsData = useCallback(async () => {
        if (!usuario) return;
        try {
            const [respFamiliares, respFornecedores, respCategorias] = await Promise.all([
                axios.get(`${API_BASE_URL}/familiares.php?usuario_id=${usuario.id}`),
                axios.get(`${API_BASE_URL}/fornecedores.php?usuario_id=${usuario.id}`),
                axios.get(`${API_BASE_URL}/categorias.php?tipo=DESPESA`)
            ]);
            setSelectsData({
                familiares: Array.isArray(respFamiliares.data) ? respFamiliares.data : [],
                fornecedores: Array.isArray(respFornecedores.data) ? respFornecedores.data : [],
                categorias: Array.isArray(respCategorias.data) ? respCategorias.data : []
            });
        } catch (error) {
            console.error('Erro ao carregar dados de suporte:', error);
            showNotification('Erro ao carregar dados de suporte.', 'error');
        }
    }, [usuario]);

    useEffect(() => {
        if (usuario) {
            fetchDespesas();
        }
    }, [usuario, fetchDespesas]);

    useEffect(() => {
        if (usuario) {
            fetchSelectsData();
        }
    }, [usuario, fetchSelectsData]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleSave = async (form) => {
        const payload = {
            usuario_id: usuario.id,
            id: editingDespesa ? editingDespesa.id : undefined,
            escopo: editingDespesa?.escopo,
            grupo_recorrencia_id: editingDespesa?.grupo_recorrencia_id,
            ...form
        };

        if (!form.recorrente) {
            delete payload.parcelas;
            delete payload.frequencia;
        }

        try {
            await axios.post(DESPESAS_API_URL, payload);
            showNotification(`Despesa salva com sucesso!`, 'success');
            setEditingDespesa(null);
            fetchDespesas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar a despesa.';
            showNotification(errorMsg, 'error');
            throw error; // Re-throw para que o formulário possa lidar com o erro
        }
    };

    const handleDelete = (despesa) => {
        if (despesa.grupo_recorrencia_id) {
            setModalState({
                isOpen: true,
                title: 'Excluir Despesa Recorrente',
                message: 'Esta é uma despesa recorrente. Como você deseja excluí-la?',
                onConfirm: () => {
                    executeDelete(despesa, 'esta_e_futuras');
                    setModalState({ isOpen: false });
                },
                confirmText: 'Esta e as Futuras',
                onCancel: () => {
                    executeDelete(despesa, 'apenas_esta');
                    setModalState({ isOpen: false });
                },
                cancelText: 'Apenas Esta Parcela',
                onClose: () => setModalState({ isOpen: false })
            });
        } else {
            setModalState({
                isOpen: true,
                title: 'Confirmar Exclusão',
                message: `Tem certeza que deseja excluir a despesa de ${despesa.valor}?`,
                onConfirm: () => {
                    executeDelete(despesa, 'unica');
                    setModalState({ isOpen: false });
                },
                onCancel: () => setModalState({ isOpen: false })
            });
        }
    };

    const executeDelete = async (despesa, escopo) => {
        try {
            await axios.delete(`${DESPESAS_API_URL}?id=${despesa.id}&usuario_id=${usuario.id}&escopo=${escopo}&grupo_recorrencia_id=${despesa.grupo_recorrencia_id || ''}`);
            showNotification('Despesa excluída com sucesso!', 'success');
            fetchDespesas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao excluir a despesa.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleConfirmarPagamento = async (id) => {
        try {
            await axios.post(`${DESPESAS_API_URL}?action=confirmar_pagamento`, { id, usuario_id: usuario.id });
            showNotification('Pagamento confirmado com sucesso!', 'success');
            fetchDespesas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao confirmar pagamento.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleEdit = (despesa) => {
        setEditingDespesa(despesa);
    };

    const handleCancel = () => {
        setEditingDespesa(null);
    };

    const handleFiltroChange = (e) => {
        setFiltroData({ ...filtroData, [e.target.name]: e.target.value });
    };

    const handleLimitChange = (e) => {
        setLimit(Number(e.target.value));
    };

    const handleFilterClick = () => {
        fetchDespesas();
    };

    const handleSort = (key) => {
        let direction = 'asc';
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            direction = 'desc';
        }
        setSortConfig({ key, direction });
    };

    const getStatusDespesa = (dataCompra, dataPagamento) => {
        if (dataPagamento) return 'pago';

        const hoje = new Date().toISOString().split('T')[0];
        if (dataCompra < hoje) return 'atrasado';
        if (dataCompra === hoje) return 'hoje';
        return 'pendente';
    };

    const formatarData = (data) => {
        if (!data) return '-';
        return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
    };

    return (
        <div className="page-container">
            {notification.message && <div className={`notification ${notification.type}`}>{notification.message}</div>}

            <ModalConfirmacao
                isOpen={modalState.isOpen}
                title={modalState.title}
                message={modalState.message}
                onConfirm={modalState.onConfirm}
                onCancel={modalState.onCancel}
                confirmText={modalState.confirmText}
                cancelText={modalState.cancelText}
                onClose={modalState.onClose}
            />

            <ModalConfirmacao
                isOpen={modalEditState.isOpen}
                title={modalEditState.title}
                message={modalEditState.message}
                onConfirm={modalEditState.onConfirm}
                onCancel={modalEditState.onCancel}
                confirmText={modalEditState.confirmText}
                cancelText={modalEditState.cancelText}
                onClose={modalEditState.onClose}
            />

            <div className="content-card">
                <div className="page-header">
                    <h1 className="page-title">💸 Despesas</h1>
                </div>

                <DespesaForm
                    onSave={handleSave}
                    onCancel={handleCancel}
                    editingDespesa={editingDespesa}
                    initialFormState={initialFormState}
                    selectsData={selectsData}
                />
            </div>

            <div className="content-card">
                <div className="table-title">Últimos Lançamentos</div>
                <div className="table-filters">
                    <div className="filter-group">
                        <label>Data Início:</label>
                        <input
                            type="date"
                            name="inicio"
                            value={filtroData.inicio}
                            onChange={handleFiltroChange}
                            className="form-control"
                        />
                    </div>
                    <div className="filter-group">
                        <label>Data Fim:</label>
                        <input
                            type="date"
                            name="fim"
                            value={filtroData.fim}
                            onChange={handleFiltroChange}
                            className="form-control"
                        />
                    </div>
                    <div className="filter-group">
                        <label>Limite:</label>
                        <select value={limit} onChange={handleLimitChange} className="form-control">
                            <option value={10}>10</option>
                            <option value={25}>25</option>
                            <option value={50}>50</option>
                            <option value={100}>100</option>
                        </select>
                    </div>
                    <div className="filter-actions">
                        <button className="btn btn-primary" onClick={handleFilterClick}>
                            Filtrar
                        </button>
                    </div>
                </div>

                <div className="table-container">
                    {isLoading ? (
                        <div className="loading-container">
                            <Spinner />
                        </div>
                    ) : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <SortableHeader name="quem_comprou_nome" sortConfig={sortConfig} onSort={handleSort}>
                                        Quem Comprou
                                    </SortableHeader>
                                    <SortableHeader name="fornecedor_nome" sortConfig={sortConfig} onSort={handleSort}>
                                        Fornecedor
                                    </SortableHeader>
                                    <SortableHeader name="categoria_nome" sortConfig={sortConfig} onSort={handleSort}>
                                        Categoria
                                    </SortableHeader>
                                    <SortableHeader name="valor" sortConfig={sortConfig} onSort={handleSort}>
                                        Valor
                                    </SortableHeader>
                                    <SortableHeader name="data_compra" sortConfig={sortConfig} onSort={handleSort}>
                                        Data Prevista
                                    </SortableHeader>
                                    <SortableHeader name="data_pagamento" sortConfig={sortConfig} onSort={handleSort}>
                                        Data Pagamento
                                    </SortableHeader>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {despesas.length === 0 ? (
                                    <tr>
                                        <td colSpan="8" className="empty-state">
                                            Nenhuma despesa encontrada.
                                        </td>
                                    </tr>
                                ) : (
                                    despesas.map((despesa) => {
                                        const status = getStatusDespesa(despesa.data_compra, despesa.data_pagamento);
                                        return (
                                            <tr key={despesa.id} className={`status-${status}`}>
                                                <td>{despesa.quem_comprou_nome}</td>
                                                <td>{despesa.fornecedor_nome}</td>
                                                <td>{despesa.categoria_nome}</td>
                                                <td>R$ {parseFloat(despesa.valor).toFixed(2)}</td>
                                                <td>{formatarData(despesa.data_compra)}</td>
                                                <td>{formatarData(despesa.data_pagamento)}</td>
                                                <td>
                                                    <span className={`status-badge status-${status}`}>
                                                        {status === 'pago' && 'Pago'}
                                                        {status === 'pendente' && 'Pendente'}
                                                        {status === 'atrasado' && 'Atrasado'}
                                                        {status === 'hoje' && 'Vence Hoje'}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div className="action-buttons">
                                                        {despesa.recorrente && (
                                                            <span className="recorrente-icon" title="Despesa Recorrente">🔄</span>
                                                        )}
                                                        <button
                                                            className="btn-icon btn-success"
                                                            onClick={() => {
                                                                if (status === 'pendente' || status === 'atrasado' || status === 'hoje') {
                                                                    setModalEditState({
                                                                        isOpen: true,
                                                                        title: 'Confirmar Pagamento',
                                                                        message: `Deseja confirmar o pagamento desta despesa?`,
                                                                        onConfirm: () => {
                                                                            handleConfirmarPagamento(despesa.id);
                                                                            setModalEditState({ isOpen: false });
                                                                        },
                                                                        onCancel: () => setModalEditState({ isOpen: false })
                                                                    });
                                                                }
                                                            }}
                                                            title="Confirmar Pagamento"
                                                            disabled={status === 'pago'}
                                                        >
                                                            ✅
                                                        </button>
                                                        <button
                                                            className="btn-icon btn-warning"
                                                            onClick={() => handleEdit(despesa)}
                                                            title="Editar"
                                                        >
                                                            ✏️
                                                        </button>
                                                        <button
                                                            className="btn-icon btn-delete"
                                                            onClick={() => handleDelete(despesa)}
                                                            title="Excluir"
                                                        >
                                                            🗑️
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}

