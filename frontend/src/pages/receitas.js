import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './receitas.css'; // Usa o novo CSS unificado
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import ModalConfirmacao from './ModalConfirmacao';
import './ModalConfirmacao.css';

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

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form);
    };

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingReceita ? 'Editar Receita' : 'Cadastrar Receita'}</h2>
            <div className="form-grid">
                {/* Coluna da Esquerda */}
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
                        {selectsData.bancos.map(b => <option key={b.id} value={b.id}>{b.nome}</option>)}
                    </select>
                </div>

                {/* Coluna da Direita */}
                <div className="form-group">
                    <label htmlFor="valor">Valor (R$) *</label>
                    <input id="valor" name="valor" type="number" step="0.01" value={form.valor} onChange={handleChange} className="form-control" placeholder="0.00" required />
                </div>
                <div className="form-group">
                    <label htmlFor="data_recebimento">Data do Recebimento *</label>
                    <input id="data_recebimento" name="data_recebimento" type="date" value={form.data_recebimento} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" rows="3" />
                </div>
                
                {/* Opções de Recorrência */}
                <div className="form-group form-group-full-width" style={{ flexDirection: 'row', alignItems: 'center', gap: '1rem' }}>
                    <input id="recorrente" type="checkbox" name="recorrente" checked={form.recorrente} onChange={handleChange} style={{ width: 'auto', height: 'auto' }} />
                    <label htmlFor="recorrente" style={{ marginBottom: 0 }}>É uma receita recorrente?</label>
                </div>

                {form.recorrente && (
                    <div className="form-group form-group-full-width">
                        <label htmlFor="parcelas">Repetir por quantos meses?</label>
                        <input id="parcelas" name="parcelas" type="number" min="1" value={form.parcelas} onChange={handleChange} className="form-control" />
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

    const [modalState, setModalState] = useState({ isOpen: false, title: '', message: '', onConfirm: () => {}, onCancel: () => {} });

    const RECEITAS_API_URL = `${API_BASE_URL}/receitas.php`;

    const initialFormState = useMemo(() => ({
        quem_recebeu: '', categoria_id: '', forma_recebimento: '',
        valor: '', data_recebimento: new Date().toISOString().split('T')[0],
        recorrente: false, parcelas: 1, observacoes: ''
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
            const params = { usuario_id: usuario.id, ...filtroData };
            const response = await axios.get(RECEITAS_API_URL, { params });
            setReceitas(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar receitas.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, filtroData, RECEITAS_API_URL]);

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
                bancos: respBancos.data || []
            });
        } catch (error) {
            showNotification('Erro ao carregar dados de suporte.', 'error');
        }
    }, [usuario]);

    useEffect(() => {
        if (usuario) {
            fetchReceitas();
            fetchSelectsData();
        }
    }, [usuario, fetchReceitas, fetchSelectsData]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleSave = async (form) => {
        const payload = {
            usuario_id: usuario.id,
            id: editingReceita ? editingReceita.id : undefined,
            ...form
        };
        // Garante que o número de parcelas seja enviado corretamente
        if (form.recorrente) {
            payload.parcelas = form.parcelas;
        } else {
            delete payload.parcelas; // Remove para não confundir a API
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
        setEditingReceita({
            ...receita,
            // Garante que os IDs corretos sejam usados para preencher os selects
            quem_recebeu: receita.quem_recebeu_id,
            forma_recebimento: receita.forma_recebimento_id,
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => setEditingReceita(null);
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
                    
                    <div className="filter-group">
                        <label>&nbsp;</label>
                        <button className="btn btn-primary" onClick={fetchReceitas}>Filtrar</button>
                    </div>
                </div>

                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Quem Recebeu</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {receitas.length > 0 ? receitas.map(r => (
                                    <tr key={r.id}>
                                        <td>{r.quem_recebeu_nome}</td>
                                        <td>{r.categoria_nome}</td>
                                        <td>{`R$ ${parseFloat(r.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}</td>
                                        <td>{new Date(r.data_recebimento).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(r)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                <button onClick={() => handleDelete(r)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="5" className="empty-state">Nenhuma receita encontrada.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}
