import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './despesas.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import ModalConfirmacao from './ModalConfirmacao'; 
import './ModalConfirmacao.css';

// --- COMPONENTE DO FORMULÁRIO ---
const DespesaForm = ({ onSave, onCancel, editingDespesa, initialFormState, selectsData }) => {
    const [form, setForm] = useState(initialFormState);

    useEffect(() => {
        setForm(editingDespesa ? { ...editingDespesa } : initialFormState);
    }, [editingDespesa, initialFormState]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        const val = type === 'checkbox' ? checked : value;
        setForm(prev => ({ ...prev, [name]: val }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form);
    };

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingDespesa ? 'Editar Despesa' : 'Cadastrar Despesa'}</h2>
            <div className="form-grid">
                {/* Coluna da Esquerda */}
                <div className="form-group">
                    <label htmlFor="quem_comprou">Quem Comprou *</label>
                    <select id="quem_comprou" name="quem_comprou" value={form.quem_comprou} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {selectsData.familiares.map(f => <option key={f.id} value={f.id}>{f.nome}</option>)}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="onde_comprou">Fornecedor *</label>
                    <select id="onde_comprou" name="onde_comprou" value={form.onde_comprou} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {selectsData.fornecedores.map(f => <option key={f.id} value={f.id}>{f.nome}</option>)}
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
                    <label htmlFor="forma_pagamento">Forma de Pagamento *</label>
                    <select id="forma_pagamento" name="forma_pagamento" value={form.forma_pagamento} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="DINHEIRO">Dinheiro</option>
                        <option value="PIX">PIX</option>
                        <option value="CARTAO_CREDITO">Cartão de Crédito</option>
                        <option value="CARTAO_DEBITO">Cartão de Débito</option>
                        <option value="BOLETO">Boleto</option>
                    </select>
                </div>

                {/* Coluna da Direita */}
                <div className="form-group">
                    <label htmlFor="valor">Valor (R$) *</label>
                    <input id="valor" name="valor" type="number" step="0.01" value={form.valor} onChange={handleChange} className="form-control" placeholder="0.00" required />
                </div>
                <div className="form-group">
                    <label htmlFor="data_compra">Data da Compra *</label>
                    <input id="data_compra" name="data_compra" type="date" value={form.data_compra} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" rows="3" />
                </div>
                
                {/* Opções de Recorrência */}
                <div className="form-group form-group-full-width" style={{ flexDirection: 'row', alignItems: 'center', gap: '1rem' }}>
                    <input id="recorrente" type="checkbox" name="recorrente" checked={form.recorrente} onChange={handleChange} style={{ width: 'auto', height: 'auto' }} />
                    <label htmlFor="recorrente" style={{ marginBottom: 0 }}>É uma conta recorrente?</label>
                </div>

                {form.recorrente && (
                    <div className="form-group form-group-full-width">
                        <label htmlFor="parcelas">Repetir por quantos meses?</label>
                        <input id="parcelas" name="parcelas" type="number" min="1" value={form.parcelas} onChange={handleChange} className="form-control" />
                    </div>
                )}
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>{editingDespesa ? 'Cancelar' : 'Limpar'}</button>
                <button type="submit" className="btn btn-save">{editingDespesa ? 'Salvar Alterações' : 'Adicionar Despesa'}</button>
            </div>
        </form>
    );
};


