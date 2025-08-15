import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './fornecedores.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner'; // Ajuste o caminho se necessário

// --- COMPONENTE DO FORMULÁRIO (sem alterações) ---
const FornecedorForm = ({ onSave, onCancel, editingFornecedor, initialFormState }) => {
    const [form, setForm] = useState(initialFormState);

    useEffect(() => {
        const initialState = editingFornecedor 
            ? { ...initialFormState, ...editingFornecedor } 
            : initialFormState;
        setForm(initialState);
    }, [editingFornecedor, initialFormState]);

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
            <h2 className="form-title">{editingFornecedor ? 'Editar Fornecedor' : 'Cadastrar Novo Fornecedor'}</h2>
            <div className="form-grid">
                <div className="form-group form-group-full-width">
                    <label htmlFor="nome">Nome do Fornecedor *</label>
                    <input id="nome" name="nome" type="text" value={form.nome} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group">
                    <label htmlFor="contato">Contato</label>
                    <input id="contato" name="contato" type="text" value={form.contato} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group">
                    <label htmlFor="cnpj">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" value={form.cnpj} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="telefone">Telefone</label>
                    <input id="telefone" name="telefone" type="text" value={form.telefone} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" />
                </div>
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>Cancelar</button>
                <button type="submit" className="btn btn-save">{editingFornecedor ? 'Salvar Alterações' : 'Adicionar Fornecedor'}</button>
            </div>
        </form>
    );
};

// --- COMPONENTE PRINCIPAL DA PÁGINA ---
export default function Fornecedores() {
    const [usuario, setUsuario] = useState(null); // Estado para guardar o objeto do usuário
    const [fornecedores, setFornecedores] = useState([]);
    const [editingFornecedor, setEditingFornecedor] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });
    const [searchTerm, setSearchTerm] = useState('');
    const [sortConfig, setSortConfig] = useState({ key: 'nome', direction: 'asc' });
    const navigate = useNavigate();

    const initialFormState = useMemo(() => ({
        nome: '', contato: '', cnpj: '', telefone: '', observacoes: ''
    }), []);

    const FORNECEDORES_API_URL = `${API_BASE_URL}/fornecedores.php`;

    // Efeito para carregar o usuário do localStorage APENAS UMA VEZ
    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user || !user.id) {
            navigate('/');
        } else {
            setUsuario(user);
        }
    }, [navigate]);

    // Efeito para buscar os fornecedores, dependendo do usuário
    const fetchFornecedores = useCallback(async () => {
        // Só executa se o usuário já foi carregado no estado
        if (!usuario) return; 

        setIsLoading(true);
        try {
            const response = await axios.get(`${FORNECEDORES_API_URL}?usuario_id=${usuario.id}`);
            setFornecedores(Array.isArray(response.data) ? response.data : []);
        } catch (error) {
            showNotification('Erro ao carregar fornecedores.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, FORNECEDORES_API_URL]); // Depende do objeto 'usuario'

    // Este useEffect agora reage à mudança no estado 'usuario'
    useEffect(() => {
        fetchFornecedores();
    }, [fetchFornecedores]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleSave = async (form) => {
        const payload = { ...form, usuario_id: usuario.id };
        const url = FORNECEDORES_API_URL;
        
        if (editingFornecedor) {
            payload.id = editingFornecedor.id;
        }

        try {
            await axios.post(url, payload);
            showNotification(`Fornecedor "${form.nome}" salvo com sucesso!`, 'success');
            setEditingFornecedor(null);
            fetchFornecedores(); // Re-busca os dados após salvar
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar o fornecedor.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleDelete = async (id) => {
        if (window.confirm('Tem certeza que deseja excluir este fornecedor?')) {
            try {
                // Passando o usuario_id também no delete para verificação de segurança no backend
                await axios.delete(`${FORNECEDORES_API_URL}?id=${id}&usuario_id=${usuario.id}`);
                showNotification('Fornecedor excluído com sucesso!', 'success');
                fetchFornecedores(); // Re-busca os dados após excluir
            } catch (error) {
                const errorMsg = error.response?.data?.erro || 'Erro ao excluir o fornecedor.';
                showNotification(errorMsg, 'error');
            }
        }
    };

    // O resto do componente permanece igual...
    const handleEdit = (fornecedor) => {
        setEditingFornecedor(fornecedor);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => {
        setEditingFornecedor(null);
    };

    const requestSort = (key) => {
        let direction = 'asc';
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            direction = 'desc';
        }
        setSortConfig({ key, direction });
    };

    const sortedAndFilteredFornecedores = useMemo(() => {
        let sortableItems = [...fornecedores];
        sortableItems = sortableItems.filter(f => f.nome.toLowerCase().includes(searchTerm.toLowerCase()));

        sortableItems.sort((a, b) => {
            if (a[sortConfig.key] < b[sortConfig.key]) {
                return sortConfig.direction === 'asc' ? -1 : 1;
            }
            if (a[sortConfig.key] > b[sortConfig.key]) {
                return sortConfig.direction === 'asc' ? 1 : -1;
            }
            return 0;
        });
        return sortableItems;
    }, [fornecedores, searchTerm, sortConfig]);

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
                <FornecedorForm onSave={handleSave} onCancel={handleCancel} editingFornecedor={editingFornecedor} initialFormState={initialFormState} />
            </div>

            <div className="content-card">
                <h3 className="table-title">Fornecedores Cadastrados</h3>
                <div className="table-filters">
                    <div className="filter-group">
                        <label htmlFor="search-fornecedor">Buscar por Nome</label>
                        <input id="search-fornecedor" type="text" className="form-control" placeholder="Digite o nome do fornecedor..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} />
                    </div>
                </div>
                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th><button className="sort-button" onClick={() => requestSort('nome')}>Nome{getSortIndicator('nome')}</button></th>
                                    <th><button className="sort-button" onClick={() => requestSort('contato')}>Contato{getSortIndicator('contato')}</button></th>
                                    <th>CNPJ</th>
                                    <th>Telefone</th>
                                    <th>Observações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedAndFilteredFornecedores.length > 0 ? sortedAndFilteredFornecedores.map(f => (
                                    <tr key={f.id}>
                                        <td>{f.nome}</td>
                                        <td>{f.contato || '-'}</td>
                                        <td>{f.cnpj || '-'}</td>
                                        <td>{f.telefone || '-'}</td>
                                        <td>{f.observacoes || '-'}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(f)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                <button onClick={() => handleDelete(f.id)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="6" className="empty-state">Nenhum fornecedor encontrado.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}
