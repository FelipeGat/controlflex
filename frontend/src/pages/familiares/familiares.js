import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './familiares.css';
import { API_BASE_URL, UPLOADS_BASE_URL } from '../../apiConfig';
import Spinner from '../../components/Spinner';

// --- COMPONENTE ISOLADO PARA A IMAGEM (BOA PRÁTICA) ---
const FamiliarImage = ({ foto, nome }) => {
    const defaultAvatar = `${UPLOADS_BASE_URL}/default-avatar.png`;
    const [imageSrc, setImageSrc] = useState(foto ? `${UPLOADS_BASE_URL}/${foto}` : defaultAvatar);

    // Atualiza a imagem se a prop 'foto' mudar (ex: após editar)
    useEffect(() => {
        setImageSrc(foto ? `${UPLOADS_BASE_URL}/${foto}` : defaultAvatar);
    }, [foto]);

    const handleError = () => {
        if (imageSrc !== defaultAvatar) {
            setImageSrc(defaultAvatar);
        }
    };

    return <img src={imageSrc} alt={nome} className="foto-thumb" onError={handleError} />;
};

// --- COMPONENTE DO FORMULÁRIO (SEM MUDANÇAS NA LÓGICA INTERNA) ---
const FamiliarForm = ({ onSave, onCancel, editingFamiliar, initialFormState }) => {
    const [form, setForm] = useState(initialFormState);
    const [fotoPreview, setFotoPreview] = useState(null);
    const [fotoFile, setFotoFile] = useState(null);

    useEffect(() => {
        if (editingFamiliar) {
            setForm({ ...editingFamiliar });
            setFotoPreview(editingFamiliar.foto ? `${UPLOADS_BASE_URL}/${editingFamiliar.foto}` : null);
        } else {
            setForm(initialFormState);
            setFotoPreview(null);
        }
        setFotoFile(null);
    }, [editingFamiliar, initialFormState]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    };

    const handleFotoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setFotoFile(file);
            setFotoPreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form, fotoFile);
    };

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingFamiliar ? 'Editar Familiar' : 'Cadastrar Novo Familiar'}</h2>
            <div className="form-grid">
                <div className="form-group form-group-full-width">
                    <label htmlFor="nome">Nome *</label>
                    <input id="nome" name="nome" type="text" value={form.nome} onChange={handleChange} className="form-control" required />
                </div>
                <div className="form-group">
                    <label htmlFor="salario">Renda Total</label>
                    <input id="salario" name="salario" type="number" step="0.01" value={form.salario} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group">
                    <label htmlFor="limiteCartao">Limite Cartão Total</label>
                    <input id="limiteCartao" name="limiteCartao" type="number" step="0.01" value={form.limiteCartao} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="limiteCheque">Limite Cheque Total</label>
                    <input id="limiteCheque" name="limiteCheque" type="number" step="0.01" value={form.limiteCheque} onChange={handleChange} className="form-control" />
                </div>
                <div className="form-group form-group-full-width">
                    <label htmlFor="foto">Foto</label>
                    <input id="foto" name="foto" type="file" onChange={handleFotoChange} className="form-control" accept="image/*" />
                    {fotoPreview && <img src={fotoPreview} alt="Pré-visualização" style={{ width: '100px', height: '100px', objectFit: 'cover', borderRadius: '50%', marginTop: '10px' }} />}
                </div>
            </div>
            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>Cancelar</button>
                <button type="submit" className="btn btn-save">{editingFamiliar ? 'Salvar Alterações' : 'Adicionar Familiar'}</button>
            </div>
        </form>
    );
};

