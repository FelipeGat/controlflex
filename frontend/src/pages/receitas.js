import React, { useState, useEffect } from 'react';
import './receitas.css';
import { useNavigate } from 'react-router-dom';
import { API_BASE_URL } from '../apiConfig';
import ModalConfirmacao from './ModalConfirmacao'; // Importa o novo componente
import './ModalConfirmacao.css'; // Importa o CSS do modal

function Receitas() {
  const navigate = useNavigate();
  const [usuario, setUsuario] = useState(null);

  const [familiares, setFamiliares] = useState([]);
  const [categorias, setCategorias] = useState([]);
  const [bancos, setBancos] = useState([]);
  const [ultimasReceitas, setUltimasReceitas] = useState([]);
  const [receitaEditandoId, setReceitaEditandoId] = useState(null);

  const [filtroDataInicio, setFiltroDataInicio] = useState('');
  const [filtroDataFim, setFiltroDataFim] = useState('');

  // ========= INÍCIO DAS MUDANÇAS PARA O MODAL =========
  const [modalState, setModalState] = useState({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: () => {},
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
  });
  // ========= FIM DAS MUDANÇAS PARA O MODAL =========

  const [form, setForm] = useState({
    quemRecebeu: '',
    categoriaId: '',
    formaRecebimento: '',
    valor: '',
    dataRecebimento: new Date().toISOString().split('T')[0],
    recorrente: false,
    parcelas: 1,
    observacoes: ''
  });

  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  useEffect(() => {
    if (!usuario) return;

    const carregarDadosIniciais = async () => {
      try {
        const [resFamiliares, resCategorias, resBancos] = await Promise.all([
          fetch(`${API_BASE_URL}/familiares/familiares.php?usuario_id=${usuario.id}`),
          fetch(`${API_BASE_URL}/categorias/categorias.php?tipo=receita`),
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
      let url = `${API_BASE_URL}/receitas/listar_receitas.php?usuario_id=${usuario.id}`;

      if (filtroDataInicio && filtroDataFim) {
        url += `&inicio=${filtroDataInicio}&fim=${filtroDataFim}`;
      }

      const res = await fetch(url);
      const data = await res.json();
      setUltimasReceitas(Array.isArray(data) ? data.slice(0, 50) : []);
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

  const resetForm = () => {
    setForm({
      quemRecebeu: '',
      categoriaId: '',
      formaRecebimento: '',
      valor: '',
      dataRecebimento: new Date().toISOString().split('T')[0],
      recorrente: false,
      parcelas: 1,
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
      parcelas: form.parcelas,
      observacoes: form.observacoes
    };

    try {
      const res = await fetch(`${API_BASE_URL}/receitas/salvar_receitas.php`, {
        method: receitaEditandoId ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.sucesso) {
        alert(data.mensagem);
        resetForm();
        carregarUltimasReceitas();
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
      recorrente: receita.recorrente == 1,
      parcelas: receita.parcelas,
      observacoes: receita.observacoes || ''
    });
    setReceitaEditandoId(receita.id);
    window.scrollTo(0, 0);
  };

  // ========= FUNÇÃO DE EXCLUSÃO TOTALMENTE REFEITA =========
  const handleExcluirReceita = (receita) => {
    const { recorrente, grupo_recorrencia_id } = receita;

    // Se for uma receita recorrente, abre o modal com as opções
    if (recorrente === 1 && grupo_recorrencia_id) {
      setModalState({
        isOpen: true,
        title: 'Excluir Receita Recorrente',
        message: 'Como você deseja excluir esta parcela?',
        // Ação do primeiro botão
        onConfirm: () => {
          prosseguirComExclusao(receita, 'esta_e_futuras');
          setModalState({ isOpen: false }); // Fecha o modal
        },
        confirmText: 'Esta e as Futuras',
        // Ação do segundo botão (que aqui é o "cancelar" do componente)
        onClose: () => {
          prosseguirComExclusao(receita, 'apenas_esta');
          setModalState({ isOpen: false }); // Fecha o modal
        },
        cancelText: 'Apenas Esta',
      });
    } else {
      // Para receitas simples, usa o confirm padrão
      if (window.confirm('Tem certeza que deseja excluir esta receita?')) {
        prosseguirComExclusao(receita, 'apenas_esta');
      }
    }
  };

  const prosseguirComExclusao = async (receita, escopo) => {
    try {
      const res = await fetch(`${API_BASE_URL}/receitas/excluir_receitas.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: receita.id,
          escopo_exclusao: escopo,
          data_recebimento: receita.data_recebimento
        })
      });
      
      const data = await res.json();

      if (data.sucesso) {
        alert(data.mensagem || 'Receita(s) excluída(s) com sucesso!');
        carregarUltimasReceitas(); 
      } else {
        alert(data.erro || 'Erro ao excluir a receita.');
      }
    } catch (error) {
      console.error('Erro ao excluir receita:', error);
      alert('Erro de conexão ao excluir.');
    }
  };
  // ========= FIM DA REESTRUTURAÇÃO DA EXCLUSÃO =========

  return (
    <div className="page-container">
      {/* Renderiza o Modal aqui */}
      <ModalConfirmacao
        isOpen={modalState.isOpen}
        onClose={() => setModalState({ isOpen: false })} // Ação de fechar (clicar fora ou no botão de cancelar)
        onConfirm={modalState.onConfirm}
        title={modalState.title}
        confirmText={modalState.confirmText}
        cancelText={modalState.cancelText}
      >
        {modalState.message}
      </ModalConfirmacao>

      <div className="form-card">
        <h2 className="form-title">{receitaEditandoId ? 'Editar Receita' : 'Cadastrar Receita'}</h2>
        <form onSubmit={salvarReceita}>
          <div className="form-grid">
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
            <button type="submit" className="btn btn-success">{receitaEditandoId ? 'Atualizar' : 'Salvar'}</button>
            <button type="button" className="btn btn-secondary" onClick={resetForm}>
              {receitaEditandoId ? 'Cancelar' : 'Limpar'}
            </button>
          </div>
        </form>
      </div>

      <div className="ultimas-despesas">
        <h3>Últimas Receitas</h3>

        <div className="filtros-receitas">
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
            <button className="btn btn-primary" onClick={carregarUltimasReceitas}>
              Filtrar
            </button>
          </div>
        </div>

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
                        <button type="button" onClick={() => editarReceita(r)} className="btn-icon btn-edit" title="Editar">
                          <i className="fas fa-pen"></i>
                        </button>
                        {/* A chamada agora é para a nova função handleExcluirReceita */}
                        <button type="button" onClick={() => handleExcluirReceita(r)} className="btn-icon btn-trash" title="Excluir">
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
