import React, { useState, useEffect } from 'react';
import './receitas.css'; // Usará o novo receitas.css
import { useNavigate } from 'react-router-dom';
import { API_BASE_URL } from '../apiConfig';

function Receitas() {
  const navigate = useNavigate();
  const [usuario, setUsuario] = useState(null);

  // Estados para os selects
  const [familiares, setFamiliares] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [bancos, setBancos] = useState([]);
  
  // Estado para a lista da tabela
  const [ultimasReceitas, setUltimasReceitas] = useState([]);

  // Estado para controlar edição
  const [receitaEditandoId, setReceitaEditandoId] = useState(null);

  // Estado do formulário
  const [form, setForm] = useState({
    quemRecebeu: '',
    categoriaId: '',
    formaRecebimento: '',
    valor: '',
    dataRecebimento: new Date().toISOString().split('T')[0],
    recorrente: false,
    observacoes: ''
  });

  // Carrega o usuário do localStorage
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  // Carrega dados dos selects e a lista de receitas
  useEffect(() => {
    if (!usuario) return;

    const carregarDadosIniciais = async () => {
      try {
        const [resFamiliares, resCategorias, resBancos] = await Promise.all([
          fetch(`${API_BASE_URL}/familiares/familiares.php?usuario_id=${usuario.id}`),
          fetch(`${API_BASE_URL}/categorias.php?tipo=receita`), // Assumindo que você filtra categorias de receita
          fetch(`${API_BASE_URL}/bancos.php?usuario_id=${usuario.id}`)
        ]);

        const [dadosFamiliares, dadosCategorias, dadosBancos] = await Promise.all([
          resFamiliares.json(),
          resCategorias.json(),
          resBancos.json()
        ]);

        setFamiliares(dadosFamiliares);
        setCategorias(dadosCategorias);
        setBancos(dadosBancos);
      } catch (error) {
        console.error('Erro ao carregar dados dos selects:', error);
      }
    };

    carregarUltimasReceitas();
    carregarDadosIniciais();
  }, [usuario]);

  const carregarUltimasReceitas = async () => {
    if (!usuario) return;
    try {
      const res = await fetch(`${API_BASE_URL}/receitas/listar_receitas.php?usuario_id=${usuario.id}`);
      const data = await res.json();
      setUltimasReceitas(Array.isArray(data) ? data.slice(0, 5) : []);
    } catch (err) {
      console.error('Erro ao carregar últimas receitas:', err);
    }
  };

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

    setForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const resetForm = () => {
    setForm({
      quemRecebeu: '',
      categoriaId: '',
      formaRecebimento: '',
      valor: '',
      dataRecebimento: new Date().toISOString().split('T')[0],
      recorrente: false,
      observacoes: ''
    });
    setReceitaEditandoId(null);
  };

  const salvarReceita = async (e) => {
    e.preventDefault();

    const valorNumerico = parseFloat(form.valor.replace(',', '.'));
    if (isNaN(valorNumerico) || valorNumerico <= 0) {
      alert('O valor da receita é inválido.');
      return;
    }

    const payload = {
      id: receitaEditandoId,
      usuario_id: usuario.id,
      quem_recebeu: form.quemRecebeu,
      categoria_id: parseInt(form.categoriaId),
      forma_recebimento: form.formaRecebimento,
      valor: valorNumerico,
      data_recebimento: form.dataRecebimento,
      recorrente: form.recorrente ? 1 : 0,
      observacoes: form.observacoes
    };

    try {
      const res = await fetch(`${API_BASE_URL}/receitas/salvar_receitas.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.sucesso) {
        alert(receitaEditandoId ? 'Receita atualizada com sucesso!' : 'Receita cadastrada com sucesso!');
        resetForm();
        carregarUltimasReceitas(); // Recarrega a lista
      } else {
        alert(data.erro || 'Erro ao salvar a receita.');
      }
    } catch (err) {
      console.error('Erro ao salvar receita:', err);
      alert('Erro de comunicação ao salvar a receita.');
    }
  };

  const editarReceita = (receita) => {
    setForm({
      quemRecebeu: receita.quem_recebeu_id,
      categoriaId: receita.categoria_id,
      formaRecebimento: receita.forma_recebimento_id,
      valor: String(receita.valor).replace('.', ','),
      dataRecebimento: receita.data_recebimento,
      recorrente: receita.recorrente === 1,
      observacoes: receita.observacoes || ''
    });
    setReceitaEditandoId(receita.id);
    window.scrollTo(0, 0); // Rola a página para o topo para ver o formulário
  };

  const excluirReceita = async (id) => {
    if (!window.confirm('Tem certeza que deseja excluir esta receita?')) return;

    try {
      const res = await fetch(`${API_BASE_URL}/receitas/excluir_receitas.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
      });
      const data = await res.json();

      if (data.sucesso) {
        alert('Receita excluída com sucesso!');
        setUltimasReceitas(prev => prev.filter(r => r.id !== id));
      } else {
        alert(data.erro || 'Erro ao excluir a receita.');
      }
    } catch (error) {
      console.error('Erro ao excluir receita:', error);
      alert('Erro de conexão ao excluir.');
    }
  };

  return (
    <div className="page-container">
      <div className="form-card">
        <h2 className="form-title">{receitaEditandoId ? 'Editar Receita' : 'Cadastrar Receita'}</h2>
        <form onSubmit={salvarReceita}>
          <div className="form-grid">
            {/* Coluna da Esquerda */}
            <div>
              <label>Quem Recebeu *</label>
              <select name="quemRecebeu" value={form.quemRecebeu} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {familiares.map(f => <option key={f.id} value={f.id}>{f.nome}</option>)}
              </select>

              <label>Categoria *</label>
              <select name="categoriaId" value={form.categoriaId} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {categorias.map(c => <option key={c.id} value={c.id}>{c.nome}</option>)}
              </select>

              <label>Forma de Recebimento *</label>
              <select name="formaRecebimento" value={form.formaRecebimento} onChange={handleChange} className="form-control" required>
                <option value="">Selecione...</option>
                {bancos.map(b => <option key={b.id} value={b.id}>{b.nome}</option>)}
              </select>
            </div>

            {/* Coluna da Direita */}
            <div>
              <label>Valor (R$) *</label>
              <input type="text" name="valor" value={form.valor} onChange={handleChange} className="form-control" placeholder="0,00" required />

              <label>Data do Recebimento *</label>
              <input type="date" name="dataRecebimento" value={form.dataRecebimento} onChange={handleChange} className="form-control" required />

              <div className="checkbox-container mt-2 mb-2">
                <label>
                  <input type="checkbox" name="recorrente" checked={form.recorrente} onChange={handleChange} />
                  {' '}Receita Recorrente?
                </label>
              </div>

              <label>Observações</label>
              <textarea name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" rows="4" placeholder="Ex: adiantamento de salário..." />
            </div>
          </div>

          <div className="buttons-container-right">
            <button type="submit" className="btn btn-success">{receitaEditandoId ? 'Atualizar' : 'Salvar'}</button>
            <button type="button" className="btn btn-secondary" onClick={resetForm}>
              {receitaEditandoId ? 'Cancelar' : 'Limpar'}
            </button>
          </div>
        </form>
      </div>

      <div className="ultimas-despesas"> {/* Reutilizando a classe para manter o estilo */}
        <h3>Últimas Receitas</h3>
        {ultimasReceitas.length === 0 ? (
          <p>Nenhuma receita cadastrada.</p>
        ) : (
          <div className="tabela-despesas-container">
            <table className="tabela-despesas">
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
                {ultimasReceitas.map((r) => (
                  <tr key={r.id}>
                    <td>{r.quem_recebeu_nome}</td>
                    <td>{r.categoria_nome}</td>
                    <td>R$ {parseFloat(r.valor).toFixed(2).replace('.', ',')}</td>
                    <td>{new Date(r.data_recebimento).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                    <td>
                      <div className="table-buttons">
                        <button type="button" onClick={() => editarReceita(r)} title="Editar" className="btn-icon btn-edit">
                          <i className="fas fa-pen"></i>
                        </button>
                        <button type="button" onClick={() => excluirReceita(r.id)} title="Excluir" className="btn-icon btn-trash">
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

export default Receitas;
