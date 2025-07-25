import React, { useState, useEffect } from 'react';
import './bancos.css';
import { useNavigate } from 'react-router-dom';

const API_BASE_URL = process.env.REACT_APP_API_BASE_URL;
const BANK_ICON_BASE_URL = process.env.REACT_APP_BANK_ICONS_BASE_URL;

function Bancos() {
  const [usuario, setUsuario] = useState(null);
  const [bancos, setBancos] = useState([]);
  const [editando, setEditando] = useState(null);
  const [form, setForm] = useState({
    nome: '',
    codigo_banco: '',
    agencia: '',
    conta: '',
    saldo: '',
    limite_cartao: '',
    cheque_especial: '',
    icone: ''
  });

  const navigate = useNavigate();

  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
      return;
    }
    setUsuario(user);
  }, [navigate]);

  useEffect(() => {
    if (!usuario || !usuario.id) return;

    fetch(`${API_BASE_URL}/bancos.php?usuario_id=${usuario.id}`)
      .then(res => res.json())
      .then(setBancos)
      .catch(err => console.error('Erro ao carregar bancos:', err));
  }, [usuario]);

  const handleChange = (e) => {
    const { name, value } = e.target;

    if (name === 'codigo_banco') {
      const codigoBanco = value.padStart(3, '0');
      const caminho = `${BANK_ICON_BASE_URL}/${codigoBanco}.png`;

      const img = new Image();
      img.onload = () => setForm(prev => ({ ...prev, icone: caminho }));
      img.onerror = () => setForm(prev => ({ ...prev, icone: `${BANK_ICON_BASE_URL}/default-bank.png` }));
      img.src = caminho;
    }

    setForm(prev => ({ ...prev, [name]: value }));
  };

  const salvarBanco = async (e) => {
    e.preventDefault();

    const metodo = editando ? 'PUT' : 'POST';
    const payload = { ...form, usuario_id: usuario.id };
    if (editando) payload.id = editando;

    const res = await fetch(`${API_BASE_URL}/bancos.php`, {
      method: metodo,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const data = await res.json();

    if (data.sucesso) {
      if (editando) {
        setBancos(bancos.map(b => (b.id === editando ? { ...form, id: editando } : b)));
        setEditando(null);
      } else {
        setBancos([...bancos, { id: data.id, ...form }]);
      }

      setForm({
        nome: '',
        codigo_banco: '',
        agencia: '',
        conta: '',
        saldo: '',
        limite_cartao: '',
        cheque_especial: '',
        icone: ''
      });
    } else {
      alert('Erro ao salvar banco.');
    }
  };

  const editarBanco = (banco) => {
    setEditando(banco.id);
    setForm({ ...banco });
  };

  const excluirBanco = async (id) => {
    if (!window.confirm('Deseja realmente excluir este banco?')) return;

    const res = await fetch(`${API_BASE_URL}/bancos.php?id=${id}`, {
      method: 'DELETE'
    });

    const data = await res.json();

    if (data.sucesso) {
      setBancos(bancos.filter(b => b.id !== id));
    } else {
      alert('Erro ao excluir banco.');
    }
  };

  return (
    <div className="page-container">
      <div className="form-card">
        <h2 className="form-title">{editando ? 'Editar Banco' : 'Cadastro de Bancos'}</h2>
        <form onSubmit={salvarBanco}>
          <label>Nome do Banco *</label>
          <input type="text" name="nome" value={form.nome} onChange={handleChange} className="form-control" required />

          <label>Código do Banco *</label>
          <input type="text" name="codigo_banco" value={form.codigo_banco} onChange={handleChange} className="form-control" required />

          <label>Agência *</label>
          <input type="text" name="agencia" value={form.agencia} onChange={handleChange} className="form-control" required />

          <label>Conta Corrente</label>
          <input type="text" name="conta" value={form.conta} onChange={handleChange} className="form-control" />

          <label>Saldo</label>
          <input type="number" step="0.01" name="saldo" value={form.saldo} onChange={handleChange} className="form-control" />

          <label>Limite Cartão de Crédito</label>
          <input type="number" step="0.01" name="limite_cartao" value={form.limite_cartao} onChange={handleChange} className="form-control" />

          <label>Limite Cheque Especial</label>
          <input type="number" step="0.01" name="cheque_especial" value={form.cheque_especial} onChange={handleChange} className="form-control" />

          <div className="buttons-container">
            <button type="submit" className="btn-success">{editando ? 'Atualizar' : 'Salvar'}</button>
            <button
              type="button"
              className="btn-secondary"
              onClick={() => {
                setEditando(null);
                setForm({
                  nome: '',
                  codigo_banco: '',
                  agencia: '',
                  conta: '',
                  saldo: '',
                  limite_cartao: '',
                  cheque_especial: '',
                  icone: ''
                });
              }}
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>

      {bancos.length > 0 && (
        <div className="table-container">
          <h3>Bancos Cadastrados</h3>
          <table className="bancos-table">
            <thead>
              <tr>
                <th>Ícone</th>
                <th>Nome</th>
                <th>Cód. Banco</th>
                <th>Agência</th>
                <th>Conta</th>
                <th>Saldo</th>
                <th>Cartão</th>
                <th>Cheque</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {bancos.map(b => (
                <tr key={b.id}>
                  <td>
                    <img
                      src={`${BANK_ICON_BASE_URL}/${b.codigo_banco.padStart(3, '0')}.png`}
                      alt={`ícone de ${b.nome}`}
                      className="icone-thumb"
                      onError={(e) => {
                        e.target.onerror = null;
                        e.target.src = `${BANK_ICON_BASE_URL}/default-bank.png`;
                      }}
                    />
                  </td>
                  <td>{b.nome}</td>
                  <td>{b.codigo_banco || '-'}</td>
                  <td>{b.agencia || '-'}</td>
                  <td>{b.conta || '-'}</td>
                  <td>R$ {parseFloat(b.saldo || 0).toFixed(2)}</td>
                  <td>R$ {parseFloat(b.limite_cartao || 0).toFixed(2)}</td>
                  <td>R$ {parseFloat(b.cheque_especial || 0).toFixed(2)}</td>
                  <td>
                    <div className="table-buttons">
                      <button type="button" onClick={() => editarBanco(b)} title="Editar" className="btn-icon btn-edit">
                        <i className="fas fa-pen"></i>
                      </button>
                      <button type="button" onClick={() => excluirBanco(b.id)} title="Excluir" className="btn-icon btn-trash">
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
  );
}

export default Bancos;
