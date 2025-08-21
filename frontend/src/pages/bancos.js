import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './bancos.css';
import { API_BASE_URL, BANK_ICONS_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';
import Select from 'react-select';

// --- ÍCONE DA CARTEIRA EM SVG ---
const WalletIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" style={{ width: '24px', height: '24px' }}>
        <path fill="#4A90E2" d="M576 176v256c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V176c0-35.3 28.7-64 64-64H512c35.3 0 64 28.7 64 64zm-32 80c0-8.8-7.2-16-16-16H416c-8.8 0-16 7.2-16 16v96c0 8.8 7.2 16 16 16h112c8.8 0 16-7.2 16-16V256zM64 128c-8.8 0-16 7.2-16 16v160c0 8.8 7.2 16 16 16h16c8.8 0 16-7.2 16-16V144c0-8.8-7.2-16-16-16H64zm272 16c0-8.8-7.2-16-16-16h-96c-8.8 0-16 7.2-16 16V368c0 8.8 7.2 16 16 16h96c8.8 0 16-7.2 16-16V144zM512 432H64V176h448v256zm-128-48a32 32 0 1 1-64 0 32 32 0 1 1 64 0z" />
    </svg>
);

// --- COMPONENTE DO FORMULÁRIO ---
const BankForm = ({ onSave, onCancel, editingBank, initialFormState, familiares }) => {
    const [form, setForm] = useState(initialFormState);
    const [temChequeEspecial, setTemChequeEspecial] = useState(false);
    const [temCartaoCredito, setTemCartaoCredito] = useState(false);

    useEffect(() => {
        if (editingBank) {
            const isCarteira = editingBank.nome === 'Carteira';
            setForm({
                ...initialFormState,
                ...editingBank,
                saldo: isCarteira ? '0' : (editingBank.saldo || '').toString(),
                limite_cartao: (editingBank.limite_cartao || '').toString(),
                cheque_especial: (editingBank.cheque_especial || '').toString(),
                saldo_cheque: (editingBank.saldo_cheque || '').toString(),
                saldo_cartao: (editingBank.saldo_cartao || '').toString(),
            });
            setTemChequeEspecial(parseFloat(editingBank.cheque_especial) > 0);
            setTemCartaoCredito(parseFloat(editingBank.limite_cartao) > 0);
        } else {
            setForm(initialFormState);
            setTemChequeEspecial(false);
            setTemCartaoCredito(false);
        }
    }, [editingBank, initialFormState]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    };

    const handleCheckboxChange = (e) => {
        const { name, checked } = e.target;
        if (name === 'tem_cheque_especial') {
            setTemChequeEspecial(checked);
            setForm(prev => ({ ...prev, cheque_especial: checked ? '' : '0', saldo_cheque: checked ? '' : '0' }));
        }
        if (name === 'tem_cartao_credito') {
            setTemCartaoCredito(checked);
            setForm(prev => ({ ...prev, limite_cartao: checked ? '' : '0', saldo_cartao: checked ? '' : '0' }));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(form, temChequeEspecial, temCartaoCredito);
    };

    const isCarteira = editingBank?.nome === 'Carteira' && editingBank?.tipo_conta === 'Dinheiro';

    const familiaresOptions = familiares.map(fam => ({
        value: fam.id,
        label: fam.nome
    }));

    // Adicionando console.log para depuração
    console.log("Familiares recebidos pelo formulário:", familiares);
    console.log("Opções formatadas para o Select:", familiaresOptions);

    const selectedTitular = familiaresOptions.find(opt => opt.value === form.titular_id) || null;

    const handleTitularSelectChange = (selectedOption) => {
        setForm(prev => ({
            ...prev,
            titular_id: selectedOption ? selectedOption.value : null
        }));
    };

    return (
        <form onSubmit={handleSubmit}>
            <h2 className="form-title">{editingBank ? 'Editar Banco' : 'Cadastrar Novo Banco'}</h2>
            <div className="form-grid">

                {/* Campos principais */}
                <div className="form-group form-group-full-width">
                    <label htmlFor="nome">Nome do Banco *</label>
                    <input
                        id="nome"
                        name="nome"
                        type="text"
                        value={form.nome}
                        onChange={handleChange}
                        className="form-control"
                        required
                        disabled={isCarteira}
                    />
                </div>

                <div className="form-group">
                    <label htmlFor="codigo_banco">Código do Banco *</label>
                    <input id="codigo_banco" name="codigo_banco" type="text" value={form.codigo_banco || ''} onChange={handleChange} className="form-control" required disabled={isCarteira} />
                </div>
                <div className="form-group">
                    <label htmlFor="agencia">Agência *</label>
                    <input id="agencia" name="agencia" type="text" value={form.agencia || ''} onChange={handleChange} className="form-control" required disabled={isCarteira} />
                </div>

                <div className="form-group">
                    <label htmlFor="conta">Conta Corrente</label>
                    <input id="conta" name="conta" type="text" value={form.conta || ''} onChange={handleChange} className="form-control" disabled={isCarteira} />
                </div>

                <div className="form-group">
                    <label htmlFor="conta_poupanca">Conta Poupança</label>
                    <input id="conta_poupanca" name="conta_poupanca" type="text" value={form.conta_poupanca || ''} onChange={handleChange} className="form-control" disabled={isCarteira} />
                </div>

                {/* Seleção de Titular com React-Select */}
                <div className="form-group form-group-full-width">
                    <label htmlFor="titular_id">Titular *</label>
                    <Select
                        id="titular_id"
                        name="titular_id"
                        value={selectedTitular}
                        onChange={handleTitularSelectChange}
                        options={familiaresOptions}
                        placeholder="Selecione ou digite o nome do titular..."
                        isClearable
                        isSearchable
                        required
                    />
                </div>

                {/* Campo de Saldo */}
                <div className="form-group form-group-full-width">
                    <label htmlFor="saldo">Saldo</label>
                    <input id="saldo" name="saldo" type="number" step="0.01" value={form.saldo} onChange={handleChange} className="form-control" />
                </div>

                {/* Checkbox para Cheque Especial - Estilizado como botão */}
                <div className="form-group form-group-full-width checkbox-container">
                    <input type="checkbox" id="tem_cheque_especial" name="tem_cheque_especial" checked={temChequeEspecial} onChange={handleCheckboxChange} disabled={isCarteira} />
                    <label htmlFor="tem_cheque_especial" className="btn-toggle-label">Possui Cheque Especial?</label>
                </div>
                {temChequeEspecial && (
                    <>
                        <div className="form-group form-group-full-width">
                            <label htmlFor="cheque_especial">Valor Total (Limite) *</label>
                            <input id="cheque_especial" name="cheque_especial" type="number" step="0.01" value={form.cheque_especial} onChange={handleChange} className="form-control" required={temChequeEspecial} />
                        </div>
                        <div className="form-group form-group-full-width">
                            <label htmlFor="saldo_cheque">Saldo Disponível *</label>
                            <input id="saldo_cheque" name="saldo_cheque" type="number" step="0.01" value={form.saldo_cheque} onChange={handleChange} className="form-control" required={temChequeEspecial} />
                        </div>
                    </>
                )}

                {/* Checkbox para Cartão de Crédito - Estilizado como botão */}
                <div className="form-group form-group-full-width checkbox-container">
                    <input type="checkbox" id="tem_cartao_credito" name="tem_cartao_credito" checked={temCartaoCredito} onChange={handleCheckboxChange} disabled={isCarteira} />
                    <label htmlFor="tem_cartao_credito" className="btn-toggle-label">Possui Cartão de Crédito?</label>
                </div>
                {temCartaoCredito && (
                    <>
                        <div className="form-group form-group-full-width">
                            <label htmlFor="limite_cartao">Valor Total (Limite) *</label>
                            <input id="limite_cartao" name="limite_cartao" type="number" step="0.01" value={form.limite_cartao} onChange={handleChange} className="form-control" required={temCartaoCredito} />
                        </div>
                        <div className="form-group form-group-full-width">
                            <label htmlFor="saldo_cartao">Saldo Disponível *</label>
                            <input id="saldo_cartao" name="saldo_cartao" type="number" step="0.01" value={form.saldo_cartao} onChange={handleChange} className="form-control" required={temCartaoCredito} />
                        </div>
                    </>
                )}
            </div>

            <div className="form-buttons">
                <button type="button" className="btn btn-cancel" onClick={onCancel}>Cancelar</button>
                <button type="submit" className="btn btn-save">{editingBank ? 'Salvar Alterações' : 'Adicionar Conta'}</button>
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
    const [familiares, setFamiliares] = useState([]);
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);

    const initialFormState = useMemo(() => ({
        nome: '',
        codigo_banco: '',
        agencia: '',
        conta: '',
        conta_poupanca: '',
        saldo: 0,
        limite_cartao: 0,
        saldo_cartao: 0,
        cheque_especial: 0,
        saldo_cheque: 0,
        tipo_conta: '',
        titular_id: ''
    }), []);

    const BANCOS_API_URL = `${API_BASE_URL}/bancos.php`;
    const FAMILIARES_API_URL = `${API_BASE_URL}/familiares.php`;

    const showNotification = useCallback((message, type) => {
        setNotification({ message, type });
        setTimeout(() => setNotification({ message: '', type: '' }), 3000);
    }, []);

    const fetchBancos = useCallback(async () => {
        if (!usuario) return;

        setIsLoading(true);
        try {
            const response = await axios.get(`${BANCOS_API_URL}?usuario_id=${usuario.id}`);
            const fetchedBancos = response.data.data || [];

            const formattedBancos = fetchedBancos.map(b => ({
                ...b,
                saldo: parseFloat(b.saldo),
                limite_cartao: parseFloat(b.limite_cartao),
                saldo_cartao: parseFloat(b.saldo_cartao),
                cheque_especial: parseFloat(b.cheque_especial),
                saldo_cheque: parseFloat(b.saldo_cheque)
            }));

            setBancos(formattedBancos);

        } catch (error) {
            console.error('Erro ao carregar bancos:', error);
            showNotification('Erro ao carregar bancos.', 'error');
        } finally {
            setIsLoading(false);
        }
    }, [usuario, BANCOS_API_URL, showNotification]);

    const fetchFamiliares = useCallback(async () => {
        if (!usuario) return;
        try {
            const response = await axios.get(`${FAMILIARES_API_URL}?usuario_id=${usuario.id}`);
            // Correção aqui: A API retorna um array diretamente, não um objeto com a chave 'data'
            console.log("Dados de familiares recebidos:", response.data);
            setFamiliares(response.data);
        } catch (error) {
            console.error('Erro ao carregar familiares:', error);
            showNotification('Erro ao carregar familiares.', 'error');
        }
    }, [usuario, FAMILIARES_API_URL, showNotification]);

    const checkAndCreateCarteira = useCallback(async () => {
        if (!usuario) return;
        try {
            const response = await axios.get(`${BANCOS_API_URL}?usuario_id=${usuario.id}`);
            const fetchedBancos = response.data.data || [];

            const carteiraExists = fetchedBancos.some(b => b.tipo_conta === 'Dinheiro');

            if (!carteiraExists) {
                console.log('Conta Carteira não encontrada. Criando...');
                await axios.post(BANCOS_API_URL, {
                    usuario_id: usuario.id,
                    nome: 'Carteira',
                    codigo_banco: null,
                    agencia: null,
                    conta: null,
                    conta_poupanca: null,
                    saldo: 0,
                    limite_cartao: 0,
                    saldo_cartao: 0,
                    cheque_especial: 0,
                    saldo_cheque: 0,
                    tipo_conta: 'Dinheiro',
                    titular_id: usuario.id
                });
                console.log('Conta Carteira criada com sucesso.');
            }
        } catch (error) {
            console.error('Erro ao verificar ou criar conta Carteira:', error);
        } finally {
            fetchBancos();
        }
    }, [usuario, BANCOS_API_URL, fetchBancos]);

    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user || !user.id) {
            navigate('/');
        } else {
            setUsuario(user);
        }
    }, [navigate]);

    useEffect(() => {
        if (usuario) {
            // Chamadas de API simultâneas para otimizar o carregamento
            Promise.all([
                fetchFamiliares(),
                checkAndCreateCarteira()
            ]).then(() => {
                setIsLoading(false);
            }).catch(() => {
                setIsLoading(false);
            });
        }
    }, [usuario, fetchFamiliares, checkAndCreateCarteira]);

    const handleSave = async (form, temChequeEspecial, temCartaoCredito) => {
        try {
            const isUpdate = form.id !== null && form.id !== undefined;

            let tipo_conta_inferido = '';
            if (temCartaoCredito) {
                tipo_conta_inferido = 'Cartão de Crédito';
            } else if (temChequeEspecial) {
                tipo_conta_inferido = 'Conta Corrente';
            } else if (form.conta_poupanca) {
                tipo_conta_inferido = 'Poupança';
            } else {
                tipo_conta_inferido = 'Conta Corrente';
            }

            if (form.nome === 'Carteira') {
                tipo_conta_inferido = 'Dinheiro';
            }

            const payload = {
                ...form,
                usuario_id: usuario.id,
                conta: form.conta || null,
                conta_poupanca: form.conta_poupanca || null,
                codigo_banco: form.codigo_banco || null,
                agencia: form.agencia || null,
                limite_cartao: temCartaoCredito ? parseFloat(form.limite_cartao) : 0,
                saldo_cartao: temCartaoCredito ? parseFloat(form.saldo_cartao) : 0,
                cheque_especial: temChequeEspecial ? parseFloat(form.cheque_especial) : 0,
                saldo_cheque: temChequeEspecial ? parseFloat(form.saldo_cheque) : 0,
                saldo: (!temChequeEspecial && !temCartaoCredito) ? parseFloat(form.saldo) : 0,
                tipo_conta: tipo_conta_inferido,
                id: isUpdate ? form.id : undefined
            };

            await axios.post(BANCOS_API_URL, payload);

            showNotification(`Conta "${form.nome}" salva com sucesso!`, 'success');
            setEditingBank(null);
            fetchBancos();
        } catch (error) {
            console.error('Erro ao salvar conta:', error);
            const errorMsg = error.response?.data?.mensagem || error.response?.data?.erro || 'Erro ao salvar a conta.';
            showNotification(errorMsg, 'error');
        }
    };

    const handleDelete = async (id) => {
        if (window.confirm('Tem certeza que deseja excluir esta conta?')) {
            try {
                await axios.delete(`${BANCOS_API_URL}?id=${id}&usuario_id=${usuario.id}`);
                showNotification('Conta excluída com sucesso!', 'success');
                fetchBancos();
            } catch (error) {
                const errorMsg = error.response?.data?.mensagem || error.response?.data?.erro || 'Erro ao excluir a conta.';
                showNotification(errorMsg, 'error');
            }
        }
    };

    const handleEdit = (banco) => {
        setEditingBank({
            ...banco,
            saldo: banco.saldo.toString(),
            limite_cartao: banco.limite_cartao.toString(),
            saldo_cartao: banco.saldo_cartao.toString(),
            cheque_especial: banco.cheque_especial.toString(),
            saldo_cheque: banco.saldo_cheque.toString(),
        });
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
                {isLoading ? <Spinner /> : (
                    <BankForm
                        onSave={handleSave}
                        onCancel={handleCancel}
                        editingBank={editingBank}
                        initialFormState={initialFormState}
                        familiares={familiares}
                    />
                )}
            </div>

            <div className="content-card">
                <h3 className="table-title">Contas Cadastradas</h3>
                <div className="table-filters">
                    <div className="filter-group">
                        <label htmlFor="search-banco">Buscar por Nome</label>
                        <input id="search-banco" type="text" className="form-control" placeholder="Digite o nome da conta..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} />
                    </div>
                </div>
                <div className="table-wrapper">
                    {isLoading ? <Spinner /> : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Ícone</th>
                                    <th><button className="sort-button" onClick={() => requestSort('nome')}>Nome{getSortIndicator('nome')}</button></th>
                                    <th><button className="sort-button" onClick={() => requestSort('tipo_conta')}>Tipo{getSortIndicator('tipo_conta')}</button></th>
                                    <th><button className="sort-button" onClick={() => requestSort('codigo_banco')}>Cód.{getSortIndicator('codigo_banco')}</button></th>
                                    <th>Agência</th>
                                    <th>Conta Corrente</th>
                                    <th>Conta Poupança</th>
                                    <th>Saldo</th>
                                    <th>Cheque Esp.</th>
                                    <th>Cartão</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedAndFilteredBancos.length > 0 ? sortedAndFilteredBancos.map(b => (
                                    <tr key={b.id}>
                                        <td>
                                            {b.tipo_conta === 'Dinheiro' ? (
                                                <WalletIcon />
                                            ) : (
                                                <img src={`${BANK_ICONS_BASE_URL}/${String(b.codigo_banco).padStart(3, '0')}.png`} alt={b.nome} className="icone-thumb" onError={(e) => { e.target.onerror = null; e.target.src = `${BANK_ICONS_BASE_URL}/default-bank.png`; }} />
                                            )}
                                        </td>
                                        <td>{b.nome}</td>
                                        <td>{b.tipo_conta}</td>
                                        <td>{b.codigo_banco || '-'}</td>
                                        <td>{b.agencia || '-'}</td>
                                        <td>{b.conta || '-'}</td>
                                        <td>{b.conta_poupanca || '-'}</td>
                                        <td>{formatCurrency(b.saldo)}</td>
                                        <td>{b.cheque_especial > 0 ? formatCurrency(b.cheque_especial) : '-'}</td>
                                        <td>{b.limite_cartao > 0 ? formatCurrency(b.limite_cartao) : '-'}</td>
                                        <td>
                                            <div className="table-buttons">
                                                <button onClick={() => handleEdit(b)} className="btn-icon" title="Editar"><i className="fas fa-pen"></i></button>
                                                {b.tipo_conta !== 'Dinheiro' && (
                                                    <button onClick={() => handleDelete(b.id)} className="btn-icon btn-delete" title="Excluir"><i className="fas fa-trash"></i></button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="11" className="empty-state">Nenhuma conta encontrada.</td></tr>
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