import React, { useState, useEffect } from 'react';
import './fornecedores.css';
import { useNavigate } from 'react-router-dom';
import { API_BASE_URL }from '../apiConfig';

function Fornecedores() {
  const [usuario, setUsuario] = useState(null);
  const [fornecedores, setFornecedores] = useState([]);
  const [editando, setEditando] = useState(null);
  const [paginaAtual, setPaginaAtual] = useState(1);
  const [form, setForm] = useState({ nome: '', contato: '', cnpj: '', telefone: '', observacoes: '' });

  const navigate = useNavigate();
  const itensPorPagina = 5;

  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    console.log('Usuário carregado do localStorage:', user);

    if (!user || !user.id) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  useEffect(() => {
    if (!usuario?.id) return;

    const carregarFornecedores = async () => {
      try {
        const res = await fetch(`${API_BASE_URL}/fornecedores.php?usuario_id=${usuario.id}`);
        const data = await res.json();

        if (data.erro) {
          console.error('Erro retornado pela API:', data.erro);
          return;
        }

        setFornecedores(data);
      } catch (err) {
        console.error('Erro ao carregar fornecedores:', err);
      }
    };

    carregarFornecedores();
  }, [usuario]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const salvarFornecedor = async (e) => {
    e.preventDefault();

    const metodo = editando ? 'PUT' : 'POST';
    const payload = { ...form, usuario_id: usuario.id };
    if (editando) payload.id = editando;

    try {
      const res = await fetch(`${API_BASE_URL}/fornecedores.php`, {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.sucesso) {
        if (editando) {
          setFornecedores(fornecedores.map(f => (f.id === editando ? { ...form, id: editando } : f)));
          setEditando(null);
        } else {
          setFornecedores([...fornecedores, { id: data.id, ...form }]);
        }

        setForm({ nome: '', contato: '', cnpj: '', telefone: '', observacoes: '' });
      } else {
        alert(data.erro || 'Erro ao salvar fornecedor.');
      }
    } catch (error) {
      console.error('Erro no fetch:', error);
      alert('Erro ao se comunicar com o servidor.');
    }
  };

  const editarFornecedor = (fornecedor) => {
    setEditando(fornecedor.id);
    setForm({ ...fornecedor });
  };

  const excluirFornecedor = async (id) => {
    if (!window.confirm('Deseja realmente excluir este fornecedor?')) return;

    try {
      const res = await fetch(`${API_BASE_URL}/fornecedores.php?id=${id}`, {
        method: 'DELETE'
      });

      const data = await res.json();

      if (data.sucesso) {
        setFornecedores(fornecedores.filter(f => f.id !== id));
      } else {
        alert(data.erro || 'Erro ao excluir fornecedor.');
      }
    } catch (err) {
      console.error('Erro ao excluir fornecedor:', err);
    }
  };

  const fornecedoresPaginados = fornecedores.slice(
    (paginaAtual - 1) * itensPorPagina,
    paginaAtual * itensPorPagina
  );

  const totalPaginas = Math.ceil(fornecedores.length / itensPorPagina);

  return (
    <div className="page-container">
      <div className="form-card">
        <h2 className="form-title">{editando ? 'Editar Fornecedor' : 'Cadastro de Fornecedores'}</h2>
        <form onSubmit={salvarFornecedor}>
          <label>Nome do Fornecedor *</label>
          <input type="text" name="nome" value={form.nome} onChange={handleChange} className="form-control" required />

          <label>Contato</label>
          <input type="text" name="contato" value={form.contato} onChange={handleChange} className="form-control" />

          <label>CNPJ</label>
          <input type="text" name="cnpj" value={form.cnpj} onChange={handleChange} className="form-control" />

          <label>Telefone</label>
          <input type="text" name="telefone" value={form.telefone} onChange={handleChange} className="form-control" />

          <label>Observações</label>
          <textarea name="observacoes" value={form.observacoes} onChange={handleChange} className="form-control" />

          <div className="buttons-container">
            <button type="submit" className="btn-success">{editando ? 'Atualizar' : 'Salvar'}</button>
            <button type="button" className="btn-secondary" onClick={() => {
              setEditando(null);
              setForm({ nome: '', contato: '', cnpj: '', telefone: '', observacoes: '' });
            }}>Cancelar</button>
          </div>
        </form>
      </div>

      {fornecedores.length > 0 && (
        <div className="table-container">
          <h3>Fornecedores Cadastrados</h3>
          <table className="fornecedores-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Contato</th>
                <th>CNPJ</th>
                <th>Telefone</th>
                <th>Observações</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {fornecedoresPaginados.map(f => (
                <tr key={f.id}>
                  <td>{f.nome}</td>
                  <td>{f.contato || '-'}</td>
                  <td>{f.cnpj || '-'}</td>
                  <td>{f.telefone || '-'}</td>
                  <td>{f.observacoes || '-'}</td>
                  <td>
                    <div className="table-buttons">
                      <button type="button" onClick={() => editarFornecedor(f)} title="Editar" className="btn-icon btn-edit">
                        <i className="fas fa-pen"></i>
                      </button>
                      <button type="button" onClick={() => excluirFornecedor(f.id)} title="Excluir" className="btn-icon btn-trash">
                        <i className="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {totalPaginas > 1 && (
            <div className="pagination">
              {Array.from({ length: totalPaginas }, (_, i) => (
                <button key={i + 1} className={paginaAtual === i + 1 ? 'active' : ''} onClick={() => setPaginaAtual(i + 1)}>
                  {i + 1}
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default Fornecedores;
