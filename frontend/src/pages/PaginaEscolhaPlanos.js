import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import './PaginaEscolhaPlanos.css';
import { API_BASE_URL } from '../apiConfig';

const PaginaEscolhaPlanos = () => {
  const [planos, setPlanos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [planoSelecionado, setPlanoSelecionado] = useState(null);
  const [mostrarFormulario, setMostrarFormulario] = useState(false);
  const [enviando, setEnviando] = useState(false);
  const navigate = useNavigate();

  // Dados do formulário - MODIFICADO para contexto pessoal
  const [formData, setFormData] = useState({
    nome_empresa: '', // Mantém o nome do campo para compatibilidade com API
    cnpj_cpf: '',
    email_contato: '',
    telefone: '',
    admin_nome: '',
    admin_email: '',
    admin_senha: '',
    confirmar_senha: ''
  });

  // Planos padrão como fallback
  const planosDefault = [
    {
      id: 1,
      nome_plano: 'Básico',
      valor_mensal: 29.90,
      limite_usuarios: 3,
      limite_transacoes: 1000,
      recursos_inclusos: ['Controle de despesas', 'Relatórios básicos', 'Suporte por email']
    },
    {
      id: 2,
      nome_plano: 'Profissional',
      valor_mensal: 59.90,
      limite_usuarios: 10,
      limite_transacoes: 5000,
      recursos_inclusos: ['Todos do Básico', 'Relatórios avançados', 'Múltiplos usuários', 'Suporte prioritário']
    },
    {
      id: 3,
      nome_plano: 'Empresarial',
      valor_mensal: 99.90,
      limite_usuarios: -1,
      limite_transacoes: -1,
      recursos_inclusos: ['Todos do Profissional', 'Usuários ilimitados', 'Transações ilimitadas', 'Suporte 24/7']
    }
  ];

  // Carregar planos disponíveis
  useEffect(() => {
    carregarPlanos();
  }, []);

  const carregarPlanos = async () => {
    try {
      setLoading(true);
      setError('');
      
      console.log('Carregando planos da API...');
      const response = await fetch(`${API_BASE_URL}/criar_tenant_novo.php?action=planos`);
      
      console.log('Response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      console.log('Dados recebidos da API:', data);
      
      // CORRIGIDO: Verificar estrutura da resposta
      let planosData = [];
      
      if (data.status === 'success' && Array.isArray(data.data)) {
        // Se a API retorna { status: 'success', data: [...] }
        planosData = data.data;
      } else if (Array.isArray(data)) {
        // Se a API retorna diretamente um array
        planosData = data;
      } else if (data.planos && Array.isArray(data.planos)) {
        // Se a API retorna { planos: [...] }
        planosData = data.planos;
      } else {
        console.warn('Formato de resposta inesperado:', data);
        throw new Error('Formato de dados inválido');
      }
      
      // Verificar se temos planos válidos
      if (planosData.length === 0) {
        console.warn('Nenhum plano retornado pela API, usando planos padrão');
        planosData = planosDefault;
      }
      
      // Normalizar dados dos planos
      const planosNormalizados = planosData.map(plano => ({
        id: plano.id || plano.plano_id,
        nome_plano: plano.nome_plano || plano.nome || 'Plano',
        valor_mensal: parseFloat(plano.valor_mensal || plano.preco || 0),
        limite_usuarios: plano.limite_usuarios || plano.max_usuarios || -1,
        limite_transacoes: plano.limite_transacoes || plano.max_transacoes || -1,
        recursos_inclusos: plano.recursos_inclusos || plano.recursos || ['Recursos básicos']
      }));
      
      console.log('Planos normalizados:', planosNormalizados);
      setPlanos(planosNormalizados);
      
    } catch (err) {
      console.error('Erro ao carregar planos:', err);
      setError(`Erro ao carregar planos: ${err.message}`);
      
      // Usar planos padrão como fallback
      console.log('Usando planos padrão como fallback');
      setPlanos(planosDefault);
    } finally {
      setLoading(false);
    }
  };

  const selecionarPlano = (plano) => {
    setPlanoSelecionado(plano);
    setMostrarFormulario(true);
    setError(''); // Limpar erro ao selecionar plano
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const validarFormulario = () => {
    const erros = [];

    // MODIFICADO: Validação para "Nome do Cliente"
    if (!formData.nome_empresa.trim()) erros.push('Nome do cliente é obrigatório');
    if (!formData.email_contato.trim()) erros.push('Email de contato é obrigatório');
    if (!formData.admin_nome.trim()) erros.push('Nome do administrador é obrigatório');
    if (!formData.admin_email.trim()) erros.push('Email do administrador é obrigatório');
    if (!formData.admin_senha.trim()) erros.push('Senha é obrigatória');
    if (formData.admin_senha !== formData.confirmar_senha) erros.push('Senhas não coincidem');

    // Validar emails
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (formData.email_contato && !emailRegex.test(formData.email_contato)) {
      erros.push('Email de contato inválido');
    }
    if (formData.admin_email && !emailRegex.test(formData.admin_email)) {
      erros.push('Email do administrador inválido');
    }

    return erros;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    const erros = validarFormulario();
    if (erros.length > 0) {
      setError(erros.join(', '));
      return;
    }

    setEnviando(true);
    setError('');

    try {
      console.log('Enviando dados para criação de conta:', {
        ...formData,
        plano_id: planoSelecionado.id
      });
      
      const response = await fetch(`${API_BASE_URL}/criar_tenant_novo.php?action=criar`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...formData,
          plano_id: planoSelecionado.id
        })
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Resposta da API:', data);

      if (response.ok && (data.success || data.status === 'success')) {
        const codigoTenant = data.codigo_tenant || data.data?.codigo_tenant || 'N/A';
        alert(`Conta criada com sucesso!\n\nCódigo da empresa: ${codigoTenant}\nEmail: ${formData.admin_email}\n\nVocê tem 15 dias de trial gratuito!`);
        navigate('/');
      } else {
        setError(data.error || data.message || 'Erro ao criar conta');
      }
    } catch (err) {
      console.error('Erro ao criar conta:', err);
      setError('Erro ao conectar com o servidor');
    } finally {
      setEnviando(false);
    }
  };

  const voltarParaPlanos = () => {
    setMostrarFormulario(false);
    setPlanoSelecionado(null);
    setError('');
  };

  const voltarParaLogin = () => {
    navigate('/');
  };

  if (loading) {
    return (
      <div className="pagina-planos">
        <div className="container-loading">
          <div className="spinner"></div>
          <p>Carregando planos...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="pagina-planos">
      <div className="container-principal">
        {!mostrarFormulario ? (
          // Tela de seleção de planos
          <div className="secao-planos">
            <div className="cabecalho">
              <h1 className="logo">ControleFlex</h1>
              <h2 className="titulo">Escolha seu Plano</h2>
              <p className="subtitulo">Comece com 15 dias grátis em qualquer plano</p>
            </div>

            {error && (
              <div className="alert-erro">
                {error}
                <br />
                <small>Usando planos padrão para demonstração</small>
              </div>
            )}

            <div className="grid-planos">
              {Array.isArray(planos) && planos.length > 0 ? (
                planos.map((plano) => (
                  <div key={plano.id} className="card-plano">
                    <div className="cabecalho-plano">
                      <h3 className="nome-plano">{plano.nome_plano}</h3>
                      <div className="preco">
                        <span className="valor">R$ {plano.valor_mensal.toFixed(2)}</span>
                        <span className="periodo">/mês</span>
                      </div>
                    </div>

                    <div className="recursos">
                      <h4>Recursos inclusos:</h4>
                      <ul>
                        {Array.isArray(plano.recursos_inclusos) && plano.recursos_inclusos.map((recurso, index) => (
                          <li key={index}>{recurso}</li>
                        ))}
                      </ul>
                    </div>

                    <div className="limites">
                      <p><strong>Usuários:</strong> {plano.limite_usuarios === -1 ? 'Ilimitados' : plano.limite_usuarios}</p>
                      <p><strong>Transações:</strong> {plano.limite_transacoes === -1 ? 'Ilimitadas' : plano.limite_transacoes.toLocaleString()}</p>
                    </div>

                    <button 
                      className="btn-selecionar"
                      onClick={() => selecionarPlano(plano)}
                    >
                      Escolher {plano.nome_plano}
                    </button>
                  </div>
                ))
              ) : (
                <div className="erro-planos">
                  <p>Erro ao carregar planos. Tente recarregar a página.</p>
                  <button onClick={carregarPlanos} className="btn-recarregar">
                    Recarregar Planos
                  </button>
                </div>
              )}
            </div>

            <div className="rodape">
              <button className="btn-voltar" onClick={voltarParaLogin}>
                ← Voltar ao Login
              </button>
            </div>
          </div>
        ) : (
          // Formulário de cadastro - MODIFICADO
          <div className="secao-formulario">
            <div className="cabecalho">
              <h1 className="logo">ControleFlex</h1>
              <h2 className="titulo">Criar Conta - {planoSelecionado.nome_plano}</h2>
              <p className="subtitulo">Preencha os dados para criar sua conta</p>
            </div>

            {error && (
              <div className="alert-erro">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="formulario">
              {/* MODIFICADO: Seção "Dados do Cliente" */}
              <div className="secao-form">
                <h3>Dados do Cliente</h3>
                <div className="grid-inputs">
                  <input
                    type="text"
                    name="nome_empresa"
                    placeholder="Nome do Cliente *"
                    value={formData.nome_empresa}
                    onChange={handleInputChange}
                    required
                  />
                  <input
                    type="text"
                    name="cnpj_cpf"
                    placeholder="CNPJ/CPF"
                    value={formData.cnpj_cpf}
                    onChange={handleInputChange}
                  />
                  <input
                    type="email"
                    name="email_contato"
                    placeholder="Email de Contato *"
                    value={formData.email_contato}
                    onChange={handleInputChange}
                    required
                  />
                  <input
                    type="tel"
                    name="telefone"
                    placeholder="Telefone"
                    value={formData.telefone}
                    onChange={handleInputChange}
                  />
                </div>
              </div>

              <div className="secao-form">
                <h3>Dados do Administrador</h3>
                <div className="grid-inputs">
                  <input
                    type="text"
                    name="admin_nome"
                    placeholder="Nome Completo *"
                    value={formData.admin_nome}
                    onChange={handleInputChange}
                    required
                  />
                  <input
                    type="email"
                    name="admin_email"
                    placeholder="Email *"
                    value={formData.admin_email}
                    onChange={handleInputChange}
                    required
                  />
                  <input
                    type="password"
                    name="admin_senha"
                    placeholder="Senha *"
                    value={formData.admin_senha}
                    onChange={handleInputChange}
                    required
                  />
                  <input
                    type="password"
                    name="confirmar_senha"
                    placeholder="Confirmar Senha *"
                    value={formData.confirmar_senha}
                    onChange={handleInputChange}
                    required
                  />
                </div>
              </div>

              <div className="acoes-formulario">
                <button 
                  type="button" 
                  className="btn-voltar"
                  onClick={voltarParaPlanos}
                >
                  ← Voltar aos Planos
                </button>
                <button 
                  type="submit" 
                  className="btn-criar"
                  disabled={enviando}
                >
                  {enviando ? 'Criando...' : 'Criar Conta'}
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
};

export default PaginaEscolhaPlanos;

