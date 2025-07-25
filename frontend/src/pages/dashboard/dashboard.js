import React, { useState, useEffect } from 'react';
import { Bar, Pie } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { useNavigate } from 'react-router-dom';
import AnnualChart from '../../components/AnnualChart';
import './dashboard.css';
import { API_BASE_URL } from '../../apiConfig';
import ContasTabela from './ContasTabela';
import './ContasTabela.css';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  ChartDataLabels
);

export default function Dashboard() {
  const [usuario, setUsuario] = useState(null);
  const [receitas, setReceitas] = useState([]);
  const [despesas, setDespesas] = useState([]);
  const [filtro, setFiltro] = useState('mes');
  const [loading, setLoading] = useState(true);
  const [annualData, setAnnualData] = useState(null);
  const [pizzaChartMode, setPizzaChartMode] = useState('despesas');
  const [notification, setNotification] = useState(null);
  const navigate = useNavigate();

  useEffect(() => {
    const user = JSON.parse(localStorage.getItem('usuarioLogado'));
    if (!user) {
      navigate('/');
    } else {
      setUsuario(user);
    }
  }, [navigate]);

  useEffect(() => {
    if (!usuario) return;

    const getDateRange = (filter) => {
      const hoje = new Date();
      let inicio, fim;

      switch (filter) {
        case 'hoje':
          inicio = fim = hoje.toISOString().split('T')[0];
          break;
        case 'semana':
          const primeiroDia = new Date(hoje);
          primeiroDia.setDate(hoje.getDate() - hoje.getDay());
          const ultimoDia = new Date(primeiroDia);
          ultimoDia.setDate(primeiroDia.getDate() + 6);
          inicio = primeiroDia.toISOString().split('T')[0];
          fim = ultimoDia.toISOString().split('T')[0];
          break;
        case 'ano':
          inicio = `${hoje.getFullYear()}-01-01`;
          fim = `${hoje.getFullYear()}-12-31`;
          break;
        default:
        case 'mes':
          const primeiroMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
          const ultimoMes = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0);
          inicio = primeiroMes.toISOString().split('T')[0];
          fim = ultimoMes.toISOString().split('T')[0];
          break;
      }
      return { inicio, fim };
    };

    const { inicio, fim } = getDateRange(filtro);
    setLoading(true);

    fetch(`${API_BASE_URL}/dashboard.php?inicio=${inicio}&fim=${fim}`)
      .then(res => res.json())
      .then(data => {
        setReceitas(data.receitas || []);
        setDespesas(data.despesas || []);
        setAnnualData(data.annualChart || null);
        setLoading(false);
      })
      .catch(err => {
        console.error("Erro ao carregar dados:", err);
        setLoading(false);
        showNotification('Erro ao carregar dados do dashboard', 'error');
      });
  }, [usuario, filtro]);

  const totalReceitas = receitas.reduce((sum, r) => sum + parseFloat(r.valor || 0), 0);
  const totalDespesas = despesas.reduce((sum, d) => sum + parseFloat(d.valor || 0), 0);
  const saldo = totalReceitas - totalDespesas;

  const agruparPorMes = (dados, campoData, campoValor) => {
    const meses = Array(12).fill(0);
    dados.forEach(item => {
      const data = new Date(item[campoData] + 'T00:00:00');
      const mes = data.getMonth();
      meses[mes] += parseFloat(item[campoValor] || 0);
    });
    return meses;
  };

  const receitasPorMes = agruparPorMes(receitas, 'data_recebimento', 'valor');
  const despesasPorMes = agruparPorMes(despesas, 'data_compra', 'valor');

  const dataBar = {
    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
    datasets: [
      {
        label: 'Receitas',
        data: receitasPorMes,
        backgroundColor: '#28a74580',
      },
      {
        label: 'Despesas',
        data: despesasPorMes,
        backgroundColor: '#dc354580',
      },
    ],
  };

  const agruparParaPizza = (dados, campoCategoria, campoValor) => {
    const resultado = {};
    dados.forEach(item => {
      const cat = item[campoCategoria] || 'Sem Categoria';
      resultado[cat] = (resultado[cat] || 0) + parseFloat(item[campoValor] || 0);
    });
    return resultado;
  };

  const dadosPizza = pizzaChartMode === 'despesas'
    ? agruparParaPizza(despesas, 'categoria_nome', 'valor')
    : agruparParaPizza(receitas, 'fornecedor', 'valor');

  const dataPie = {
    labels: Object.keys(dadosPizza),
    datasets: [{
      data: Object.values(dadosPizza),
      backgroundColor: ['#dc3545', '#ffc107', '#28a745', '#007bff', '#6f42c1']
    }],
  };

  const showNotification = (message, type = 'info') => {
    setNotification({ message, type });
    setTimeout(() => setNotification(null), 5000);
  };

  const handleEditConta = (conta) => {
    // ...
  };

  const handleDeleteConta = async (conta) => {
    // ...
  };

  const handleSaveEdit = async (dadosEditados) => {
    // ...
  };

  const contas = [
    ...despesas.map(d => ({ ...d, tipo: 'pagar', id: `despesa-${d.id}`, vencimento: d.data_compra })),
    ...receitas.map(r => ({ ...r, tipo: 'receber', id: `receita-${r.id}`, vencimento: r.data_recebimento })),
  ];

  if (loading) return <div className="page-container">Carregando...</div>;

  return (
    <div className="page-container">
      {/* Botões “Ver Todas…” */}
      <div className="ver-todas-btns">
        <button 
          onClick={() => navigate('/despesas')} 
          className="btn-ver-todas"
        >
          Ver Todas as Despesas
        </button>
        <button 
          onClick={() => navigate('/receitas')} 
          className="btn-ver-todas"
        >
          Ver Todas as Receitas
        </button>
      </div>

      {/* Tabela Contas a Pagar e Receber (agora no topo) */}
      <section style={{ marginTop: '1rem' }}>
        <ContasTabela 
          contas={contas} 
          exibirAcoes={false}
/>
      </section>

      {/* Filtros de período */}
      <div className="main-content-card">
        <header className="dashboard-header">
          <div className="filtros-container">
            <label htmlFor="filtro-periodo">Filtrar Período:</label>
            <select 
              id="filtro-periodo" 
              value={filtro} 
              onChange={e => setFiltro(e.target.value)} 
              className="form-control"
            >
              <option value="mes">Este Mês</option>
              <option value="semana">Esta Semana</option>
              <option value="hoje">Hoje</option>
              <option value="ano">Este Ano</option>
            </select>
          </div>
        </header>

        <main className="dashboard-main">
          <section className="summary-cards-grid">
            <div 
              className={`card-resumo green-bg ${pizzaChartMode === 'receitas' ? 'active' : ''}`}
              onClick={() => setPizzaChartMode('receitas')}
            >
              <span>Receitas</span>
              <strong>{totalReceitas.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</strong>
            </div>
            <div 
              className={`card-resumo red-bg ${pizzaChartMode === 'despesas' ? 'active' : ''}`}
              onClick={() => setPizzaChartMode('despesas')}
            >
              <span>Despesas</span>
              <strong>{totalDespesas.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</strong>
            </div>
            <div className="card-resumo">
              <span>Saldo</span>
              <strong className={saldo >= 0 ? 'saldo-positivo' : 'saldo-negativo'}>
                {saldo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
              </strong>
            </div>
          </section>

          <section className="annual-chart-section">
            <h3>Visão Geral do Ano</h3>
            <div className="annual-chart-container">
              {annualData
                ? <AnnualChart chartData={annualData} showDataLabels={false} />
                : <p>Carregando gráfico anual...</p>
              }
            </div>
          </section>
          
          <section className="detailed-charts-grid">
            <div className="grafico-container">
              <h3>Detalhes do Período</h3>
              <Bar data={dataBar} options={{ responsive: true, maintainAspectRatio: false }} />
            </div>
            <div className="grafico-container">
              <h3>
                {pizzaChartMode === 'despesas'
                  ? 'Despesas por Categoria'
                  : 'Receitas por Fornecedor'}
              </h3>
              {Object.keys(dadosPizza).length > 0
                ? <Pie data={dataPie} options={{
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                      legend: { position: 'right' },
                      datalabels: {
                        formatter: (value, ctx) => {
                          const total = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                          const perc = (value / total) * 100;
                          return perc > 5 ? `${perc.toFixed(1)}%` : '';
                        },
                        color: '#fff',
                      },
                    },
                  }} />
                : <p className="no-data">Sem dados para exibir.</p>
              }
            </div>
          </section>
        </main>
      </div>

      {/* Notificação */}
      {notification && (
        <div className={`notification notification-${notification.type}`}>
          {notification.message}
          <button 
            onClick={() => setNotification(null)} 
            className="notification-close"
          >
            ×
          </button>
        </div>
      )}
    </div>
  );
}
