import React, { useState, useEffect } from 'react';
import './despesas.css'; // Mantém a importação do CSS de despesas
import { useNavigate } from 'react-router-dom';
import { API_BASE_URL } from '../apiConfig';
import ModalConfirmacao from './ModalConfirmacao';
import './ModalConfirmacao.css';

function Despesas() {
  const navigate = useNavigate();
  const [usuario, setUsuario] = useState(null);

  // Estados para os selects
  const [familiares, setFamiliares] = useState([]);
  const [fornecedores, setFornecedores] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [ultimasDespesas, setUltimasDespesas] = useState([]);

  const [despesaEditandoId, setDespesaEditandoId] = useState(null);

  // Estados para filtro de datas (padronizado com receitas.js)
  const [filtroDataInicio, setFiltroDataInicio] = useState('');
  const [filtroDataFim, setFiltroDataFim] = useState('');

  // Estado para controlar o Modal de Confirmação
  const [modalState, setModalState] = useState({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: () => {},
    onCancel: () => {},
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
  });

  // Estado do formulário
  const [form, setForm] = useState({
    quemComprou: '',
    fornecedor: '',
    categoriaId: '',
    formaPagamento: '',
    valor: '',
    dataCompra: new Date().toISOString().split('T')[0],
    recorrente: false,
    parcelas: 1,
    observacoes: ''
  });

  // Carrega usuário logado
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  // Carrega dados iniciais (selects e últimas despesas)
  useEffect(() => {
    if (!usuario) return;

    const carregarDadosIniciais = async () => {
      try {
        const [respFamiliares, respFornecedores, respCategorias] = await Promise.all([
          fetch(`${API_BASE_URL}/familiares/familiares.php?usuario_id=${usuario.id}`),
          fetch(`${API_BASE_URL}/fornecedores.php?usuario_id=${usuario.id}`),
          fetch(`${API_BASE_URL}/categorias/categorias.php?tipo=DESPESA`)
        ]);

        const [dadosFamiliares, dadosFornecedores, dadosCategorias] = await Promise.all([
          respFamiliares.json(),
          respFornecedores.json(),
          respCategorias.json()
        ]);

        setFamiliares(dadosFamiliares);
        setFornecedores(dadosFornecedores);
        setCategorias(dadosCategorias);
      } catch (error) {
        console.error('Erro ao carregar dados dos selects:', error);
      }
    };

    carregarUltimasDespesas();
    carregarDadosIniciais();
  }, [usuario]);

  // Função para carregar a lista de despesas, agora com filtro de datas
  const carregarUltimasDespesas = async () => {
    if (!usuario) return;

    let url = `${API_BASE_URL}/despesas/listar_despesas.php?usuario_id=${usuario.id}`;

    // Usa os estados padronizados
    if (filtroDataInicio && filtroDataFim) {
      url += `&inicio=${filtroDataInicio}&fim=${filtroDataFim}`;
    }

    try {
      const res = await fetch(url);
      const data = await res.json();
      setUltimasDespesas(Array.isArray(data) ? data.slice(0, 50) : []);
    } catch (err) {
      console.error('Erro ao carregar últimas despesas:', err);
    }
  };

  // Handler para mudanças nos inputs
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;

    if (name === 'valor') {
      let cleaned = value.replace(/[^0-9,]/g, '');
      const parts = cleaned.split(',');
      if (parts.length > 2) {
        cleaned = parts[0] + ',' + parts.slice(1).join('');
      }
      setForm(prev => ({ ...prev, valor: cleaned }));
      return;
    }

    if (name === 'parcelas') {
      const parsed = parseInt(value, 10);
      setForm(prev => ({ ...prev, parcelas: isNaN(parsed) || parsed < 0 ? 0 : parsed }));
      return;
    }

    setForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  // Reseta o formulário
  const resetForm = () => {
    setForm({
      quemComprou: '',
      fornecedor: '',
      categoriaId: '',
      formaPagamento: '',
      valor: '',
      dataCompra: new Date().toISOString().split('T')[0],
      recorrente: false,
      parcelas: 1,
      observacoes: ''
    });
    setDespesaEditandoId(null);
  };

  // Salva ou atualiza uma despesa
  const salvarDespesa = async (e) => {
    e.preventDefault();

    const valorNumerico = parseFloat(form.valor.replace(',', '.'));
    if (isNaN(valorNumerico) || valorNumerico <= 0) {
      alert('O valor da despesa é inválido.');
      return;
    }

    const payload = {
      id: despesaEditandoId,
      usuario_id: usuario.id,
      quem_comprou: form.quemComprou,
      onde_comprou: form.fornecedor,
      categoria_id: parseInt(form.categoriaId),
      forma_pagamento: form.formaPagamento,
      valor: valorNumerico,
      data_compra: form.dataCompra,
      recorrente: form.recorrente ? 1 : 0,
      parcelas: form.recorrente ? form.parcelas : 1,
      observacoes: form.observacoes
    };

    try {
      const res = await fetch(`${API_BASE_URL}/despesas/salvar_despesas.php`, {
        method: despesaEditandoId ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.sucesso) {
        alert(data.mensagem || 'Operação realizada com sucesso!');
        resetForm();
        carregarUltimasDespesas();
      } else {
        alert(data.erro || 'Erro ao salvar a despesa.');
      }
    } catch (err) {
      console.error('Erro ao salvar despesa:', err);
      alert('Erro de conexão ao salvar a despesa.');
    }
  };

  // Preenche o formulário para edição
  const editarDespesa = (despesa) => {
    setForm({
      quemComprou: despesa.quem_comprou_id,
      fornecedor: despesa.onde_comprou_id,
      categoriaId: despesa.categoria_id,
      formaPagamento: despesa.forma_pagamento,
      valor: String(despesa.valor).replace('.', ','),
      dataCompra: despesa.data_compra,
      recorrente: despesa.recorrente == 1,
      parcelas: despesa.parcelas || 1,
      observacoes: despesa.observacoes || ''
    });
    setDespesaEditandoId(despesa.id);
    window.scrollTo(0, 0);
  };

  // Função para confirmação e exclusão
  const handleExcluirDespesa = (despesa) => {
    const { recorrente, grupo_recorrencia_id } = despesa;

    if (recorrente === 1 && grupo_recorrencia_id) {
      setModalState({
        isOpen: true,
        title: 'Excluir Despesa Recorrente',
        message: 'Como você deseja excluir esta despesa?',
        onConfirm: () => {
          prosseguirComExclusao(despesa, 'esta_e_futuras');
          setModalState({ isOpen: false });
        },
        confirmText: 'Esta e as Futuras',
        onCancel: () => {
          prosseguirComExclusao(despesa, 'apenas_esta');
          setModalState({ isOpen: false });
        },
        cancelText: 'Apenas Esta',
      });
    } else {
      if (window.confirm('Tem certeza que deseja excluir esta despesa?')) {
        prosseguirComExclusao(despesa, 'apenas_esta');
      }
    }
  };

  // Executa exclusão e atualiza lista
  const prosseguirComExclusao = async (despesa, escopo) => {
    try {
      const res = await fetch(`${API_BASE_URL}/despesas/excluir_despesas.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: despesa.id,
          escopo_exclusao: escopo,
          data_compra: despesa.data_compra
        })
      });
      
      const data = await res.json();

      if (data.sucesso) {
        alert(data.mensagem || 'Despesa(s) excluída(s) com sucesso!');
        carregarUltimasDespesas(); 
      } else {
        alert(data.erro || 'Erro ao excluir a despesa.');
      }
    } catch (error) {
      console.error('Erro ao excluir despesa:', error);
      alert('Erro de conexão ao excluir.');
    }
  };

  return (
    <div className="page-container">
      <ModalConfirmacao
        isOpen={modalState.isOpen}
        onClose={() => setModalState({ isOpen: false })}
        onConfirm={modalState.onConfirm}
        onCancel={modalState.onCancel}
        title={modalState.title}
        confirmText={modalState.confirmText}
        cancelText={modalState.cancelText}
      >
        {modalState.message}
      </ModalConfirmacao>

      <div className="form-card">
        <h2 className="form-title">{despesaEditandoId ? 'Editar Despesa' : 'Cadastrar Despesa'}</h2>
        <form onSubmit={salvarDespesa}>
          <div className="form-grid">
            <div>
              <label>Quem Comprou *</label>
              <select name="quemComprou" value={form.quemComprou} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {familiares.map(f => <option key={f.id} value={f.id}>{f.nome}</option>)}
              </select>

              <label>Fornecedor *</label>
              <select name="fornecedor" value={form.fornecedor} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {fornecedores.map(f => <option key={f.id} value={f.id}>{f.nome}</option>)}
              </select>

              <label>Categoria *</label>
              <select name="categoriaId" value={form.categoriaId} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {categorias.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
              </select>

              <label>Forma de Pagamento *</label>
              <select name="formaPagamento" value={form.formaPagamento} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                <option value="DINHEIRO">DINHEIRO</option>
                <option value="PIX">PIX</option>
                <option value="CARTÃO DE CRÉDITO">CARTÃO DE CRÉDITO</option>
                <option value="CARTÃO DE DÉBITO">CARTÃO DE DÉBITO</option>
                <option value="BOLETO">BOLETO</option>
              </select>
            </div>

            <div>
              <label>Valor (R$) *</label>
              <input type="text" name="valor" value={form.valor} onChange={handleChange} className="form-control" placeholder="0,00" required />

              <label>Data da Compra *</label>
              <input type="date" name="dataCompra" value={form.dataCompra} onChange={handleChange} className="form-control" required />

              <div className="checkbox-container mt-2 mb-2">
                <label>
                  <input type="checkbox" name="recorrente" checked={form.recorrente} onChange={handleChange} />
                  {' '}Conta Recorrente?
                </label>
              </div>

              {form.recorrente && (
                <div>
                  <label>Repetir por (meses) *</label>
                  <small style={{ display: 'block', marginBottom: '5px' }}>Use 0 para recorrência infinita.</small>
                  <input
                    type="number"
                    name="parcelas"
                    value={form.parcelas}
                    min="0"
                    onChange={handleChange}
                    className="form-control"
                    required
                  />
                </div>
              )}

              <label>Observações</label>
              <textarea name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" rows="4" />
            </div>
          </div>

          <div className="buttons-container-right">
            <button type="submit" className="btn btn-success">{despesaEditandoId ? 'Atualizar' : 'Salvar'}</button>
            <button type="button" className="btn btn-secondary" onClick={resetForm}>
              {despesaEditandoId ? 'Cancelar' : 'Limpar'}
            </button>
          </div>
        </form>
      </div>

      {/* SEÇÃO DA TABELA E FILTROS - CÓDIGO ATUALIZADO */}
      <div className="ultimas-despesas">
        <h3>Últimas Despesas</h3>

        <div className="filtros-receitas"> {/* Classe reutilizada de receitas.css */}
          <div className="filtro-controles">
            <div className="campos-data">
              <label>
                Data Início:
                <input
                  type="date"
                  value={filtroDataInicio}
                  onChange={(e) => setFiltroDataInicio(e.target.value)}
                />
              </label>
              <label>
                Data Fim:
                <input
                  type="date"
                  value={filtroDataFim}
                  onChange={(e) => setFiltroDataFim(e.target.value)}
                />
              </label>
            </div>
            <button className="btn btn-primary" onClick={carregarUltimasDespesas}>
              Filtrar
            </button>
          </div>
        </div>

        {ultimasDespesas.length === 0 ? (
          <p>Nenhuma despesa cadastrada.</p>
        ) : (
          <div className="tabela-despesas-container">
            <table className="tabela-despesas">
              <thead>
                <tr>
                  <th>Quem Comprou</th>
                  <th>Categoria</th>
                  <th>Valor</th>
                  <th>Data</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                {ultimasDespesas.map((d) => (
                  <tr key={d.id}>
                    <td>{d.quem_comprou_nome}</td>
                    <td>{d.categoria_nome}</td>
                    <td>R$ {parseFloat(d.valor).toFixed(2).replace('.', ',')}</td>
                    <td>{new Date(d.data_compra).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                    <td>
                      <div className="table-buttons">
                        <button type="button" onClick={() => editarDespesa(d)} className="btn-icon btn-edit" title="Editar">
                          <i className="fas fa-pen"></i>
                        </button>
                        <button type="button" onClick={() => handleExcluirDespesa(d)} className="btn-icon btn-trash" title="Excluir">
                          <i className="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

export default Despesas;
