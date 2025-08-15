import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import './CadastroCategoria.css';
import { API_BASE_URL } from '../../apiConfig';
import Spinner from '../../components/Spinner';

// --- Dicionário de Ícones ---
const ICONS_MAP = {
    money: { emoji: '💰', label: 'Dinheiro' },
    food: { emoji: '🍽️', label: 'Alimentação' },
    car: { emoji: '🚗', label: 'Transporte' },
    home: { emoji: '🏠', label: 'Moradia' },
    shop: { emoji: '🛒', label: 'Compras' },
    education: { emoji: '🎓', label: 'Educação' },
    bills: { emoji: '💡', label: 'Contas' },
    gifts: { emoji: '🎁', label: 'Presentes' },
    health: { emoji: '❤️', label: 'Saúde' },
    travel: { emoji: '✈️', label: 'Viagem' },
};

// --- Componente IconSelector ---
const IconSelector = ({ value, onChange }) => (
    <select id="icone" name="icone" className="form-control" value={value} onChange={onChange} required>
        <option value="">Selecione um ícone</option>
        {Object.entries(ICONS_MAP).map(([key, { emoji, label }]) => (
            <option key={key} value={key}>{emoji} {label}</option>
        ))}
    </select>
);

// --- Componente CategoryForm ---
const CategoryForm = ({ onSave, onCancel, editingCategory }) => {
    const [form, setForm] = useState({ nome: '', tipo: 'DESPESA', icone: '' });

    useEffect(() => {
        if (editingCategory) {
            setForm({
                id: editingCategory.id,
                nome: editingCategory.nome,
                tipo: editingCategory.tipo,
                icone: editingCategory.icone,
            });
        } else {
            setForm({ nome: '', tipo: 'DESPESA', icone: '' });
        }
    }, [editingCategory]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!form.nome || !form.tipo || !form.icone) return;
        onSave(form);
    };

    return (
        <form onSubmit={handleSubmit} className="category-form">
            <h2 className="form-title">{editingCategory ? 'Editar Categoria' : 'Cadastrar Categoria'}</h2>
            <div className="form-grid">
                <div className="form-group form-group-full-width">
                    <label htmlFor="nome">Nome</label>
                    <input id="nome" name="nome" className="form-control" value={form.nome} onChange={handleChange} placeholder="Ex: Supermercado" required />
                </div>
                <div className="form-group">
                    <label htmlFor="tipo">Tipo</label>
                    <select id="tipo" name="tipo" className="form-control" value={form.tipo} onChange={handleChange} required>
                        <option value="DESPESA">Despesa</option>
                        <option value="RECEITA">Receita</option>
                    </select>
                </div>
                <div className="form-group">
                    <label htmlFor="icone">Ícone</label>
                    <IconSelector value={form.icone} onChange={handleChange} />
                </div>
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>Cancelar</button>
                <button type="submit" className="btn btn-save">{editingCategory ? 'Salvar Alterações' : 'Adicionar Categoria'}</button>
            </div>
        </form>
    );
};

