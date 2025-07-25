import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import './usuarios.css';
// 1. Importa as variáveis de configuração
import { API_BASE_URL, UPLOADS_BASE_URL } from '../apiConfig';

function Usuarios( ) {
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

  const navigate = useNavigate();

  useEffect(() => {
    // 2. Corrige o fetch inicial
    fetch(`${API_BASE_URL}/usuarios.php`)
      .then(res => res.json())
      .then(setUsuarios);
  }, []);

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

  const salvarUsuario = async (e) => {
    e.preventDefault();

    if (form.senha !== form.confirmarSenha) {
      alert('As senhas não coincidem.');
      return;
    }

    const data = new FormData();
    data.append('nome', form.nome);
    data.append('email', form.email);
    data.append('senha', form.senha);
    data.append('perfil', form.nivel);
    data.append('status', form.status);
    if (foto) data.append('foto', foto);
    if (editando) data.append('id', editando);

    try {
      // 3. Corrige o fetch de salvar/atualizar
      const res = await fetch(`${API_BASE_URL}/usuarios.php`, {
        method: 'POST',
        body: data
      });

      const json = await res.json();

      if (json.sucesso) {
        alert(editando ? 'Usuário atualizado com sucesso!' : 'Usuário cadastrado com sucesso!');
        window.location.reload();
      } else {
        alert('Erro ao salvar usuário.');
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
      senha: '',
      confirmarSenha: '',
      nivel: usuario.perfil,
      status: usuario.status
    });
    // 4. Corrige a URL da imagem de preview
    if (usuario.foto) setFotoPreview(`${UPLOADS_BASE_URL}/${usuario.foto}`);
    else setFotoPreview(null);
    setFoto(null);
  };

  const excluir = async (id) => {
    if (!window.confirm('Deseja excluir este usuário?')) return;

    try {
      // 5. Corrige o fetch de exclusão
      await fetch(`${API_BASE_URL}/usuarios.php?id=${id}`, {
        method: 'DELETE'
      });
      window.location.reload();
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
          <input name="email" value={form.email} onChange={handleChange} className="form-control" required />

          <label>Senha *</label>
          <input type="password" name="senha" value={form.senha} onChange={handleChange} className="form-control" required />

          <label>Confirmar Senha *</label>
          <input type="password" name="confirmarSenha" value={form.confirmarSenha} onChange={handleChange} className="form-control" required />

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
            <button
              type="button"
              className="btn-secondary"
              onClick={() => {
                setEditando(null);
                setForm({ nome: '', email: '', senha: '', confirmarSenha: '', nivel: 'Padrão', status: 'Ativo' });
                setFoto(null);
                setFotoPreview(null);
              }}
            >
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
                    {/* 6. Corrige a URL da imagem na tabela */}
                    {u.foto
                      ? <img src={`${UPLOADS_BASE_URL}/${u.foto}`} alt="avatar" className="foto-thumb" />
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
