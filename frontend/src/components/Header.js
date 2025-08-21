import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaUserCircle, FaChevronDown, FaThumbtack } from 'react-icons/fa';
import './header.css';
import { UPLOADS_BASE_URL } from '../apiConfig';

function Header() {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [showCadastroMenu, setShowCadastroMenu] = useState(false);
  const [showRelatoriosMenu, setShowRelatoriosMenu] = useState(false);
  const [showUserMenu, setShowUserMenu] = useState(false);

  // Usando useRef para armazenar os timeouts
  const cadastroTimeout = useRef(null);
  const relatoriosTimeout = useRef(null);

  useEffect(() => {
    const stored = localStorage.getItem('usuarioLogado');
    if (stored) setUser(JSON.parse(stored));

    // Cleanup dos timeouts ao desmontar
    return () => {
      if (cadastroTimeout.current) clearTimeout(cadastroTimeout.current);
      if (relatoriosTimeout.current) clearTimeout(relatoriosTimeout.current);
    };
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('usuarioLogado');
    localStorage.removeItem('token');
    navigate('/', { replace: true });
  };

  const toggleUserMenu = () => {
    setShowUserMenu(prev => !prev);
    setShowCadastroMenu(false);
    setShowRelatoriosMenu(false);
  };

  return (
    <nav className="navbar">
      <div className="navbar-left" onClick={() => navigate('/home')} style={{ cursor: 'pointer' }}>
        <span className="navbar-logo">ControleFlex</span>
      </div>

      <div className="navbar-center">
        <button className="nav-item" onClick={() => navigate('/home')}>
          🏠 Home
        </button>

        <button className="nav-item" onClick={() => navigate('/dashboard')}>
          📊 Dashboard
        </button>

        <button className="nav-item" onClick={() => navigate('/lancamentos')}>
          <FaThumbtack style={{ color: "white", marginRight: "6px" }} />
          Lançamentos
        </button>

        <div
          className="dropdown"
          onMouseEnter={() => {
            if (cadastroTimeout.current) clearTimeout(cadastroTimeout.current);
            setShowCadastroMenu(true);
          }}
          onMouseLeave={() => {
            cadastroTimeout.current = setTimeout(() => setShowCadastroMenu(false), 200);
          }}
        >
          <button className="nav-item">
            📝 Cadastro <FaChevronDown />
          </button>
          {showCadastroMenu && (
            <div className="dropdown-content">
              <button onClick={() => navigate('/bancos')}>🏦 Bancos</button>
              <button onClick={() => navigate('/familiares')}>👥 Familiares</button>
              <button onClick={() => navigate('/fornecedores')}>🏪 Fornecedores</button>
              <button onClick={() => navigate('/usuarios')}>👤 Usuários</button>
              <button onClick={() => navigate('/despesas')}>💸 Despesas</button>
              <button onClick={() => navigate('/receitas')}>💰 Receitas</button>
              <button onClick={() => navigate('/categorias')}>📂 Categorias</button>
              <button onClick={() => navigate('/investimentos')}>📈 Investimentos</button>
            </div>
          )}
        </div>

        <div
          className="dropdown"
          onMouseEnter={() => {
            if (relatoriosTimeout.current) clearTimeout(relatoriosTimeout.current);
            setShowRelatoriosMenu(true);
          }}
          onMouseLeave={() => {
            relatoriosTimeout.current = setTimeout(() => setShowRelatoriosMenu(false), 200);
          }}
        >
          <button className="nav-item">
            📊 Relatórios <FaChevronDown />
          </button>
          {showRelatoriosMenu && (
            <div className="dropdown-content">
              <button onClick={() => navigate('/relatorio-despesas')}>💸 Despesas</button>
              <button onClick={() => navigate('/relatorio-receitas')}>💰 Receitas</button>
              <button onClick={() => navigate('/relatorio-investimentos')}>📈 Investimentos</button>
              {/* NOVO: Adicionar link direto para Lançamentos nos relatórios */}
              <button onClick={() => navigate('/lancamentos')}>📌 Lançamentos</button>
            </div>
          )}
        </div>
      </div>

      <div className="navbar-right user-menu">
        {user && (
          <>
            {user.foto ? (
              <img
                src={`${UPLOADS_BASE_URL}/${user.foto}`}
                alt="Foto do usuário"
                className="user-avatar"
                onClick={toggleUserMenu}
                style={{ cursor: 'pointer' }}
              />
            ) : (
              <FaUserCircle className="user-icon" onClick={toggleUserMenu} />
            )}
            <span
              className="user-name"
              onClick={toggleUserMenu}
              style={{ cursor: 'pointer', marginLeft: '8px' }}
            >
              Olá, {user.nome}
            </span>
          </>
        )}

        {showUserMenu && (
          <div className="profile-dropdown">
            <button onClick={() => navigate('/usuarios')}>✏️ Editar Perfil</button>
            <button onClick={handleLogout}>🚪 Logout</button>
          </div>
        )}
      </div>
    </nav>
  );
}

export default Header;

