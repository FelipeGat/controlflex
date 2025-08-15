import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './bancos.css';
import { API_BASE_URL, BANK_ICONS_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

// --- COMPONENTE DO FORMULÁRIO (Mantido igual, já estava bom) ---
const BankForm = ({ onSave, onCancel, editingBank, initialFormState }) => {
    const [form, setForm] = useState(initialFormState);

    useEffect(() => {
        // Se estiver editando, preenche o form. Senão, usa o estado inicial.
        const initialState = editingBank 
            ? { ...initialFormState, ...editingBank } 
            : initialFormState;
        setForm(initialState);
    }, [editingBank, initialFormState]);

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
            <h2 className="form-title">{editingBank ? 'Editar Banco' : 'Cadastrar Novo Banco'}</h2>
            <div className="form-grid">
                <div className="form-group form-group-full-width">
                    <label htmlFor="nome">Nome do Banco *</label>
                    <input id="nome" name="nome" type="text" value={form.nome} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group">
                    <label htmlFor="codigo_banco">Código do Banco *</label>
                    <input id="codigo_banco" name="codigo_banco" type="text" value={form.codigo_banco} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group">
                    <label htmlFor="agencia">Agência *</label>
                    <input id="agencia" name="agencia" type="text" value={form.agencia} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="conta">Conta Corrente</label>
                    <input id="conta" name="conta" type="text" value={form.conta} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group">
                    <label htmlFor="saldo">Saldo</label>
                    <input id="saldo" name="saldo" type="number" step="0.01" value={form.saldo} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group">
                    <label htmlFor="limite_cartao">Limite Cartão de Crédito</label>
                    <input id="limite_cartao" name="limite_cartao" type="number" step="0.01" value={form.limite_cartao} onChange={handleChange} className="form-control" />
                </div>
                 <div className="form-group form-group-full-width">
                    <label htmlFor="cheque_especial">Limite Cheque Especial</label>
                    <input id="cheque_especial" name="cheque_especial" type="number" step="0.01" value={form.cheque_especial} onChange={handleChange} className="form-control" />
                </div>
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>Cancelar</button>
                <button type="submit" className="btn btn-save">{editingBank ? 'Salvar Alterações' : 'Adicionar Banco'}</button>
            </div>
        </form>
    );
};