// --- COMPONENTE PRINCIPAL DA PÁGINA ---
export default function Despesas() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [despesas, setDespesas] = useState([]);
    const [editingDespesa, setEditingDespesa] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });
    
    const [selectsData, setSelectsData] = useState({ familiares: [], fornecedores: [], categorias: [] });
    const [filtroData, setFiltroData] = useState({ inicio: '', fim: '' });

    const [modalState, setModalState] = useState({ isOpen: false, title: '', message: '', onConfirm: () => {}, onCancel: () => {} });

    const DESPESAS_API_URL = `${API_BASE_URL}/despesas.php`;

    const initialFormState = useMemo(() => ({
        quem_comprou: '', onde_comprou: '', categoria_id: '', forma_pagamento: '',
        valor: '', data_compra: new Date().toISOString().split('T')[0],
        recorrente: false, parcelas: 1, observacoes: ''
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
            const params = { usuario_id: usuario.id, ...filtroData };
            const response = await axios.get(DESPESAS_API_URL, { params });
            setDespesas(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar despesas.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, filtroData, DESPESAS_API_URL]);

    const fetchSelectsData = useCallback(async () => {
        if (!usuario) return;
        try {
            const [respFamiliares, respFornecedores, respCategorias] = await Promise.all([
                axios.get(`${API_BASE_URL}/familiares.php?usuario_id=${usuario.id}`),
                axios.get(`${API_BASE_URL}/fornecedores.php?usuario_id=${usuario.id}`),
                axios.get(`${API_BASE_URL}/categorias.php?tipo=DESPESA`)
            ]);
            setSelectsData({
                familiares: respFamiliares.data || [],
                fornecedores: respFornecedores.data || [],
                categorias: respCategorias.data || []
            });
        } catch (error) {
            showNotification('Erro ao carregar dados de suporte.', 'error');
        }
    }, [usuario]);

    useEffect(() => {
        if (usuario) {
            fetchDespesas();
            fetchSelectsData();
        }
    }, [usuario, fetchDespesas, fetchSelectsData]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleSave = async (form) => {
        const payload = {
            usuario_id: usuario.id,
            id: editingDespesa ? editingDespesa.id : undefined,
            quem_comprou: form.quem_comprou,
            onde_comprou: form.onde_comprou,
            categoria_id: form.categoria_id,
            forma_pagamento: form.forma_pagamento,
            valor: form.valor,
            data_compra: form.data_compra,
            recorrente: form.recorrente,
            observacoes: form.observacoes
        };

        if (form.recorrente) {
            payload.parcelas = form.parcelas;
        }

        try {
            await axios.post(DESPESAS_API_URL, payload);
            showNotification(`Despesa salva com sucesso!`, 'success');
            setEditingDespesa(null);
            fetchDespesas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar a despesa.';
            showNotification(errorMsg, 'error');
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
            if (window.confirm('Tem certeza que deseja excluir esta despesa?')) {
                executeDelete(despesa, 'apenas_esta');
            }
        }
    };

    const executeDelete = async (despesa, escopo) => {
        try {
            await axios.delete(`${DESPESAS_API_URL}?id=${despesa.id}&escopo=${escopo}`);
            showNotification('Despesa(s) excluída(s) com sucesso!', 'success');
            fetchDespesas();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao excluir a despesa.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleEdit = (despesa) => {
        setEditingDespesa({
            ...despesa,
            quem_comprou: despesa.quem_comprou_id,
            onde_comprou: despesa.onde_comprou_id,
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => setEditingDespesa(null);
    const handleFiltroChange = (e) => setFiltroData(prev => ({ ...prev, [e.target.name]: e.target.value }));

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
                onClose={modalState.onClose}
            >
                <p>{modalState.message}</p>
            </ModalConfirmacao>

            <div className="content-card">
                <DespesaForm 
                    onSave={handleSave} 
                    onCancel={handleCancel} 
                    editingDespesa={editingDespesa} 
                    initialFormState={initialFormState} 
                    selectsData={selectsData} 
                />
            </div>

            <div className="content-card">
                <h3 className="table-title">Últimas Despesas</h3>
                
                <div className="table-filters">
                    {/* Agrupa os campos de data */}
                    <div className="filter-date-inputs">
                        <div className="filter-group">
                            <label htmlFor="inicio">Data Início</label>
                            <input id="inicio" name="inicio" type="date" className="form-control" value={filtroData.inicio} onChange={handleFiltroChange} />
                        </div>
                        <div className="filter-group">
                            <label htmlFor="fim">Data Fim</label>
                            <input id="fim" name="fim" type="date" className="form-control" value={filtroData.fim} onChange={handleFiltroChange} />
                        </div>
                    </div>
                    
                    {/* Mantém o botão em seu próprio grupo para alinhamento */}
                    <div className="filter-group">
                        <label>&nbsp;</label>
                        <button className="btn btn-primary" onClick={fetchDespesas}>Filtrar</button>
                    </div>
                </div>

                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Quem Comprou</th>
                                    <th>Fornecedor</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {despesas.length > 0 ? despesas.map(d => (
                                    <tr key={d.id}>
                                        <td>{d.quem_comprou_nome}</td>
                                        <td>{d.onde_comprou_nome}</td>
                                        <td>{d.categoria_nome}</td>
                                        <td>{`R$ ${parseFloat(d.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}</td>
                                        <td>{new Date(d.data_compra).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(d)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                <button onClick={() => handleDelete(d)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="6" className="empty-state">Nenhuma despesa encontrada.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}
