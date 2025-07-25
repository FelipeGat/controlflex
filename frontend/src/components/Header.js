import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaUserCircle, FaChevronDown } from 'react-icons/fa';
import './header.css';
// 1. Importa a variável de configuração para a pasta de uploads
import { UPLOADS_BASE_URL } from '../apiConfig';

function Header() {
  const navigate = useNavigate();
  const [user, setUser] = useState(null);
  const [showCadastroMenu, setShowCadastroMenu] = useState(false);
  const [showRelatoriosMenu, setShowRelatoriosMenu] = useState(false);
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [cadastroTimeout, setCadastroTimeout] = useState(null);
  const [relatoriosTimeout, setRelatoriosTimeout] = useState(null);

  useEffect(() => {
    const stored = localStorage.getItem('usuarioLogado');
    if (stored) setUser(JSON.parse(stored));

    return () => {
      if (cadastroTimeout) clearTimeout(cadastroTimeout);
      if (relatoriosTimeout) clearTimeout(relatoriosTimeout);
    };
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('usuarioLogado');
    localStorage.removeItem('token');
    navigate('/', { replace: true });
  };

  const toggleUserMenu = () => {
    setShowUserMenu((prev) => !prev);
    setShowCadastroMenu(false);
    setShowRelatoriosMenu(false);
  };

  // --- O seu JSX (return) permanece o mesmo, com uma correção na URL da imagem ---
  return (
    <nav className="navbar">
      <div className="navbar-left" onClick={() => navigate('/dashboard')} style={{ cursor: 'pointer' }}>
        <span className="navbar-logo">ControleFlex</span>
      </div>

      <div className="navbar-center">
        <button className="nav-item" onClick={() => navigate('/dashboard')}>
          Dashboard
        </button>

        <div
          className="dropdown"
          onMouseEnter={() => {
            if (cadastroTimeout) clearTimeout(cadastroTimeout);
            setShowCadastroMenu(true);
          }}
          onMouseLeave={() => {
            const timeout = setTimeout(() => setShowCadastroMenu(false), 200);
            setCadastroTimeout(timeout);
          }}
        >
          <button className="nav-item">
            Cadastro <FaChevronDown />
          </button>
          {showCadastroMenu && (
            <div className="dropdown-content">
              <button onClick={() => navigate('/bancos')}>Bancos</button>
              <button onClick={() => navigate('/familiares')}>Familiares</button>
              <button onClick={() => navigate('/fornecedores')}>Fornecedores</button>
              <button onClick={() => navigate('/usuarios')}>Usuários</button>
              <button onClick={() => navigate('/despesas')}>Despesas</button>
              <button onClick={() => navigate('/receitas')}>Receitas</button>
            </div>
          )}
        </div>

        <div
          className="dropdown"
          onMouseEnter={() => {
            if (relatoriosTimeout) clearTimeout(relatoriosTimeout);
            setShowRelatoriosMenu(true);
          }}
          onMouseLeave={() => {
            const timeout = setTimeout(() => setShowRelatoriosMenu(false), 200);
            setRelatoriosTimeout(timeout);
          }}
        >
          <button className="nav-item">
            Relatórios <FaChevronDown />
          </button>
          {showRelatoriosMenu && (
            <div className="dropdown-content">
              <button onClick={() => navigate('/relatorio-despesas')}>Despesas</button>
              <button onClick={() => navigate('/relatorio-receitas')}>Receitas</button>
              <button onClick={() => navigate('/relatorio-investimentos')}>Investimentos</button>
            </div>
          )}
        </div>
      </div>

      <div className="navbar-right user-menu">
        {user && (
          <>
            {user.foto ? (
              <img
                // 2. Corrige a URL da imagem do avatar
                src={`${UPLOADS_BASE_URL}/${user.foto}`}
                alt="Foto do usuário"
                className="user-avatar"
                onClick={toggleUserMenu}
                style={{ cursor: 'pointer' }}
              />
            ) : (
              <FaUserCircle className="user-icon" onClick={toggleUserMenu} />
            )}
            <span className="user-name" onClick={toggleUserMenu} style={{ cursor: 'pointer', marginLeft: '8px' }}>
              Olá, {user.nome}
            </span>
          </>
        )}

        {showUserMenu && (
          <div className="profile-dropdown">
            <button onClick={() => navigate('/perfil')}>Editar Perfil</button>
            <button onClick={handleLogout}>Logout</button>
          </div>
        )}
      </div>
    </nav>
  );
}

export default Header;
