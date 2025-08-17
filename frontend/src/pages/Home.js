import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { 
  FaMoneyBillWave, FaReceipt, FaClipboardList, FaUsers, FaUniversity, 
  FaChartBar, FaStore, FaChartLine, FaCog, FaExclamationTriangle,
  FaClock, FaCalendarAlt, FaCloudSun, FaDollarSign, FaBell,
  FaArrowUp, FaArrowDown, FaEquals, FaEye, FaCheckCircle
} from 'react-icons/fa';
import './home.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

// Componente de Saudação Dinâmica
const SaudacaoPersonalizada = ({ usuario }) => {
  const [saudacao, setSaudacao] = useState('');
  const [dataHora, setDataHora] = useState(new Date());

  useEffect(() => {
    const atualizarSaudacao = () => {
      const agora = new Date();
      const hora = agora.getHours();
      
      let mensagem = '';
      let emoji = '';
      
      if (hora >= 5 && hora < 12) {
        mensagem = 'Bom dia';
        emoji = '🌅';
      } else if (hora >= 12 && hora < 18) {
        mensagem = 'Boa tarde';
        emoji = '☀️';
      } else {
        mensagem = 'Boa noite';
        emoji = '🌙';
      }
      
      setSaudacao(`${emoji} ${mensagem}, ${usuario?.nome || 'Usuário'}!`);
      setDataHora(agora);
    };

    atualizarSaudacao();
    const interval = setInterval(atualizarSaudacao, 60000);
    
    return () => clearInterval(interval);
  }, [usuario]);

  return (
    <div className="saudacao-container">
      <h1 className="saudacao-titulo">{saudacao}</h1>
      <p className="saudacao-data">
        {dataHora.toLocaleDateString('pt-BR', { 
          weekday: 'long', 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        })} • {dataHora.toLocaleTimeString('pt-BR', { 
          hour: '2-digit', 
          minute: '2-digit' 
        })}
      </p>
    </div>
  );
};

