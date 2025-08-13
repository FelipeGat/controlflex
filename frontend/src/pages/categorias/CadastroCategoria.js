import React, { useState, useEffect } from 'react';
import axios from 'axios';
import './CadastroCategoria.css';

// É uma boa prática mover a URL da API para um arquivo de configuração central
const API_BASE = 'http://localhost/ControleFlex/backend/api/categorias/';

// Dicionário que mapeia a chave de texto (do banco ) para o emoji e o label.
const iconesMap = {
  money:     { emoji: '💰', label: 'Dinheiro' },
  food:      { emoji: '🍽️', label: 'Alimentação' },
  car:       { emoji: '🚗', label: 'Transporte' },
  home:      { emoji: '🏠', label: 'Moradia' },
  shop:      { emoji: '🛒', label: 'Compras' },
  education: { emoji: '🎓', label: 'Educação' },
  bills:     { emoji: '💡', label: 'Contas' },
  gifts:     { emoji: '🎁', label: 'Presentes' },
  health:    { emoji: '❤️', label: 'Saúde' },
  travel:    { emoji: '✈️', label: 'Viagem' },
};

// Função auxiliar para obter o emoji a partir da chave. Retorna a própria chave se não encontrar.
const getEmoji = (key) => (iconesMap[key] ? iconesMap[key].emoji : key);

const CadastroCategoria = () => {
  const [nome, setNome] = useState('');
  const [tipo, setTipo] = useState('');
  const [icone, setIcone] = useState(''); // O estado 'icone' vai guardar a chave, ex: "money"
  const [categorias, setCategorias] = useState([]);
  const [busca, setBusca] = useState('');
  const [paginaAtual, setPaginaAtual] = useState(1);
  const itensPorPagina = 5;

  const [modoEdicao, setModoEdicao] = useState(false);
  const [categoriaEditando, setCategoriaEditando] = useState(null);

  useEffect(() => {
    fetchCategorias();
  }, []);

  const fetchCategorias = async () => {
    try {
      const response = await axios.get(API_BASE + 'listar.php');
      setCategorias(response.data);
    } catch (error) {
      console.error('Erro ao buscar categorias', error);
    }
  };

  const salvarCategoria = async (e) => {
    e.preventDefault();
    if (!nome || !tipo || !icone) {
      alert('Preencha todos os campos!');
      return;
    }

    try {
      const payload = { nome, tipo, icone }; // 'icone' aqui é a chave de texto
      let url = API_BASE + 'salvar.php';

      if (modoEdicao && categoriaEditando) {
        payload.id = categoriaEditando.id;
        url = API_BASE + 'editar.php';
      }
      
      await axios.post(url, payload);

      fetchCategorias();
      limparCampos();
    } catch (error) {
      console.error('Erro ao salvar categoria', error);
    }
  };

  const excluirCategoria = async (id) => {
    if (window.confirm('Tem certeza que deseja excluir esta categoria?')) {
      try {
        await axios.post(API_BASE + 'excluir.php', { id });
        fetchCategorias();
      } catch (error) {
        console.error('Erro ao excluir categoria', error);
      }
    }
  };

  const editarCategoria = (categoria) => {
    setModoEdicao(true);
    setCategoriaEditando(categoria);
    setNome(categoria.nome);
    setTipo(categoria.tipo);
    setIcone(categoria.icone); // Seta a chave, ex: "car"
    window.scrollTo(0, 0);
  };

  const limparCampos = () => {
    setNome('');
    setTipo('');
    setIcone('');
    setModoEdicao(false);
    setCategoriaEditando(null);
  };

  const categoriasFiltradas = categorias.filter((cat) =>
    cat.nome.toLowerCase().includes(busca.toLowerCase())
  );

  const totalPaginas = Math.ceil(categoriasFiltradas.length / itensPorPagina);
  const indiceInicial = (paginaAtual - 1) * itensPorPagina;
  const categoriasPaginadas = categoriasFiltradas.slice(indiceInicial, indiceInicial + itensPorPagina);

  const mudarPagina = (novaPagina) => {
    if (novaPagina >= 1 && novaPagina <= totalPaginas) {
      setPaginaAtual(novaPagina);
    }
  };

  return (
      <div className="page-container">
        <div className="form-card">
          <h2 className="form-title">{modoEdicao ? 'Editar Categoria' : 'Cadastrar Categoria'}</h2>
          
          <form onSubmit={salvarCategoria}>
            <div className="form-fields">
              <div>
                <label htmlFor="nome">Nome da Categoria *</label>
                <input id="nome" name="nome" className="form-control" value={nome} onChange={(e) => setNome(e.target.value)} required />
              </div>

              <div>
                <label htmlFor="tipo">Tipo *</label>
                <select id="tipo" name="tipo" className="form-control" value={tipo} onChange={(e) => setTipo(e.target.value)} required>
                  <option value="">Selecione...</option>
                  <option value="receita">Receita</option>
                  <option value="despesa">Despesa</option>
                </select>
              </div>

              <div>
                <label htmlFor="icone">Ícone *</label>
                <select id="icone" name="icone" className="form-control" value={icone} onChange={(e) => setIcone(e.target.value)} required>
                  <option value="">Selecione um ícone</option>
                  {Object.entries(iconesMap).map(([key, { emoji, label }]) => (
                    <option key={key} value={key}>
                      {emoji} {label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-buttons">
              <button type="submit" className="btn-save">
                {modoEdicao ? 'Atualizar' : 'Salvar'}
              </button>
              <button type="button" className="btn-cancel" onClick={limparCampos}>
                Cancelar
              </button>
            </div>
          </form>
        </div>

        <div className="table-container">
          <h3 className="form-title" style={{ marginBottom: '20px' }}>Categorias Cadastradas</h3>

          <input
            className="form-control"
            type="text"
            placeholder="Buscar por nome..."
            value={busca}
            onChange={(e) => setBusca(e.target.value)}
            style={{ marginBottom: '1.5rem' }}
          />

          <table className="data-table">
            <thead>
              <tr>
                <th>Ícone</th>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {categoriasPaginadas.map((cat) => (
                <tr key={cat.id}>
                  <td className="icone-cell">{getEmoji(cat.icone)}</td>
                  <td>{cat.nome}</td>
                  <td>{cat.tipo}</td>
                  <td className="actions-cell">
                    <div className="table-buttons">
                      <button className="btn-icon btn-edit" onClick={() => editarCategoria(cat)} title="Editar">
                        <i className="fas fa-pen"></i>
                      </button>
                      <button className="btn-icon btn-trash" onClick={() => excluirCategoria(cat.id)} title="Excluir">
                        <i className="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {totalPaginas > 1 && (
            <div className="pagination-buttons" style={{ marginTop: '20px', display: 'flex', justifyContent: 'center', gap: '10px' }}>
              <button onClick={() => mudarPagina(paginaAtual - 1)} disabled={paginaAtual === 1}>
                Anterior
              </button>
              <span style={{ padding: '0 10px', alignSelf: 'center' }}>
                Página {paginaAtual} de {totalPaginas}
              </span>
              <button onClick={() => mudarPagina(paginaAtual + 1)} disabled={paginaAtual === totalPaginas}>
                Próxima
              </button>
            </div>
          )}
        </div>
      </div>
  );
};

export default CadastroCategoria;
