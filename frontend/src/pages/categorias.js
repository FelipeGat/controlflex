import React, { useEffect, useState } from 'react';
// 1. Importa a nossa configuração da API
import API_BASE_URL from '../apiConfig';

function Categorias() {
  const [categorias, setCategorias] = useState([]);
  const [form, setForm] = useState({ nome: '', tipo: 'receita', icone: '' });
  const [loading, setLoading] = useState(false);
  const [msg, setMsg] = useState(null);

  // Função para buscar categorias, para evitar repetição
  const fetchCategorias = () => {
    // 2. Corrige o fetch inicial
    fetch(`${API_BASE_URL}/categorias.php`)
      .then(res => res.json())
      .then(setCategorias)
      .catch(console.error);
  };

  // Carrega categorias do backend na montagem do componente
  useEffect(() => {
    fetchCategorias();
  }, []);

  function handleChange(e) {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  }

  function handleSubmit(e) {
    e.preventDefault();
    setLoading(true);
    setMsg(null);

    // 3. Corrige o fetch para salvar a categoria
    fetch(`${API_BASE_URL}/categorias.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })
      .then(res => res.json())
      .then(data => {
        if (data.sucesso) {
          setMsg({ type: 'success', text: data.msg });
          setForm({ nome: '', tipo: 'receita', icone: '' });
          // Atualiza a lista chamando a função que já faz isso
          fetchCategorias();
        } else {
          setMsg({ type: 'error', text: data.erro || 'Erro desconhecido' });
        }
      })
      .catch(() => setMsg({ type: 'error', text: 'Erro na comunicação com o servidor' }))
      .finally(() => setLoading(false));
  }

  // --- O seu JSX (return) permanece exatamente o mesmo ---
  return (
    <div className="container py-4">
      <h2 className="mb-4">Cadastro de Categorias</h2>

      {msg && (
        <div
          className={`alert ${msg.type === 'success' ? 'alert-success' : 'alert-danger'}`}
          role="alert"
        >
          {msg.text}
        </div>
      )}

      <form onSubmit={handleSubmit} className="mb-5">
        <div className="mb-3">
          <label htmlFor="nome" className="form-label">Nome da Categoria</label>
          <input
            type="text"
            id="nome"
            name="nome"
            value={form.nome}
            onChange={handleChange}
            className="form-control"
            required
            maxLength={50}
            placeholder="Ex: Alimentação"
          />
        </div>

        <div className="mb-3">
          <label htmlFor="tipo" className="form-label">Tipo</label>
          <select
            id="tipo"
            name="tipo"
            value={form.tipo}
            onChange={handleChange}
            className="form-select"
            required
          >
            <option value="receita">Receita</option>
            <option value="despesa">Despesa</option>
          </select>
        </div>

        <div className="mb-3">
          <label htmlFor="icone" className="form-label">Ícone (classe ou emoji)</label>
          <input
            type="text"
            id="icone"
            name="icone"
            value={form.icone}
            onChange={handleChange}
            className="form-control"
            placeholder="Ex: 🍔 ou fa-solid fa-utensils"
          />
          <small className="form-text text-muted">
            Você pode usar emojis ou classes de ícones (FontAwesome, etc).
          </small>
        </div>

        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Salvando...' : 'Salvar Categoria'}
        </button>
      </form>

      <h3>Lista de Categorias</h3>
      {categorias.length === 0 ? (
        <p>Nenhuma categoria cadastrada ainda.</p>
      ) : (
        <ul className="list-group">
          {categorias.map(cat => (
            <li
              key={cat.id}
              className={`list-group-item d-flex justify-content-between align-items-center ${
                cat.tipo === 'receita' ? 'list-group-item-success' : 'list-group-item-danger'
              }`}
            >
              <span>{cat.icone} {cat.nome}</span>
              <small className="badge bg-secondary text-capitalize">{cat.tipo}</small>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

export default Categorias;
