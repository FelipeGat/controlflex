import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './receitas.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import ModalConfirmacao from './ModalConfirmacao';
import './ModalConfirmacao.css';
import ToggleSwitch from '../components/ToggleSwitch';

// --- COMPONENTE DO FORMULÁRIO DE RECEITA ---
const ReceitaForm = ({ onSave, onCancel, editingReceita, initialFormState, selectsData }) => {
    const [form, setForm] = useState(initialFormState);

    useEffect(() => {
        setForm(editingReceita ? { ...editingReceita } : initialFormState);
    }, [editingReceita, initialFormState]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        const val = type === 'checkbox' ? checked : value;
        setForm(prev => ({ ...prev, [name]: val }));
    };

    const handleToggleChange = (e) => {
        setForm(prev => ({ ...prev, recorrente: e.target.checked }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form);
    };

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingReceita ? 'Editar Receita' : 'Cadastrar Receita'}</h2>
            <div className="form-grid">
                <div className="form-group">
                    <label htmlFor="quem_recebeu">Quem Recebeu *</label>
                    <select id="quem_recebeu" name="quem_recebeu" value={form.quem_recebeu} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {selectsData.familiares.map(f => <option key={f.id} value={f.id}>{f.nome}</option>)}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="categoria_id">Categoria *</label>
                    <select id="categoria_id" name="categoria_id" value={form.categoria_id} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {selectsData.categorias.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="forma_recebimento">Forma de Recebimento *</label>
                    <select id="forma_recebimento" name="forma_recebimento" value={form.forma_recebimento} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {Array.isArray(selectsData.bancos) && selectsData.bancos.map(b => <option key={b.id} value={b.id}>{b.nome}</option>)}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="valor">Valor (R$) *</label>
                    <input id="valor" name="valor" type="number" step="0.01" value={form.valor} onChange={handleChange} className="form-control" placeholder="0.00" required />
                </div>
                <div className="form-group">
                    <label htmlFor="data_prevista_recebimento">Data Prevista do Recebimento *</label>
                    <input id="data_prevista_recebimento" name="data_prevista_recebimento" type="date" value={form.data_prevista_recebimento} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group">
                    <label htmlFor="data_recebimento">Data Real do Recebimento</label>
                    <input id="data_recebimento" name="data_recebimento" type="date" value={form.data_recebimento || ''} onChange={handleChange} className="form-control" />
                    <small className="form-text">Deixe em branco se ainda não foi recebido</small>
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" rows="3" />
                </div>
                <div className="form-group form-group-full-width">
                    <ToggleSwitch
                        label="É uma receita recorrente?"
                        checked={form.recorrente}
                        onChange={handleToggleChange}
                    />
                </div>
                {form.recorrente && (
                    <div className="form-grid form-group-full-width">
                        <div className="form-group">
                            <label htmlFor="frequencia">Frequência</label>
                            <select id="frequencia" name="frequencia" value={form.frequencia} onChange={handleChange} className="form-control">
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
                            />
                        </div>
                    </div>
                )}
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>{editingReceita ? 'Cancelar' : 'Limpar'}</button>
                <button type="submit" className="btn btn-save">{editingReceita ? 'Salvar Alterações' : 'Adicionar Receita'}</button>
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

// --- COMPONENTE PRINCIPAL DA PÁGINA DE RECEITAS ---
export default function Receitas() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [receitas, setReceitas] = useState([]);
    const [editingReceita, setEditingReceita] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });

    const [selectsData, setSelectsData] = useState({ familiares: [], categorias: [], bancos: [] });

    const [filtroData, setFiltroData] = useState({ inicio: '', fim: '' });
    const [limit, setLimit] = useState(10);
    const [sortConfig, setSortConfig] = useState({ key: 'data_prevista_recebimento', direction: 'desc' });

    const [modalState, setModalState] = useState({ isOpen: false, title: '', message: '', onConfirm: () => { }, onCancel: () => { }, confirmText: 'Confirmar', cancelText: 'Cancelar' });
    const [modalEditState, setModalEditState] = useState({ isOpen: false, title: '', message: '', onConfirm: () => { }, onCancel: () => { }, confirmText: 'Confirmar', cancelText: 'Cancelar' });

    const RECEITAS_API_URL = `${API_BASE_URL}/receitas.php`;

    const initialFormState = useMemo(() => ({
        quem_recebeu: '', categoria_id: '', forma_recebimento: '',
        valor: '', data_prevista_recebimento: new Date().toISOString().split('T')[0],
        data_recebimento: '', recorrente: false, parcelas: 1, frequencia: 'mensal', observacoes: ''
    }), []);

    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user) navigate('/');
        else setUsuario(user);
    }, [navigate]);

    const fetchReceitas = useCallback(async () => {
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
            const response = await axios.get(RECEITAS_API_URL, { params });
            setReceitas(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar receitas.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, filtroData, limit, sortConfig, RECEITAS_API_URL]);

    const fetchSelectsData = useCallback(async () => {
        if (!usuario) return;
        try {
            const [respFamiliares, respCategorias, respBancos] = await Promise.all([
                axios.get(`${API_BASE_URL}/familiares.php?usuario_id=${usuario.id}`),
                axios.get(`${API_BASE_URL}/categorias.php?tipo=RECEITA`),
                axios.get(`${API_BASE_URL}/bancos.php?usuario_id=${usuario.id}`)
            ]);
            setSelectsData({
                familiares: respFamiliares.data || [],
                categorias: respCategorias.data || [],
                bancos: respBancos.data.data || [] // CORREÇÃO AQUI
            });
        } catch (error) {
            showNotification('Erro ao carregar dados de suporte.', 'error');
        }
    }, [usuario]);

    useEffect(() => {
        if (usuario) {
            fetchReceitas();
        }
    }, [usuario, fetchReceitas]);

    useEffect(() => {
        if (usuario) {
            fetchSelectsData();
        }
    }, [usuario]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleSave = async (form) => {
        const payload = {
            usuario_id: usuario.id,
            id: editingReceita ? editingReceita.id : undefined,
            escopo: editingReceita?.escopo, // Envia o escopo de edição
            grupo_recorrencia_id: editingReceita?.grupo_recorrencia_id, // Adiciona o grupo da recorrencia para o back end
            ...form
        };

        if (!form.recorrente) {
            delete payload.parcelas;
            delete payload.frequencia;
        }

        try {
            await axios.post(RECEITAS_API_URL, payload);
            showNotification(`Receita salva com sucesso!`, 'success');
            setEditingReceita(null);
            fetchReceitas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar a receita.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleDelete = (receita) => {
        if (receita.grupo_recorrencia_id) {
            setModalState({
                isOpen: true,
                title: 'Excluir Receita Recorrente',
                message: 'Esta é uma receita recorrente. Como você deseja excluí-la?',
                onConfirm: () => {
                    executeDelete(receita, 'esta_e_futuras');
                    setModalState({ isOpen: false });
                },
                confirmText: 'Esta e as Futuras',
                onCancel: () => {
                    executeDelete(receita, 'apenas_esta');
                    setModalState({ isOpen: false });
                },
                cancelText: 'Apenas Esta Parcela',
                onClose: () => setModalState({ isOpen: false })
            });
        } else {
            if (window.confirm('Tem certeza que deseja excluir esta receita?')) {
                executeDelete(receita, 'apenas_esta');
            }
        }
    };

    const executeDelete = async (receita, escopo) => {
        try {
            await axios.delete(`${RECEITAS_API_URL}?id=${receita.id}&escopo=${escopo}`);
            showNotification('Receita(s) excluída(s) com sucesso!', 'success');
            fetchReceitas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao excluir a receita.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleEdit = (receita) => {
        if (receita.grupo_recorrencia_id) {
            setModalEditState({
                isOpen: true,
                title: 'Editar Receita Recorrente',
                message: 'Esta é uma receita recorrente. Como você deseja editá-la?',
                onConfirm: () => {
                    // Opção 1: Editar "esta e as futuras"
                    setEditingReceita({
                        ...receita,
                        quem_recebeu: receita.quem_recebeu_id,
                        forma_recebimento: receita.forma_recebimento_id,
                        escopo: 'esta_e_futuras' // Adiciona o escopo
                    });
                    setModalEditState({ isOpen: false });
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                confirmText: 'Esta e as Futuras',
                onCancel: () => {
                    // Opção 2: Editar "apenas esta parcela"
                    setEditingReceita({
                        ...receita,
                        quem_recebeu: receita.quem_recebeu_id,
                        forma_recebimento: receita.forma_recebimento_id,
                        escopo: 'apenas_esta' // Adiciona o escopo
                    });
                    setModalEditState({ isOpen: false });
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },
                cancelText: 'Apenas Esta Parcela',
                onClose: () => setModalEditState({ isOpen: false })
            });
        } else {
            // Fluxo original para receitas não recorrentes
            setEditingReceita({
                ...receita,
                quem_recebeu: receita.quem_recebeu_id,
                forma_recebimento: receita.forma_recebimento_id,
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    const handleCancel = () => setEditingReceita(null);
    const handleFiltroChange = (e) => setFiltroData(prev => ({ ...prev, [e.target.name]: e.target.value }));

    const handleSort = (key) => {
        setSortConfig(prevConfig => {
            if (prevConfig.key === key && prevConfig.direction === 'asc') {
                return { key, direction: 'desc' };
            }
            return { key, direction: 'asc' };
        });
    };

    const handleLimitChange = (e) => {
        setLimit(Number(e.target.value));
    };

    const handleFilterClick = () => {
        fetchReceitas();
    };

    // Função para determinar o status da receita
    const getStatusReceita = (dataPrevista, dataReal) => {
        if (dataReal) return 'recebido';

        const hoje = new Date().toISOString().split('T')[0];
        if (dataPrevista < hoje) return 'atrasado';
        if (dataPrevista === hoje) return 'hoje';
        return 'pendente';
    };

    // Função para formatar data
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
                onConfirm={modalState.onConfirm}
                confirmText={modalState.confirmText}
                onCancel={modalState.onCancel}
                cancelText={modalState.cancelText}
                onClose={() => setModalState({ isOpen: false })}
            >
                <p>{modalState.message}</p>
            </ModalConfirmacao>

            <ModalConfirmacao
                isOpen={modalEditState.isOpen}
                title={modalEditState.title}
                onConfirm={modalEditState.onConfirm}
                confirmText={modalEditState.confirmText}
                onCancel={modalEditState.onCancel}
                cancelText={modalEditState.cancelText}
                onClose={() => setModalEditState({ isOpen: false })}
            >
                <p>{modalEditState.message}</p>
            </ModalConfirmacao>

            <div className="content-card">
                <ReceitaForm
                    onSave={handleSave}
                    onCancel={handleCancel}
                    editingReceita={editingReceita}
                    initialFormState={initialFormState}
                    selectsData={selectsData}
                />
            </div>

            <div className="content-card">
                <h3 className="table-title">Últimas Receitas</h3>

                <div className="table-filters">
                    <div className="filter-group">
                        <label htmlFor="inicio">Data Início</label>
                        <input id="inicio" name="inicio" type="date" className="form-control" value={filtroData.inicio} onChange={handleFiltroChange} />
                    </div>
                    <div className="filter-group">
                        <label htmlFor="fim">Data Fim</label>
                        <input id="fim" name="fim" type="date" className="form-control" value={filtroData.fim} onChange={handleFiltroChange} />
                    </div>
                    <div className="filter-group">
                        <label htmlFor="limit">Mostrar</label>
                        <select id="limit" name="limit" className="form-control" value={limit} onChange={handleLimitChange}>
                            <option value={5}>5 linhas</option>
                            <option value={10}>10 linhas</option>
                            <option value={50}>50 linhas</option>
                            <option value={100}>100 linhas</option>
                        </select>
                    </div>
                    <div className="filter-group">
                        <label>&nbsp;</label>
                        <button className="btn btn-primary" onClick={handleFilterClick}>Filtrar</button>
                    </div>
                </div>

                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <SortableHeader name="quem_recebeu_nome" sortConfig={sortConfig} onSort={handleSort}>Quem Recebeu</SortableHeader>
                                    <SortableHeader name="categoria_nome" sortConfig={sortConfig} onSort={handleSort}>Categoria</SortableHeader>
                                    <SortableHeader name="valor" sortConfig={sortConfig} onSort={handleSort}>Valor</SortableHeader>
                                    <SortableHeader name="data_prevista_recebimento" sortConfig={sortConfig} onSort={handleSort}>Data Prevista</SortableHeader>
                                    <th>Data Recebimento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {receitas.length === 0 ? (
                                    <tr><td colSpan="7" className="empty-state">Nenhuma receita encontrada.</td></tr>
                                ) : (
                                    receitas.map(receita => {
                                        const status = getStatusReceita(receita.data_prevista_recebimento, receita.data_recebimento);
                                        return (
                                            <tr key={receita.id} className={`linha-${status}`}>
                                                <td>{receita.quem_recebeu_nome}</td>
                                                <td>{receita.categoria_nome}</td>
                                                <td>R$ {parseFloat(receita.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
                                                <td>{formatarData(receita.data_prevista_recebimento)}</td>
                                                <td>{formatarData(receita.data_recebimento)}</td>
                                                <td>
                                                    <span className={`status-badge ${status}`}>
                                                        {status === 'recebido' ? 'RECEBIDO' :
                                                            status === 'atrasado' ? 'ATRASADO' :
                                                                status === 'hoje' ? 'VENCE HOJE' : 'PENDENTE'}
                                                    </span>
                                                </td>
                                                <td className="table-buttons">
                                                    <button className="btn-icon" onClick={() => handleEdit(receita)} title="Editar">✏️</button>
                                                    <button className="btn-icon btn-delete" onClick={() => handleDelete(receita)} title="Excluir">🗑️</button>
                                                    {receita.grupo_recorrencia_id && <span title="Receita Recorrente">🔄</span>}
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