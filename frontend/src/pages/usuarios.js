import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import './usuarios.css';
import { API_BASE_URL, UPLOADS_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

const UserForm = ({ onSave, onCancel, editingUser, initialFormState }) => {
  const [form, setForm] = useState(initialFormState);
  const [fotoPreview, setFotoPreview] = useState(null);
  const [fotoFile, setFotoFile] = useState(null);

  useEffect(() => {
    if (editingUser) {
      setForm({ ...editingUser, senha: '', confirmarSenha: '' });
      setFotoPreview(editingUser.foto ? `${UPLOADS_BASE_URL}/${editingUser.foto}` : null);
    } else {
      setForm(initialFormState);
      setFotoPreview(null);
    }
    setFotoFile(null);
    // Reset file input value to allow re-upload same file if needed
    const fileInput = document.getElementById('foto');
    if (fileInput) fileInput.value = '';
  }, [editingUser, initialFormState]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleFotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Optional: validate file type and size here before accepting
      if (!file.type.startsWith('image/')) {
        alert('Por favor, selecione um arquivo de imagem válido.');
        e.target.value = ''; // reset input
        return;
      }
      if (file.size > 2 * 1024 * 1024) { // 2MB limit
        alert('A imagem deve ter no máximo 2MB.');
        e.target.value = '';
        return;
      }
      setFotoFile(file);
      setFotoPreview(URL.createObjectURL(file));
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (form.senha && form.senha !== form.confirmarSenha) {
      alert('As senhas não coincidem.');
      return;
    }
    onSave(form, fotoFile);
  };

  return (
    <form onSubmit={handleSubmit} noValidate>
      <h2 className="form-title">{editingUser ? 'Editar Usuário' : 'Cadastrar Novo Usuário'}</h2>
      <div className="form-grid">
        <div className="form-group">
          <label htmlFor="nome">Nome *</label>
          <input
            id="nome"
            name="nome"
            type="text"
            value={form.nome}
            onChange={handleChange}
            className="form-control"
            required
            autoComplete="off"
          />
        </div>
        <div className="form-group">
          <label htmlFor="email">Email *</label>
          <input
            id="email"
            name="email"
            type="email"
            value={form.email}
            onChange={handleChange}
            className="form-control"
            required
            autoComplete="off"
          />
        </div>
        <div className="form-group">
          <label htmlFor="senha">Senha {editingUser ? '(Deixe em branco para não alterar)' : '*'}</label>
          <input
            id="senha"
            name="senha"
            type="password"
            value={form.senha}
            onChange={handleChange}
            className="form-control"
            required={!editingUser}
            autoComplete={editingUser ? "new-password" : "off"}
          />
        </div>
        <div className="form-group">
          <label htmlFor="confirmarSenha">Confirmar Senha {form.senha ? '*' : ''}</label>
          <input
            id="confirmarSenha"
            name="confirmarSenha"
            type="password"
            value={form.confirmarSenha}
            onChange={handleChange}
            className="form-control"
            required={!editingUser && !!form.senha}
            autoComplete="new-password"
          />
        </div>
        <div className="form-group">
          <label htmlFor="perfil">Perfil de Acesso</label>
          <select
            id="perfil"
            name="perfil"
            value={form.perfil}
            onChange={handleChange}
            className="form-control"
          >
            <option value="admin">Administrador</option>
            <option value="padrao">Padrão</option>
          </select>
        </div>
        <div className="form-group">
          <label htmlFor="status">Status</label>
          <select
            id="status"
            name="status"
            value={form.status}
            onChange={handleChange}
            className="form-control"
          >
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
          </select>
        </div>
        <div className="form-group form-group-full-width">
          <label htmlFor="foto">Foto de Perfil</label>
          <input
            id="foto"
            name="foto"
            type="file"
            onChange={handleFotoChange}
            className="form-control"
            accept="image/*"
          />
          {fotoPreview && (
            <img
              src={fotoPreview}
              alt="Pré-visualização"
              className="foto-thumb"
              style={{ marginTop: '10px', width: '80px', height: '80px', objectFit: 'cover', borderRadius: '4px' }}
            />
          )}
        </div>
      </div>
      <div className="form-buttons">
        <button type="button" className="btn btn-cancel" onClick={onCancel}>
          Cancelar
        </button>
        <button type="submit" className="btn btn-save">
          {editingUser ? 'Salvar Alterações' : 'Adicionar Usuário'}
        </button>
      </div>
    </form>
  );
};

export default function Usuarios() {
  const [usuarios, setUsuarios] = useState([]);
  const [editingUser, setEditingUser] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [notification, setNotification] = useState({ message: '', type: '' });
  const navigate = useNavigate();

  const initialFormState = useMemo(
    () => ({
      nome: '',
      email: '',
      senha: '',
      confirmarSenha: '',
      perfil: 'padrao',
      status: 'ativo',
      foto: ''
    }),
    []
  );

  const USUARIOS_API_URL = `${API_BASE_URL}/usuarios.php`;

  const fetchUsers = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await axios.get(USUARIOS_API_URL);
      const dados = response.data?.data || [];
      setUsuarios(Array.isArray(dados) ? dados : []);
    } catch (error) {
      showNotification('Erro ao carregar usuários.', 'error');
    } finally {
      setIsLoading(false);
    }
  }, [USUARIOS_API_URL]);

  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
      return;
    }
    fetchUsers();
  }, [fetchUsers, navigate]);

  const showNotification = (message, type) => {
    setNotification({ message, type });
    setTimeout(() => setNotification({ message: '', type: '' }), 3000);
  };

  const handleSave = async (form, fotoFile) => {
    const formData = new FormData();
    // Append form fields except confirmarSenha (not needed in backend)
    Object.entries(form).forEach(([key, value]) => {
      if (key !== 'confirmarSenha') formData.append(key, value ?? '');
    });

    if (fotoFile) formData.append('foto', fotoFile);

    // Distinção POST = criar, PUT = editar seria ideal; mas backend só usa POST
    if (editingUser) {
      formData.append('id', editingUser.id);
      // No backend, verifique se foto_existente é usado para manter a foto atual
      if (editingUser.foto) formData.append('foto_existente', editingUser.foto);
    }

    try {
      await axios.post(USUARIOS_API_URL, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      showNotification(`Usuário "${form.nome}" salvo com sucesso!`, 'success');
      setEditingUser(null);
      fetchUsers();
    } catch (error) {
      // Pegue mensagem de erro do backend, fallback para mensagem genérica
      const errorMsg = error.response?.data?.error || error.response?.data?.erro || 'Erro ao salvar o usuário.';
      showNotification(errorMsg, 'error');
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm('Tem certeza que deseja excluir este usuário?')) {
      try {
        await axios.delete(`${USUARIOS_API_URL}?id=${id}`);
        showNotification('Usuário excluído com sucesso!', 'success');
        fetchUsers();
      } catch (error) {
        const errorMsg = error.response?.data?.error || error.response?.data?.erro || 'Erro ao excluir o usuário.';
        showNotification(errorMsg, 'error');
      }
    }
  };

  const handleEdit = (user) => {
    setEditingUser(user);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleCancel = () => {
    setEditingUser(null);
  };

  return (
    <div className="page-container">
      {notification.message && <div className={`notification ${notification.type}`}>{notification.message}</div>}

      <div className="content-card">
        <UserForm onSave={handleSave} onCancel={handleCancel} editingUser={editingUser} initialFormState={initialFormState} />
      </div>

      <div className="content-card">
        <h3 className="table-title">Usuários Cadastrados</h3>
        <div className="table-wrapper">
          {isLoading ? (
            <Spinner />
          ) : (
            <table className="data-table">
              <thead>
                <tr>
                  <th>Foto</th>
                  <th>Nome</th>
                  <th>Email</th>
                  <th>Perfil</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                {usuarios.length > 0 ? (
                  usuarios.map((u) => (
                    <tr key={u.id}>
                      <td>
                        <img
                          src={`${UPLOADS_BASE_URL}/${u.foto || 'default-avatar.png'}`}
                          alt={u.nome}
                          className="foto-thumb"
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src = `${UPLOADS_BASE_URL}/default-avatar.png`;
                          }}
                        />
                      </td>
                      <td>{u.nome}</td>
                      <td>{u.email}</td>
                      <td>{u.perfil === 'admin' ? 'Administrador' : 'Padrão'}</td>
                      <td>{u.status === 'ativo' ? 'Ativo' : 'Inativo'}</td>
                      <td>
                        <div className="table-buttons">
                          <button onClick={() => handleEdit(u)} className="btn-icon" title="Editar" aria-label={`Editar usuário ${u.nome}`}>
                            <i className="fas fa-pen"></i>
                          </button>
                          <button
                            onClick={() => handleDelete(u.id)}
                            className="btn-icon btn-delete"
                            title="Excluir"
                            aria-label={`Excluir usuário ${u.nome}`}
                          >
                            <i className="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan="6" className="empty-state">
                      Nenhum usuário encontrado.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}
