import React, { useState, useEffect } from 'react';
// Removi a importação do useNavigate, pois não estava sendo usada.
import './usuarios.css';
import { API_BASE_URL } from '../apiConfig'; // UPLOADS_BASE_URL será derivado daqui

// É uma prática melhor derivar a URL de uploads da URL da API
// para evitar configurar dois caminhos diferentes.
const UPLOADS_BASE_URL = `${API_BASE_URL.replace('/api', '')}/uploads`;

function Usuarios() {
  const [usuarios, setUsuarios] = useState([]);
  const [editando, setEditando] = useState(null);
  const [fotoPreview, setFotoPreview] = useState(null);
  const [foto, setFoto] = useState(null);
  const [form, setForm] = useState({
    nome: '',
    email: '',
    senha: '',
    confirmarSenha: '',
    nivel: 'Padrão',
    status: 'Ativo'
  });

  // Removi a variável navigate, pois não estava em uso.

  useEffect(() => {
    carregarUsuarios();
  }, []);

  const carregarUsuarios = () => {
    fetch(`${API_BASE_URL}/usuarios.php`)
      .then(res => {
        if (!res.ok) {
          throw new Error('Falha na resposta da rede');
        }
        return res.json();
      })
      .then(setUsuarios)
      .catch(err => console.error('Erro ao carregar usuários:', err));
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setFoto(file);
      setFotoPreview(URL.createObjectURL(file));
    }
  };

  const resetarFormulario = () => {
    setEditando(null);
    setForm({ nome: '', email: '', senha: '', confirmarSenha: '', nivel: 'Padrão', status: 'Ativo' });
    setFoto(null);
    setFotoPreview(null);
    // Limpa o campo de input de arquivo
    document.querySelector('input[type="file"]').value = '';
  };

  const salvarUsuario = async (e) => {
    e.preventDefault();

    if (form.senha && form.senha !== form.confirmarSenha) {
      alert('As senhas não coincidem.');
      return;
    }

    const data = new FormData();
    data.append('nome', form.nome);
    data.append('email', form.email);
    data.append('nivel', form.nivel);
    data.append('status', form.status);
    
    // Só envie a senha se ela foi preenchida
    if (form.senha) {
      data.append('senha', form.senha);
    }
    
    if (foto) {
      data.append('foto', foto);
    }
    
    if (editando) {
      data.append('id', editando);
    }

    try {
      const res = await fetch(`${API_BASE_URL}/usuarios.php`, {
        method: 'POST',
        body: data
      });

      const json = await res.json();

      if (json.sucesso) {
        alert(editando ? 'Usuário atualizado com sucesso!' : 'Usuário cadastrado com sucesso!');
        carregarUsuarios();
        resetarFormulario();
      } else {
        alert('Erro ao salvar usuário: ' + (json.erro || 'Erro desconhecido.'));
      }
    } catch (error) {
      alert('Erro na comunicação com o servidor.');
      console.error(error);
    }
  };

  const editar = (usuario) => {
    setEditando(usuario.id);
    setForm({
      nome: usuario.nome,
      email: usuario.email,
      senha: '', // Limpa o campo senha
      confirmarSenha: '', // Limpa o campo confirmar senha
      nivel: usuario.perfil,
      status: usuario.status
    });

    if (usuario.foto) {
      // Adiciona um timestamp para evitar problemas de cache do navegador
      setFotoPreview(`${UPLOADS_BASE_URL}/${usuario.foto}?t=${new Date().getTime()}`);
    } else {
      setFotoPreview(null);
    }
    
    setFoto(null);
    document.querySelector('input[type="file"]').value = '';
    window.scrollTo(0, 0); // Rola a página para o topo para ver o formulário
  };

  const excluir = async (id) => {
    if (!window.confirm('Deseja excluir este usuário?')) return;

    try {
      const res = await fetch(`${API_BASE_URL}/usuarios.php?id=${id}`, {
        method: 'DELETE'
      });
      const json = await res.json();
      if (json.sucesso) {
        carregarUsuarios();
      } else {
        alert('Erro ao excluir usuário: ' + (json.erro || 'Erro desconhecido.'));
      }
    } catch (error) {
      alert('Erro ao excluir usuário.');
      console.error(error);
    }
  };

  return (
    <div className="page-container">
      <div className="form-card">
        <h2 className="form-title">{editando ? 'Editar Usuário' : 'Cadastro de Usuários'}</h2>
        <form onSubmit={salvarUsuario}>
          <label>Nome *</label>
          <input name="nome" value={form.nome} onChange={handleChange} className="form-control" required />

          <label>Email *</label>
          <input type="email" name="email" value={form.email} onChange={handleChange} className="form-control" required />

          {/* A senha só é obrigatória se for um novo cadastro */}
          <label>Senha {editando ? '(Deixe em branco para não alterar)' : '*'}</label>
          <input type="password" name="senha" value={form.senha} onChange={handleChange} className="form-control" required={!editando} />

          <label>Confirmar Senha {editando ? '' : '*'}</label>
          <input type="password" name="confirmarSenha" value={form.confirmarSenha} onChange={handleChange} className="form-control" required={!editando && form.senha} />

          <label>Nível de Acesso</label>
          <select name="nivel" value={form.nivel} onChange={handleChange} className="form-control">
            <option value="Administrador">Administrador</option>
            <option value="Padrão">Padrão</option>
            <option value="Leitor">Leitor</option>
          </select>

          <label>Status</label>
          <select name="status" value={form.status} onChange={handleChange} className="form-control">
            <option value="Ativo">Ativo</option>
            <option value="Inativo">Inativo</option>
          </select>

          <label>Foto de Perfil</label>
          <input type="file" accept="image/*" onChange={handleFotoChange} className="form-control" />
          {fotoPreview && <img src={fotoPreview} alt="Prévia" className="preview-img" />}

          <div className="buttons-container">
            <button type="submit" className="btn-success">{editando ? 'Atualizar' : 'Salvar'}</button>
            <button type="button" className="btn-secondary" onClick={resetarFormulario}>
              Cancelar
            </button>
          </div>
        </form>
      </div>

      {usuarios.length > 0 && (
        <div className="table-container">
          <h3>Usuários Cadastrados</h3>
          <table className="usuarios-table">
            <thead>
              <tr>
                <th>Foto</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Nível</th>
                <th>Status</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              {usuarios.map(u => (
                <tr key={u.id}>
                  <td>
                    {u.foto
                      // Adiciona o timestamp aqui também para garantir a atualização
                      ? <img src={`${UPLOADS_BASE_URL}/${u.foto}?t=${new Date().getTime()}`} alt="avatar" className="foto-thumb" />
                      : '-'}
                  </td>
                  <td>{u.nome}</td>
                  <td>{u.email}</td>
                  <td>{u.perfil}</td>
                  <td>{u.status}</td>
                  <td>
                    <div className="table-buttons">
                      <button onClick={() => editar(u)} className="btn-icon btn-edit" title="Editar">
                        <i className="fas fa-pen"></i>
                      </button>
                      <button onClick={() => excluir(u.id)} className="btn-icon btn-trash" title="Excluir">
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

export default Usuarios;