// --- Componente Principal da Página ---
const CadastroCategoria = () => {
    const [categorias, setCategorias] = useState([]);
    const [editingCategory, setEditingCategory] = useState(null);
    const [notification, setNotification] = useState({ message: '', type: '' });
    const [filterType, setFilterType] = useState('todos');
    const [sortOrder, setSortOrder] = useState('asc');
    const [isLoading, setIsLoading] = useState(true);

    const CATEGORIAS_API_URL = `${API_BASE_URL}/categorias.php`;

    const fetchCategorias = useCallback(async () => {
        setIsLoading(true);
        try {
            const params = filterType !== 'todos' ? { tipo: filterType } : {};
            const response = await axios.get(CATEGORIAS_API_URL, { params });
            setCategorias(response.data || []);
        } catch (error) {
            console.error('Erro ao buscar categorias:', error);
            showNotification('Erro ao carregar categorias.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [CATEGORIAS_API_URL, filterType]);

    useEffect(() => {
        fetchCategorias();
    }, [fetchCategorias]);

    const handleSave = async (categoria) => {
        try {
            await axios.post(CATEGORIAS_API_URL, categoria);
            showNotification(`Categoria "${categoria.nome}" salva com sucesso!`, 'success');
            setEditingCategory(null);
            fetchCategorias();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar a categoria.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleDelete = async (id) => {
        if (window.confirm('Tem certeza que deseja excluir esta categoria?')) {
            try {
                await axios.delete(`${CATEGORIAS_API_URL}?id=${id}`);
                showNotification('Categoria excluída com sucesso!', 'success');
                fetchCategorias();
            } catch (error) {
                const errorMsg = error.response?.data?.erro || 'Erro ao excluir a categoria.';
                showNotification(errorMsg, 'error');
            }
        }
    };

    const filteredAndSortedCategorias = useMemo(() => {
        return [...categorias].sort((a, b) => {
            const nameA = a.nome.toLowerCase();
            const nameB = b.nome.toLowerCase();
            return sortOrder === 'asc' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
        });
    }, [categorias, sortOrder]);

    const toggleSortOrder = () => {
        setSortOrder(prev => (prev === 'asc' ? 'desc' : 'asc'));
    };

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    const handleEdit = (categoria) => {
        setEditingCategory(categoria);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => {
        setEditingCategory(null);
    };

    const getEmoji = (key) => ICONS_MAP[key]?.emoji || '❔';

    return (
        <div className="page-container">
            {notification.message && (
                <div className={`notification ${notification.type}`}>{notification.message}</div>
            )}

            <div className="content-card">
                <CategoryForm onSave={handleSave} onCancel={handleCancel} editingCategory={editingCategory} />
            </div>

            <div className="content-card">
                <h3 className="table-title">Categorias Cadastradas</h3>
                <div className="table-filters">
                    <div className="filter-group">
                        <label htmlFor="filter-tipo">Filtrar por Tipo</label>
                        <select id="filter-tipo" className="form-control" value={filterType} onChange={(e) => setFilterType(e.target.value)}>
                            <option value="todos">Todos</option>
                            <option value="RECEITA">Receita</option>
                            <option value="DESPESA">Despesa</option>
                        </select>
                    </div>
                </div>

                <div className="table-wrapper">
                    {isLoading ? (
                        <Spinner />
                    ) : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th className="icon-col">Ícone</th>
                                    <th><button className="sort-button" onClick={toggleSortOrder}>Nome {sortOrder === 'asc' ? ' ▲' : ' ▼'}</button></th>
                                    <th>Tipo</th>
                                    <th className="actions-col">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredAndSortedCategorias.length > 0 ? (
                                    filteredAndSortedCategorias.map((cat) => (
                                        <tr key={cat.id}>
                                            <td className="icon-col" aria-label={`Ícone: ${ICONS_MAP[cat.icone]?.label || 'Desconhecido'}`}><span className="icon-emoji">{getEmoji(cat.icone)}</span></td>
                                            <td>{cat.nome}</td>
                                            <td>
                                                <span className={`badge ${cat.tipo === 'RECEITA' ? 'badge-success' : 'badge-danger'}`}>
                                                    {cat.tipo.toLowerCase()}
                                                </span>
                                            </td>
                                            <td className="actions-col">
                                                <div className="table-buttons">
                                                    <button className="btn-icon" onClick={() => handleEdit(cat)} aria-label={`Editar ${cat.nome}`}><i className="fas fa-pen"></i></button>
                                                    <button className="btn-icon btn-delete" onClick={() => handleDelete(cat.id)} aria-label={`Excluir ${cat.nome}`}><i className="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr><td colSpan="4" className="empty-state">Nenhuma categoria encontrada.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
};

export default CadastroCategoria;
