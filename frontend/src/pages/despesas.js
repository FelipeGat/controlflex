import React, { useState, useEffect } from 'react';
import './despesas.css';
import { useNavigate } from 'react-router-dom';
import { API_BASE_URL } from '../apiConfig';

function Despesas() {
  const navigate = useNavigate();

  const [familiares, setFamiliares] = useState([]);
  const [fornecedores, setFornecedores] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [ultimasDespesas, setUltimasDespesas] = useState([]);

  // Estado para controlar se estamos editando uma despesa
  const [despesaEditandoId, setDespesaEditandoId] = useState(null);

  const [form, setForm] = useState({
    quemComprou: '',
    fornecedor: '',
    categoria: '',
    formaPagamento: '',
    valor: '',  // valor como string sem prefixo para facilitar parseFloat
    dataCompra: new Date().toISOString().split('T')[0],
    recorrente: false,
    observacoes: ''
  });

  useEffect(() => {
    const usuario = JSON.parse(localStorage.getItem('usuarioLogado'));

    if (!usuario) {
      navigate('/');
      return;
    }

    const carregarDados = async () => {
      try {
        const [respFamiliares, respFornecedores, respCategorias] = await Promise.all([
          fetch(`${API_BASE_URL}/familiares/familiares.php?usuario_id=${usuario.id}`),
          fetch(`${API_BASE_URL}/fornecedores.php?usuario_id=${usuario.id}`),
          fetch(`${API_BASE_URL}/categorias.php`)
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
        console.error('Erro ao carregar dados:', error);
      }
    };

    const carregarUltimasDespesas = async () => {
      try {
        const res = await fetch(`${API_BASE_URL}/despesas/ultimas.php?usuario_id=${usuario.id}`);
        const data = await res.json();
        setUltimasDespesas(data);
      } catch (err) {
        console.error('Erro ao carregar últimas despesas:', err);
      }
    };

    carregarDados();
    carregarUltimasDespesas();
  }, [navigate]);

  // Atualiza valor no estado sem formatação (apenas números e vírgula)
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;

    if (name === 'valor') {
      // Permite números, vírgula e ponto (troque vírgula por ponto para parseFloat)
      // Remove tudo que não é número ou vírgula
      let cleaned = value.replace(/[^0-9,]/g, '');

      // Permite só uma vírgula
      const parts = cleaned.split(',');
      if(parts.length > 2) {
        cleaned = parts[0] + ',' + parts[1];
      }

      setForm(prev => ({ ...prev, valor: cleaned }));
      return;
    }

    setForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const salvarDespesa = async (e) => {
    e.preventDefault();

    const usuario = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!usuario) {
      alert('Usuário não autenticado.');
      return;
    }

    const valorNumerico = parseFloat(form.valor.replace(',', '.'));
    if (isNaN(valorNumerico)) {
      alert('Valor inválido');
      return;
    }

    const payload = {
      id: despesaEditandoId, // envia id para edição ou null para novo
      usuario_id: usuario.id,
      quem_comprou: form.quemComprou,
      onde_comprou: form.fornecedor,
      categoria_id: parseInt(form.categoria),
      forma_pagamento: form.formaPagamento,
      valor: valorNumerico,
      data_compra: form.dataCompra,
      recorrente: form.recorrente ? 1 : 0,
      recorrente_infinita: 0,
      parcelas: 1,
      observacoes: form.observacoes
    };

    try {
      const res = await fetch(`${API_BASE_URL}/despesas/salvar_despesas.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.sucesso) {
        alert(despesaEditandoId ? 'Despesa atualizada com sucesso!' : 'Despesa cadastrada com sucesso!');

        setForm({
          quemComprou: '',
          fornecedor: '',
          categoria: '',
          formaPagamento: '',
          valor: '',
          dataCompra: new Date().toISOString().split('T')[0],
          recorrente: false,
          observacoes: ''
        });
        setDespesaEditandoId(null);

        // Atualiza últimas despesas após salvar
        const resUltimas = await fetch(`${API_BASE_URL}/despesas/ultimas.php?usuario_id=${usuario.id}`);
        const ultimas = await resUltimas.json();
        setUltimasDespesas(ultimas);

      } else {
        alert('Erro ao salvar despesa!');
        console.error('Resposta da API:', data);
      }
    } catch (err) {
      console.error('Erro ao salvar despesa:', err);
      alert('Erro de conexão ao salvar despesa.');
    }
  };

  // Função para carregar despesa no formulário para edição
  const editarDespesa = async (id) => {
    try {
      // Use o endpoint despesa.php para carregar uma despesa única
      const res = await fetch(`${API_BASE_URL}/despesas/despesa.php?id=${id}`);
      const data = await res.json();

      if (data && data.id) {
        const despesa = data;

        setForm({
          quemComprou: despesa.quem_comprou,
          fornecedor: despesa.onde_comprou,
          categoria: despesa.categoria_id,
          formaPagamento: despesa.forma_pagamento,
          valor: despesa.valor.toString().replace('.', ','), // mantém vírgula decimal
          dataCompra: despesa.data_compra,
          recorrente: despesa.recorrente === 1,
          observacoes: despesa.observacoes || ''
        });

        setDespesaEditandoId(id);
      } else {
        alert('Despesa não encontrada');
      }
    } catch (error) {
      console.error('Erro ao carregar despesa:', error);
      alert('Erro ao carregar despesa para edição');
    }
  };

  const excluirDespesa = async (id) => {
    if (!window.confirm('Tem certeza que deseja excluir esta despesa?')) return;

    try {
      const res = await fetch(`${API_BASE_URL}/despesas/excluir.php`, {
        method: 'POST', // Backend aceita só POST para excluir
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }), // envia o id no corpo JSON
      });

      const data = await res.json();

      if (data.sucesso) {
        alert('Despesa excluída com sucesso!');
        setUltimasDespesas((prev) => prev.filter((d) => d.id !== id));
      } else {
        alert(data.erro || 'Erro ao excluir despesa.');
      }
    } catch (error) {
      console.error('Erro ao excluir despesa:', error);
      alert('Erro de conexão.');
    }
  };

  return (
    <div className="page-container">
      <div className="form-card">
        <h2 className="form-title">{despesaEditandoId ? 'Editar Despesa' : 'Cadastrar Despesa'}</h2>
        <form onSubmit={salvarDespesa}>
          <div className="form-grid">
            <div>
              <label>Quem Comprou *</label>
              <select name="quemComprou" value={form.quemComprou} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {familiares.map(f => (
                  <option key={f.id} value={f.id}>{f.nome}</option>
                ))}
              </select>

              <label>Fornecedor *</label>
              <select name="fornecedor" value={form.fornecedor} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {fornecedores.map(f => (
                  <option key={f.id} value={f.id}>{f.nome}</option>
                ))}
              </select>

              <label>Categoria *</label>
              <select name="categoria" value={form.categoria} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {categorias.map(c => (
                  <option key={c.id} value={c.id}>{c.nome}</option>
                ))}
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
              <input
                type="text"
                name="valor"
                value={form.valor}
                onChange={handleChange}
                className="form-control"
                placeholder="0,00"
                required
              />

              <label>Data da Compra *</label>
              <input
                type="date"
                name="dataCompra"
                value={form.dataCompra}
                onChange={handleChange}
                className="form-control"
                required
              />

              <div className="checkbox-container mt-2 mb-2">
                <label>
                  <input
                    type="checkbox"
                    name="recorrente"
                    checked={form.recorrente}
                    onChange={handleChange}
                  />{' '}
                  Conta Recorrente?
                </label>
              </div>

              <label>Observações</label>
              <textarea
                name="observacoes"
                value={form.observacoes}
                onChange={handleChange}
                className="form-control"
                rows="4"
                placeholder="Ex: pagamento em 2x no cartão..."
              />
            </div>
          </div>

          <div className="buttons-container-right">
            <button type="submit" className="btn btn-success">{despesaEditandoId ? 'Atualizar' : 'Salvar'}</button>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => {
                setForm({
                  quemComprou: '',
                  fornecedor: '',
                  categoria: '',
                  formaPagamento: '',
                  valor: '',
                  dataCompra: new Date().toISOString().split('T')[0],
                  recorrente: false,
                  observacoes: ''
                });
                setDespesaEditandoId(null);
              }}
            >
              Limpar
            </button>
          </div>
        </form>
      </div>

      <div className="ultimas-despesas">
        <h3>Últimas Despesas</h3>

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
                    <td>{d.quem_comprou_nome || d.quem_comprou}</td> {/* ideal ter nome no backend */}
                    <td>{d.categoria}</td>
                    <td>R$ {parseFloat(d.valor).toFixed(2).replace('.', ',')}</td>
                    <td>{new Date(d.data_compra).toLocaleDateString()}</td>
                    <td>
                      <div className="table-buttons">
                        <button
                          type="button"
                          onClick={() => editarDespesa(d.id)}
                          title="Editar"
                          className="btn-icon btn-edit"
                        >
                          <i className="fas fa-pen"></i>
                        </button>
                        <button
                          type="button"
                          onClick={() => excluirDespesa(d.id)}
                          title="Excluir"
                          className="btn-icon btn-trash"
                        >
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