// Componente de Informações Externas
const InformacoesExternas = () => {
  const [clima, setClima] = useState(null);
  const [cotacao, setCotacao] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const carregarInformacoes = async () => {
      try {
        const response = await axios.get(`${API_BASE_URL}/home.php?action=external_info`);
        if (response.data.success) {
          setClima(response.data.clima);
          setCotacao(response.data.cotacao);
        }
      } catch (error) {
        console.error('Erro ao carregar informações externas:', error);
      } finally {
        setLoading(false);
      }
    };

    carregarInformacoes();
    const interval = setInterval(carregarInformacoes, 30 * 60 * 1000);
    
    return () => clearInterval(interval);
  }, []);

  if (loading) {
    return (
      <div className="info-externa-loading">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="informacoes-externas">
      {clima && (
        <div className="info-card clima-card">
          <div className="info-header">
            <FaCloudSun className="info-icon" />
            <span>Clima</span>
          </div>
          <div className="info-content">
            <div className="temperatura">{Math.round(clima.temperatura)}°C</div>
            <div className="descricao">{clima.descricao}</div>
            <div className="cidade">{clima.cidade}</div>
          </div>
        </div>
      )}
      
      {cotacao && (
        <div className="info-card cotacao-card">
          <div className="info-header">
            <FaDollarSign className="info-icon" />
            <span>USD/BRL</span>
          </div>
          <div className="info-content">
            <div className="valor">R$ {cotacao.valor.toFixed(4)}</div>
            <div className={`variacao ${cotacao.variacao >= 0 ? 'positiva' : 'negativa'}`}>
              {cotacao.variacao >= 0 ? <FaArrowUp /> : <FaArrowDown />}
              {Math.abs(cotacao.variacao).toFixed(2)}%
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// Componente de Alertas Financeiros
const AlertasFinanceiros = ({ alertas }) => {
  const navigate = useNavigate();

  const getAlertaConfig = (tipo) => {
    const configs = {
      atrasado: {
        titulo: 'Contas Atrasadas',
        icon: FaExclamationTriangle,
        className: 'alerta-critico',
        cor: '#ea4335'
      },
      hoje: {
        titulo: 'Vencem Hoje',
        icon: FaClock,
        className: 'alerta-urgente',
        cor: '#fbbc05'
      },
      proximos: {
        titulo: 'Próximos 3 Dias',
        icon: FaCalendarAlt,
        className: 'alerta-atencao',
        cor: '#ff9800'
      }
    };
    return configs[tipo] || configs.atrasado;
  };

  const formatarMoeda = (valor) => {
    return parseFloat(valor).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
  };

  const formatarData = (data) => {
    return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
  };

  return (
    <div className="alertas-financeiros">
      <h2 className="secao-titulo">
        <FaBell className="secao-icon" />
        Alertas Financeiros
      </h2>
      
      <div className="alertas-grid">
        {Object.entries(alertas).map(([tipo, dados]) => {
          const config = getAlertaConfig(tipo);
          const Icon = config.icon;
          
          return (
            <div key={tipo} className={`alerta-card ${config.className}`}>
              <div className="alerta-header">
                <Icon className="alerta-icon" style={{ color: config.cor }} />
                <h3>{config.titulo}</h3>
                <span className="alerta-badge">{dados.total}</span>
              </div>
              
              <div className="alerta-valor">
                {formatarMoeda(dados.valor_total)}
              </div>
              
              {dados.items.length > 0 && (
                <div className="alerta-lista">
                  {dados.items.slice(0, 3).map((item, index) => (
                    <div key={index} className="alerta-item">
                      <span className={`item-tipo ${item.tipo}`}>
                        {item.tipo === 'receita' ? '💰' : '💸'}
                      </span>
                      <div className="item-info">
                        <div className="item-descricao">{item.descricao}</div>
                        <div className="item-detalhes">
                          {formatarMoeda(item.valor)} • {formatarData(item.data_prevista)}
                        </div>
                      </div>
                    </div>
                  ))}
                  
                  {dados.items.length > 3 && (
                    <div className="ver-mais" onClick={() => navigate('/lancamentos')}>
                      +{dados.items.length - 3} mais...
                    </div>
                  )}
                </div>
              )}
              
              {dados.items.length === 0 && (
                <div className="alerta-vazio">
                  <FaCheckCircle className="check-icon" />
                  Tudo em dia!
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};

// Componente de Resumo Financeiro Rápido
const ResumoFinanceiro = ({ resumo }) => {
  const formatarMoeda = (valor) => {
    return parseFloat(valor).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });
  };

  const getSaldoClass = (saldo) => {
    if (saldo > 0) return 'saldo-positivo';
    if (saldo < 0) return 'saldo-negativo';
    return 'saldo-neutro';
  };

  return (
    <div className="resumo-financeiro">
      <h2 className="secao-titulo">
        <FaChartBar className="secao-icon" />
        Resumo do Mês
      </h2>
      
      <div className="resumo-cards">
        <div className="resumo-card receitas">
          <div className="card-header">
            <FaArrowUp className="card-icon" />
            <span>Receitas</span>
          </div>
          <div className="card-valor">{formatarMoeda(resumo.receitas)}</div>
          <div className="card-meta">
            Meta: {formatarMoeda(resumo.meta_receitas || 0)}
          </div>
        </div>
        
        <div className="resumo-card despesas">
          <div className="card-header">
            <FaArrowDown className="card-icon" />
            <span>Despesas</span>
          </div>
          <div className="card-valor">{formatarMoeda(resumo.despesas)}</div>
          <div className="card-meta">
            Meta: {formatarMoeda(resumo.meta_despesas || 0)}
          </div>
        </div>
        
        <div className={`resumo-card saldo ${getSaldoClass(resumo.saldo)}`}>
          <div className="card-header">
            <FaEquals className="card-icon" />
            <span>Saldo</span>
          </div>
          <div className="card-valor">{formatarMoeda(resumo.saldo)}</div>
          <div className="card-meta">
            {resumo.saldo >= 0 ? 'Positivo' : 'Negativo'}
          </div>
        </div>
      </div>
    </div>
  );
};

// Componente de Navegação Rápida
const NavegacaoRapida = () => {
  const navigate = useNavigate();

  const menuItems = [
    { 
      path: '/receitas', 
      icon: FaMoneyBillWave, 
      label: 'Receitas', 
      color: '#34a853',
      description: 'Gerenciar receitas'
    },
    { 
      path: '/despesas', 
      icon: FaReceipt, 
      label: 'Despesas', 
      color: '#ea4335',
      description: 'Controlar gastos'
    },
    { 
      path: '/lancamentos', 
      icon: FaClipboardList, 
      label: 'Lançamentos', 
      color: '#1a73e8',
      description: 'Todos os lançamentos'
    },
    { 
      path: '/familiares', 
      icon: FaUsers, 
      label: 'Familiares', 
      color: '#4285f4',
      description: 'Membros da família'
    },
    { 
      path: '/bancos', 
      icon: FaUniversity, 
      label: 'Bancos', 
      color: '#1a73e8',
      description: 'Contas bancárias'
    },
    { 
      path: '/dashboard', 
      icon: FaChartBar, 
      label: 'Dashboard', 
      color: '#34a853',
      description: 'Relatórios e gráficos'
    },
    { 
      path: '/fornecedores', 
      icon: FaStore, 
      label: 'Fornecedores', 
      color: '#ff9800',
      description: 'Cadastro de fornecedores'
    },
    { 
      path: '/investimentos', 
      icon: FaChartLine, 
      label: 'Investimentos', 
      color: '#fbbc05',
      description: 'Carteira de investimentos'
    },
    { 
      path: '/categorias', 
      icon: FaCog, 
      label: 'Categorias', 
      color: '#5f6368',
      description: 'Categorias do sistema'
    }
  ];

  return (
    <div className="navegacao-rapida">
      <h2 className="secao-titulo">
        <FaClipboardList className="secao-icon" />
        Navegação Rápida
      </h2>
      
      <div className="menu-grid">
        {menuItems.map((item) => {
          const Icon = item.icon;
          return (
            <div
              key={item.path}
              className="menu-item"
              onClick={() => navigate(item.path)}
              style={{ '--item-color': item.color }}
            >
              <div className="menu-icon-container">
                <Icon className="menu-icon" />
              </div>
              <div className="menu-content">
                <h3 className="menu-label">{item.label}</h3>
                <p className="menu-description">{item.description}</p>
              </div>
              <div className="menu-arrow">→</div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

// Componente Principal HOME
export default function Home() {
  const navigate = useNavigate();
  const [usuario, setUsuario] = useState(null);
  const [dadosHome, setDadosHome] = useState(null);
  const [loading, setLoading] = useState(true);

  // Verificar usuário logado
  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  // Carregar dados da home
  const carregarDadosHome = useCallback(async () => {
    if (!usuario) return;

    try {
      setLoading(true);
      const response = await axios.get(`${API_BASE_URL}/home.php`, {
        params: {
          action: 'dashboard_data',
          usuario_id: usuario.id
        }
      });

      if (response.data.success) {
        setDadosHome(response.data.data);
      } else {
        console.error('Erro ao carregar dados:', response.data.message);
      }
    } catch (error) {
      console.error('Erro ao carregar dados da home:', error);
    } finally {
      setLoading(false);
    }
  }, [usuario]);

  useEffect(() => {
    if (usuario) {
      carregarDadosHome();
      
      // Atualizar dados a cada 5 minutos
      const interval = setInterval(carregarDadosHome, 5 * 60 * 1000);
      return () => clearInterval(interval);
    }
  }, [usuario, carregarDadosHome]);

  if (loading) {
    return (
      <div className="page-container home-loading">
        <Spinner />
        <p>Carregando seu jornal diário...</p>
      </div>
    );
  }

  return (
    <div className="page-container home-container">
      {/* Header com Saudação */}
      <div className="home-header">
        <SaudacaoPersonalizada usuario={usuario} />
        <InformacoesExternas />
      </div>

      {/* Conteúdo Principal */}
      <div className="home-content">
        {/* Alertas Financeiros */}
        {dadosHome?.alertas && (
          <AlertasFinanceiros alertas={dadosHome.alertas} />
        )}

        {/* Resumo Financeiro */}
        {dadosHome?.resumo && (
          <ResumoFinanceiro resumo={dadosHome.resumo} />
        )}

        {/* Navegação Rápida */}
        <NavegacaoRapida />
      </div>

      {/* Footer */}
      <div className="home-footer">
        <p>
          Última atualização: {new Date().toLocaleTimeString('pt-BR')} • 
          <span className="refresh-link" onClick={carregarDadosHome}>
            <FaEye /> Atualizar dados
          </span>
        </p>
      </div>
    </div>
  );
}

