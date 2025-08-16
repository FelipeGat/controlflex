import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './Investimentos.css'; // Criaremos este arquivo a seguir
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

// Lista de tipos de investimento para o select
const tiposDeInvestimento = [
    "Poupança", "Tesouro Direto", "CDB", "LCI/LCA", "CRI/CRA", 
    "Ações", "Fundos Imobiliários (FII)", "ETFs", "BDRs", 
    "Fundos de Investimento", "Debêntures"
];

// --- COMPONENTE DO FORMULÁRIO ---
const InvestimentoForm = ({ onSave, onCancel, editingInvestimento, initialFormState, selectsData }) => {
    const [form, setForm] = useState(initialFormState);

    useEffect(() => {
        setForm(editingInvestimento ? { ...editingInvestimento } : initialFormState);
    }, [editingInvestimento, initialFormState]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form);
    };

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingInvestimento ? 'Editar Aporte' : 'Adicionar Investimento'}</h2>
            <div className="form-grid">
                <div className="form-group">
                    <label htmlFor="nome_ativo">Nome do Ativo *</label>
                    <input id="nome_ativo" name="nome_ativo" value={form.nome_ativo} onChange={handleChange} className="form-control" placeholder="Ex: Tesouro Selic 2029, PETR4" required />
                </div>
                <div className="form-group">
                    <label htmlFor="tipo_investimento">Tipo de Investimento *</label>
                    <select id="tipo_investimento" name="tipo_investimento" value={form.tipo_investimento} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {tiposDeInvestimento.map(tipo => <option key={tipo} value={tipo}>{tipo}</option>)}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="banco_id">Corretora / Banco *</label>
                    <select id="banco_id" name="banco_id" value={form.banco_id} onChange={handleChange} className="form-control" required>
                        <option value="">Selecione...</option>
                        {selectsData.bancos.map(b => <option key={b.id} value={b.id}>{b.nome}</option>)}
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="data_aporte">Data do Aporte *</label>
                    <input id="data_aporte" name="data_aporte" type="date" value={form.data_aporte} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group">
                    <label htmlFor="valor_aportado">Valor Aportado (R$) *</label>
                    <input id="valor_aportado" name="valor_aportado" type="number" step="0.01" value={form.valor_aportado} onChange={handleChange} className="form-control" placeholder="0.00" required />
                </div>
                <div className="form-group">
                    <label htmlFor="quantidade_cotas">Quantidade (opcional)</label>
                    <input id="quantidade_cotas" name="quantidade_cotas" type="number" step="0.00000001" value={form.quantidade_cotas} onChange={handleChange} className="form-control" placeholder="Ex: 100" />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" rows="3" />
                </div>
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>Limpar</button>
                <button type="submit" className="btn btn-save">{editingInvestimento ? 'Salvar Alterações' : 'Adicionar Aporte'}</button>
            </div>
        </form>
    );
};

// --- COMPONENTE PRINCIPAL DA PÁGINA ---
export default function Investimentos() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [investimentos, setInvestimentos] = useState([]);
    const [editingInvestimento, setEditingInvestimento] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });
    const [selectsData, setSelectsData] = useState({ bancos: [] });

    const INVESTIMENTOS_API_URL = `${API_BASE_URL}/investimentos.php`;

    const initialFormState = useMemo(() => ({
        nome_ativo: '', tipo_investimento: '', banco_id: '',
        data_aporte: new Date().toISOString().split('T')[0],
        valor_aportado: '', quantidade_cotas: '', observacoes: ''
    }), []);

    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user) navigate('/');
        else setUsuario(user);
    }, [navigate]);

    const fetchInvestimentos = useCallback(async () => {
        if (!usuario) return;
        setIsLoading(true);
        try {
            const response = await axios.get(INVESTIMENTOS_API_URL, { params: { usuario_id: usuario.id } });
            setInvestimentos(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar investimentos.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, INVESTIMENTOS_API_URL]);

    const fetchSelectsData = useCallback(async () => {
        if (!usuario) return;
        try {
            const respBancos = await axios.get(`${API_BASE_URL}/bancos.php?usuario_id=${usuario.id}`);
            setSelectsData({ bancos: respBancos.data || [] });
        } catch (error) {
            showNotification('Erro ao carregar dados de suporte.', 'error');
        }
    }, [usuario]);

    useEffect(() => {
        if (usuario) {
            fetchInvestimentos();
            fetchSelectsData();
        }
    }, [usuario, fetchInvestimentos, fetchSelectsData]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleSave = async (form) => {
        const payload = { ...form, usuario_id: usuario.id };
        try {
            await axios.post(INVESTIMENTOS_API_URL, payload);
            showNotification('Investimento salvo com sucesso!', 'success');
            setEditingInvestimento(null);
            fetchInvestimentos();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar investimento.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleDelete = async (id) => {
        if (window.confirm('Tem certeza que deseja excluir este lançamento?')) {
            try {
                await axios.delete(`${INVESTIMENTOS_API_URL}?id=${id}`);
                showNotification('Lançamento excluído com sucesso!', 'success');
                fetchInvestimentos();
            } catch (error) {
                showNotification(error.response?.data?.erro || 'Erro ao excluir.', 'error');
            }
        }
    };

    const handleEdit = (investimento) => {
        setEditingInvestimento(investimento);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => setEditingInvestimento(null);

    return (
        <div className="page-container">
            {notification.message && <div className={`notification ${notification.type}`}>{notification.message}</div>}
            
            <div className="content-card">
                <InvestimentoForm 
                    onSave={handleSave} 
                    onCancel={handleCancel} 
                    editingInvestimento={editingInvestimento} 
                    initialFormState={initialFormState} 
                    selectsData={selectsData} 
                />
            </div>

            <div className="content-card">
                <h3 className="table-title">Histórico de Aportes</h3>
                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Ativo</th>
                                    <th>Tipo</th>
                                    <th>Corretora</th>
                                    <th>Data Aporte</th>
                                    <th>Valor Aportado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {investimentos.length > 0 ? investimentos.map(inv => (
                                    <tr key={inv.id}>
                                        <td>{inv.nome_ativo}</td>
                                        <td>{inv.tipo_investimento}</td>
                                        <td>{inv.banco_nome}</td>
                                        <td>{new Date(inv.data_aporte).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                                        <td>{`R$ ${parseFloat(inv.valor_aportado).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(inv)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                <button onClick={() => handleDelete(inv.id)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="6" className="empty-state">Nenhum investimento encontrado.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}
