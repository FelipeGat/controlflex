import React, { useState } from 'react';
import './login.css';
import { useNavigate } from 'react-router-dom';
import { API_BASE_URL } from '../apiConfig';

function Login() {
  const [usuario, setUsuario] = useState('');
  const [senha, setSenha] = useState('');
  const [erro, setErro] = useState('');
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();

    try {
      // 2. Corrige o fetch para usar a variável de ambiente
      const resposta = await fetch(`${API_BASE_URL}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario, senha }),
      });

      const dados = await resposta.json();

      if (resposta.ok && dados.sucesso) {
        localStorage.setItem('usuarioLogado', JSON.stringify({
          id: dados.id,
          nome: dados.nome,
          email: dados.email,
          foto: dados.foto || null,
        }));

        // ALTERAÇÃO: Redirecionar para HOME ao invés de Dashboard
        navigate('/home');
      } else {
        setErro(dados.erro || 'Erro ao fazer login');
      }
    } catch (err) {
      setErro('Erro ao conectar com o servidor');
    }
  };

  // --- O seu JSX (return) permanece exatamente o mesmo ---
  return (
    <div className="bg-animated center-container">
      <div className="glass-card">
        <h1 className="text-center cursive-logo">ControleFlex</h1>
        <div className="text-center mb-4">
          <img src="https://cdn-icons-png.flaticon.com/512/1041/1041872.png" width="80" alt="Carteira Digital" />
          <p className="text-muted mt-2">Organize suas finanças com facilidade</p>
        </div>

        {erro && <div className="alert alert-danger">{erro}</div>}

        <form onSubmit={handleLogin}>
          <div className="mb-3 input-group">
            <span className="input-group-text"><i className="bi bi-person"></i></span>
            <input
              type="text"
              className="form-control"
              placeholder="Email"
              value={usuario}
              onChange={(e ) => setUsuario(e.target.value)}
              required
            />
          </div>
          <div className="mb-3 input-group">
            <span className="input-group-text"><i className="bi bi-lock"></i></span>
            <input
              type="password"
              className="form-control"
              placeholder="Senha"
              value={senha}
              onChange={(e) => setSenha(e.target.value)}
              required
            />
          </div>
          <button type="submit" className="btn btn-primary w-100 mt-3">Entrar</button>
        </form>
      </div>

      <div className="wave-container">
        <svg className="waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 100" preserveAspectRatio="none">
          <path d="M0,0 C300,100 900,0 1200,100 L1200,0 L0,0 Z" fill="#e0f7fa" />
        </svg>
      </div>
    </div>
   );
}

export default Login;

