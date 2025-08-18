import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import './TelaSucesso.css';

const TelaSucesso = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [animationStep, setAnimationStep] = useState(0);
  const [countdown, setCountdown] = useState(10);

  // Dados vindos da criação da conta
  const dadosConta = location.state || {
    codigo_tenant: 'DEMO123',
    admin_email: 'usuario@email.com',
    nome_empresa: 'Sua Empresa',
    trial_ate: '15 dias'
  };

  useEffect(() => {
    // Animação em etapas
    const timer1 = setTimeout(() => setAnimationStep(1), 500);
    const timer2 = setTimeout(() => setAnimationStep(2), 1500);
    const timer3 = setTimeout(() => setAnimationStep(3), 2500);

    // Countdown para redirecionamento automático
    const countdownTimer = setInterval(() => {
      setCountdown(prev => {
        if (prev <= 1) {
          clearInterval(countdownTimer);
          navigate('/');
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => {
      clearTimeout(timer1);
      clearTimeout(timer2);
      clearTimeout(timer3);
      clearInterval(countdownTimer);
    };
  }, [navigate]);

  const handleAcessarSistema = () => {
    navigate('/');
  };

  const handleVerificarEmail = () => {
    // Abrir cliente de email padrão
    window.open(`mailto:${dadosConta.admin_email}`, '_blank');
  };

  return (
    <div className="tela-sucesso">
      {/* Fundo com animação */}
      <div className="fundo-animado">
        <div className="particulas">
          {[...Array(20)].map((_, i) => (
            <div key={i} className={`particula particula-${i}`}></div>
          ))}
        </div>
      </div>

      {/* Container principal */}
      <div className="container-sucesso">
        
        {/* Ícone de sucesso animado */}
        <div className={`icone-sucesso ${animationStep >= 1 ? 'animate' : ''}`}>
          <div className="circulo-sucesso">
            <svg viewBox="0 0 52 52" className="checkmark">
              <circle className="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
              <path className="checkmark-check" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
          </div>
        </div>

        {/* Título principal */}
        <div className={`titulo-principal ${animationStep >= 1 ? 'animate' : ''}`}>
          <h1>🎉 Parabéns!</h1>
          <h2>Sua conta foi criada com sucesso!</h2>
        </div>

        {/* Informações da conta */}
        <div className={`info-conta ${animationStep >= 2 ? 'animate' : ''}`}>
          <div className="card-info">
            <div className="info-item">
              <span className="label">Empresa:</span>
              <span className="valor">{dadosConta.nome_empresa}</span>
            </div>
            <div className="info-item">
              <span className="label">Código da Empresa:</span>
              <span className="valor codigo-destaque">{dadosConta.codigo_tenant}</span>
            </div>
            <div className="info-item">
              <span className="label">Email de Acesso:</span>
              <span className="valor">{dadosConta.admin_email}</span>
            </div>
            <div className="info-item trial">
              <span className="label">🎁 Trial Gratuito:</span>
              <span className="valor">{dadosConta.trial_ate} dias</span>
            </div>
          </div>
        </div>

        {/* Próximos passos */}
        <div className={`proximos-passos ${animationStep >= 3 ? 'animate' : ''}`}>
          <h3>📋 Próximos Passos:</h3>
          
          <div className="passo">
            <div className="numero-passo">1</div>
            <div className="conteudo-passo">
              <h4>📧 Verifique seu Email</h4>
              <p>Enviamos instruções de boas-vindas e como começar para <strong>{dadosConta.admin_email}</strong></p>
              <button className="btn-secundario" onClick={handleVerificarEmail}>
                Abrir Email
              </button>
            </div>
          </div>

          <div className="passo">
            <div className="numero-passo">2</div>
            <div className="conteudo-passo">
              <h4>🚀 Acesse o Sistema</h4>
              <p>Use seu email e senha para fazer login e começar a organizar suas finanças</p>
              <button className="btn-primario" onClick={handleAcessarSistema}>
                Fazer Login Agora
              </button>
            </div>
          </div>

          <div className="passo">
            <div className="numero-passo">3</div>
            <div className="conteudo-passo">
              <h4>💡 Explore os Recursos</h4>
              <p>Cadastre familiares, bancos, categorias e comece a controlar receitas e despesas</p>
            </div>
          </div>
        </div>

        {/* Benefícios do trial */}
        <div className={`beneficios-trial ${animationStep >= 3 ? 'animate' : ''}`}>
          <h3>🎁 Seu Trial Inclui:</h3>
          <div className="lista-beneficios">
            <div className="beneficio">
              <span className="icone">✅</span>
              <span>Controle completo de receitas e despesas</span>
            </div>
            <div className="beneficio">
              <span className="icone">✅</span>
              <span>Relatórios detalhados</span>
            </div>
            <div className="beneficio">
              <span className="icone">✅</span>
              <span>Cadastro de familiares e bancos</span>
            </div>
            <div className="beneficio">
              <span className="icone">✅</span>
              <span>Suporte técnico completo</span>
            </div>
          </div>
        </div>

        {/* Rodapé com countdown */}
        <div className="rodape-sucesso">
          <p>Redirecionamento automático em <span className="countdown">{countdown}s</span></p>
          <p className="texto-suporte">
            Precisa de ajuda? Entre em contato: 
            <a href="mailto:suporte@controleflex.com"> suporte@controleflex.com</a>
          </p>
        </div>

      </div>
    </div>
  );
};

export default TelaSucesso;