// --- COMPONENTE PRINCIPAL DA PÁGINA ---
export default function Familiares() {
    const [familiares, setFamiliares] = useState([]);
    const [editingFamiliar, setEditingFamiliar] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [notification, setNotification] = useState({ message: '', type: '' });
    const [searchTerm, setSearchTerm] = useState('');
    const [sortOrder, setSortOrder] = useState('asc');
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);

    const initialFormState = useMemo(() => ({
        nome: '', salario: '0.00', limiteCartao: '0.00', limiteCheque: '0.00', foto: ''
    }), []);

    // *** CORREÇÃO 1: URL da API aponta para o arquivo unificado ***
    const FAMILIARES_API_URL = `${API_BASE_URL}/familiares.php`;

    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user) {
            navigate('/');
        } else {
            setUsuario(user);
        }
    }, [navigate]);

    const fetchFamiliares = useCallback(async () => {
        if (!usuario) return;
        setIsLoading(true);
        try {
            // *** CORREÇÃO 2: GET para a API unificada ***
            const response = await axios.get(`${FAMILIARES_API_URL}?usuario_id=${usuario.id}`);
            setFamiliares(response.data || []);
        } catch (error) {
            showNotification('Erro ao carregar familiares.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, navigate, FAMILIARES_API_URL]);

    useEffect(() => {
        fetchFamiliares();
    }, [fetchFamiliares]);

    const showNotification = (message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    };

    // *** CORREÇÃO 3: handleSave unificado e simplificado ***
    const handleSave = async (form, fotoFile) => {
        const formData = new FormData();
        
        // Adiciona todos os campos do formulário ao FormData
        Object.keys(form).forEach(key => formData.append(key, form[key] ?? ''));
        formData.append('usuario_id', usuario.id);
        
        if (fotoFile) {
            formData.append('foto', fotoFile);
        } else if (editingFamiliar && editingFamiliar.foto) {
            // Se não houver foto nova, envia o nome da foto existente para o backend não apagar
            formData.append('foto_existente', editingFamiliar.foto);
        }

        if (editingFamiliar) {
            formData.append('id', editingFamiliar.id);
        }

        try {
            // A URL é sempre a mesma, o método é sempre POST
            await axios.post(FAMILIARES_API_URL, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            showNotification(`Familiar "${form.nome}" salvo com sucesso!`, 'success');
            setEditingFamiliar(null);
            fetchFamiliares();
        } catch (error) {
            const errorMsg = error.response?.data?.erro || 'Erro ao salvar o familiar.';
            showNotification(errorMsg, 'error');
        }
    };

    // *** CORREÇÃO 4: handleDelete unificado ***
    const handleDelete = async (id) => {
        if (window.confirm('Tem certeza que deseja excluir este familiar?')) {
            try {
                // Usa o método DELETE e passa o ID do usuário por segurança
                await axios.delete(`${FAMILIARES_API_URL}?id=${id}&usuario_id=${usuario.id}`);
                showNotification('Familiar excluído com sucesso!', 'success');
                fetchFamiliares();
            } catch (error) {
                const errorMessage = error.response?.data?.erro || 'Erro ao excluir o familiar.';
                showNotification(errorMessage, 'error');
            }
        }
    };

    const handleEdit = (familiar) => {
        setEditingFamiliar(familiar);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const handleCancel = () => {
        setEditingFamiliar(null);
    };

    const filteredAndSortedFamiliares = useMemo(() => {
        return [...familiares]
            .filter(f => f.nome.toLowerCase().includes(searchTerm.toLowerCase()))
            .sort((a, b) => {
                const comparison = a.nome.localeCompare(b.nome);
                return sortOrder === 'asc' ? comparison : -comparison;
            });
    }, [familiares, searchTerm, sortOrder]);

    const formatCurrency = (value) => `R$ ${parseFloat(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    return (
        <div className="page-container">
            {notification.message && <div className={`notification ${notification.type}`}>{notification.message}</div>}

            <div className="content-card">
                <FamiliarForm onSave={handleSave} onCancel={handleCancel} editingFamiliar={editingFamiliar} initialFormState={initialFormState} />
            </div>

            <div className="content-card">
                <h3 className="table-title">Lista de Familiares</h3>
                <div className="table-filters">
                    <div className="filter-group">
                        <label htmlFor="search-familiar">Buscar por Nome</label>
                        <input id="search-familiar" type="text" className="form-control" placeholder="Digite o nome do familiar..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} />
                    </div>
                </div>
                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th><button className="sort-button" onClick={() => setSortOrder(o => o === 'asc' ? 'desc' : 'asc')}>Nome {sortOrder === 'asc' ? '▲' : '▼'}</button></th>
                                    <th>Renda Total</th>
                                    <th>Limite Cartão</th>
                                    <th>Limite Cheque</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredAndSortedFamiliares.length > 0 ? filteredAndSortedFamiliares.map(f => (
                                    <tr key={f.id}>
                                        <td><FamiliarImage foto={f.foto} nome={f.nome} /></td>
                                        <td>{f.nome}</td>
                                        <td>{formatCurrency(f.salario)}</td>
                                        <td>{formatCurrency(f.limiteCartao)}</td>
                                        <td>{formatCurrency(f.limiteCheque)}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(f)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                <button onClick={() => handleDelete(f.id)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="6" className="empty-state">Nenhum familiar encontrado.</td></tr>
                                )}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}