// --- COMPONENTE PRINCIPAL DA PÁGINA ---
function Bancos() {
    const [bancos, setBancos] = useState([]);
    const [editingBank, setEditingBank] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });
    const [searchTerm, setSearchTerm] = useState('');
    const [sortConfig, setSortConfig] = useState({ key: 'nome', direction: 'asc' });
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);

    const initialFormState = useMemo(() => ({
        nome: '', codigo_banco: '', agencia: '', conta: '', saldo: '0.00', limite_cartao: '0.00', cheque_especial: '0.00'
    }), []);

    const BANCOS_API_URL = `${API_BASE_URL}/bancos.php`;

    // Efeito para carregar o usuário do localStorage
    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user || !user.id) {
            navigate('/');
        } else {
            setUsuario(user);
        }
    }, [navigate]);

    // Função para buscar os bancos, agora dependente do estado 'usuario'
    const fetchBancos = useCallback(async () => {
        if (!usuario) return; // Só busca se o usuário estiver carregado

        setIsLoading(true);
        try {
            const response = await axios.get(`${BANCOS_API_URL}?usuario_id=${usuario.id}`);
            setBancos(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar bancos.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, BANCOS_API_URL]); // Depende do usuário

    // Dispara a busca quando o usuário for definido
    useEffect(() => {
        fetchBancos();
    }, [fetchBancos]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    // *** CORREÇÃO PRINCIPAL 1: handleSave unificado ***
    const handleSave = async (form) => {
        const payload = { ...form, usuario_id: usuario.id };
        
        // Se estiver editando, adiciona o ID ao payload
        if (editingBank) {
            payload.id = editingBank.id;
        }

        try {
            // A requisição é sempre POST para o mesmo arquivo.
            // O backend decidirá se é INSERT ou UPDATE com base na presença do 'id'.
            await axios.post(BANCOS_API_URL, payload);
            
            showNotification(`Banco "${form.nome}" salvo com sucesso!`, 'success');
            setEditingBank(null);
            fetchBancos(); // Atualiza a lista
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar o banco.';
            showNotification(errorMsg, 'error');
        }
    };

    // *** CORREÇÃO PRINCIPAL 2: handleDelete corrigido ***
    const handleDelete = async (id) => {
        if (window.confirm('Tem certeza que deseja excluir este banco?')) {
            try {
                // A URL aponta para bancos.php e o método é DELETE.
                // O ID é passado como parâmetro na URL.
                await axios.delete(`${BANCOS_API_URL}?id=${id}&usuario_id=${usuario.id}`);
                
                showNotification('Banco excluído com sucesso!', 'success');
                fetchBancos(); // Atualiza a lista
            } catch (error) {
                const errorMsg = error.response?.data?.erro || 'Erro ao excluir o banco.';
                showNotification(errorMsg, 'error');
            }
        }
    };

    const handleEdit = (banco) => {
        setEditingBank(banco);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => {
        setEditingBank(null);
    };

    const requestSort = (key) => {
        let direction = 'asc';
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            direction = 'desc';
        }
        setSortConfig({ key, direction });
    };

    const sortedAndFilteredBancos = useMemo(() => {
        let sortableItems = [...bancos];
        if (searchTerm) {
            sortableItems = sortableItems.filter(b => b.nome.toLowerCase().includes(searchTerm.toLowerCase()));
        }

        sortableItems.sort((a, b) => {
            if (a[sortConfig.key] < b[sortConfig.key]) return sortConfig.direction === 'asc' ? -1 : 1;
            if (a[sortConfig.key] > b[sortConfig.key]) return sortConfig.direction === 'asc' ? 1 : -1;
            return 0;
        });
        return sortableItems;
    }, [bancos, searchTerm, sortConfig]);

    const formatCurrency = (value) => `R$ ${parseFloat(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    
    const getSortIndicator = (key) => {
        if (sortConfig.key === key) {
            return sortConfig.direction === 'asc' ? ' ▲' : ' ▼';
        }
        return '';
    };

    return (
        <div className="page-container">
            {notification.message && <div className={`notification ${notification.type}`}>{notification.message}</div>}

            <div className="content-card">
                <BankForm onSave={handleSave} onCancel={handleCancel} editingBank={editingBank} initialFormState={initialFormState} />
            </div>

            <div className="content-card">
                <h3 className="table-title">Bancos Cadastrados</h3>
                <div className="table-filters">
                    <div className="filter-group">
                        <label htmlFor="search-banco">Buscar por Nome</label>
                        <input id="search-banco" type="text" className="form-control" placeholder="Digite o nome do banco..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} />
                    </div>
                </div>
                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Ícone</th>
                                    <th><button className="sort-button" onClick={() => requestSort('nome')}>Nome{getSortIndicator('nome')}</button></th>
                                    <th><button className="sort-button" onClick={() => requestSort('codigo_banco')}>Cód.{getSortIndicator('codigo_banco')}</button></th>
                                    <th>Agência</th>
                                    <th>Conta</th>
                                    <th>Saldo</th>
                                    <th>Cartão</th>
                                    <th>Cheque</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedAndFilteredBancos.length > 0 ? sortedAndFilteredBancos.map(b => (
                                    <tr key={b.id}>
                                        <td><img src={`${BANK_ICONS_BASE_URL}/${String(b.codigo_banco).padStart(3, '0')}.png`} alt={b.nome} className="icone-thumb" onError={(e) => { e.target.onerror = null; e.target.src = `${BANK_ICONS_BASE_URL}/default-bank.png`; }} /></td>
                                        <td>{b.nome}</td>
                                        <td>{b.codigo_banco}</td>
                                        <td>{b.agencia}</td>
                                        <td>{b.conta || '-'}</td>
                                        <td>{formatCurrency(b.saldo)}</td>
                                        <td>{formatCurrency(b.limite_cartao)}</td>
                                        <td>{formatCurrency(b.cheque_especial)}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(b)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                <button onClick={() => handleDelete(b.id)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="9" className="empty-state">Nenhum banco encontrado.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}

export default Bancos;
