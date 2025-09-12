import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { Bar, Doughnut, Line } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    PointElement,
    LineElement
} from 'chart.js';
import './dashboard.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    PointElement,
    LineElement
);

export const formatCurrency = (value) => {
    const numValue = parseFloat(value);
    if (isNaN(numValue)) return 'R$ 0,00';
    return `R$ ${numValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

// Paleta de cores profissional
const professionalColors = {
    // Cores principais
    receita: '#10B981',      // Verde moderno
    despesa: '#EF4444',      // Vermelho moderno
    saldoPositivo: '#3B82F6', // Azul moderno
    saldoNegativo: '#F59E0B', // Laranja de alerta

    // Gradientes
    receitaGradient: ['#10B981', '#059669'],
    despesaGradient: ['#EF4444', '#DC2626'],
    neutroGradient: ['#6366F1', '#4F46E5'],

    // Paleta para gráficos diversos
    chart: [
        '#3B82F6', // Azul
        '#10B981', // Verde
        '#F59E0B', // Laranja
        '#EF4444', // Vermelho
        '#8B5CF6', // Roxo
        '#06B6D4', // Ciano
        '#84CC16', // Lima
        '#F97316', // Laranja escuro
        '#EC4899', // Rosa
        '#6366F1', // Índigo
        '#14B8A6', // Teal
        '#A855F7'  // Violeta
    ],

    // Cores de fundo e texto
    background: '#F8FAFC',
    cardBackground: '#FFFFFF',
    textPrimary: '#1E293B',
    textSecondary: '#64748B',
    border: '#E2E8F0'
};

const generateColor = (str, type = 'chart') => {
    if (type === 'receita') return professionalColors.receita;
    if (type === 'despesa') return professionalColors.despesa;
    if (type === 'saldo') return professionalColors.saldoPositivo;

    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash % professionalColors.chart.length);
    return professionalColors.chart[index];
};

// Função para gerar gradientes
const generateGradient = (ctx, colors, direction = 'vertical') => {
    const gradient = ctx.createLinearGradient(
        0, direction === 'vertical' ? 0 : ctx.canvas.width,
        direction === 'vertical' ? ctx.canvas.height : 0,
        0
    );
    gradient.addColorStop(0, colors[0]);
    gradient.addColorStop(1, colors[1]);
    return gradient;
};

// Função melhorada para cálculo de percentuais
const calculatePercentage = (realizado, previsto, type = 'default') => {
    // Converte para números e trata valores inválidos
    const real = parseFloat(realizado) || 0;
    const prev = parseFloat(previsto) || 0;

    // Se o previsto for zero, retorna casos especiais
    if (prev === 0) {
        if (real === 0) return 0; // Ambos zero = 0%
        return real > 0 ? 999 : -999; // Valor muito alto/baixo para indicar situação especial
    }

    // Cálculo padrão
    const percentage = (real / prev) * 100;

    // Para saldos negativos previstos, inverte a lógica
    if (type === 'saldo' && prev < 0) {
        return ((real - prev) / Math.abs(prev)) * 100;
    }

    // Limita valores extremos
    if (percentage > 9999) return 9999;
    if (percentage < -9999) return -9999;

    return Math.round(percentage * 10) / 10; // Arredonda para 1 casa decimal
};

// Função para formatar percentual com indicadores visuais
const formatPercentage = (percentage, type = 'default') => {
    const value = parseFloat(percentage);

    if (isNaN(value)) return '0.0';

    // Casos especiais
    if (Math.abs(value) >= 999) {
        return value > 0 ? '999+' : '-999';
    }

    return value.toFixed(1);
};

const getPeriodDates = (period) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let inicio, fim;

    switch (period) {
        case 'today':
            inicio = fim = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);
            inicio = fim = yesterday.toISOString().split('T')[0];
            break;
        case 'tomorrow':
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);
            inicio = fim = tomorrow.toISOString().split('T')[0];
            break;
        case 'this_week':
            const firstDayOfWeek = new Date(today);
            const dayOfWeek = today.getDay();
            firstDayOfWeek.setDate(today.getDate() - dayOfWeek);
            const lastDayOfWeek = new Date(firstDayOfWeek);
            lastDayOfWeek.setDate(firstDayOfWeek.getDate() + 6);
            inicio = firstDayOfWeek.toISOString().split('T')[0];
            fim = lastDayOfWeek.toISOString().split('T')[0];
            break;
        case 'last_week':
            const lastWeekStart = new Date(today);
            lastWeekStart.setDate(today.getDate() - today.getDay() - 7);
            const lastWeekEnd = new Date(lastWeekStart);
            lastWeekEnd.setDate(lastWeekStart.getDate() + 6);
            inicio = lastWeekStart.toISOString().split('T')[0];
            fim = lastWeekEnd.toISOString().split('T')[0];
            break;
        case 'this_month':
            inicio = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            fim = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case 'last_month':
            inicio = new Date(today.getFullYear(), today.getMonth() - 1, 1).toISOString().split('T')[0];
            fim = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
            break;
        case 'next_month':
            inicio = new Date(today.getFullYear(), today.getMonth() + 1, 1).toISOString().split('T')[0];
            fim = new Date(today.getFullYear(), today.getMonth() + 2, 0).toISOString().split('T')[0];
            break;
        case 'this_year':
            inicio = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            fim = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
            break;
        case 'last_year':
            inicio = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0];
            fim = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
            break;
        case 'next_year':
            inicio = new Date(today.getFullYear() + 1, 0, 1).toISOString().split('T')[0];
            fim = new Date(today.getFullYear() + 1, 11, 31).toISOString().split('T')[0];
            break;
        default:
            inicio = fim = today.toISOString().split('T')[0];
    }
    return { inicio, fim };
};

// =================================================================
// Componentes Reutilizáveis (mantendo estrutura original)
// =================================================================
const KpiCard = ({ title, value, percentage, theme, showValues }) => {
    const valueColor = theme === 'receita' ? 'kpi-value-positive' : theme === 'despesa' ? 'kpi-value-negative' : '';
    const percentageColor = percentage === null ? '' : (parseFloat(percentage) >= 100 ? 'kpi-percent-positive' : 'kpi-percent-negative');
    const isSaldo = theme === 'saldo';
    const saldoTheme = isSaldo ? (value >= 0 ? 'positivo' : 'negativo') : '';

    return (
        <div className={`kpi-card ${theme} ${saldoTheme}`}>
            <span className="kpi-title">{title}</span>
            <span className={`kpi-value ${valueColor}`}>{showValues ? formatCurrency(value) : 'R$ ****'}</span>
            {percentage !== null && (
                <div className="kpi-footer">
                    <span className={`kpi-percent ${percentageColor}`}>{showValues ? `${percentage}%` : '**%'}</span>
                    <span className="kpi-footer-text">do previsto</span>
                </div>
            )}
        </div>
    );
};

const SaldoCard = ({ title, value, percentage, showValues }) => {
    const positive = value >= 0;
    return (
        <div className={`kpi-card saldo ${positive ? 'positivo' : 'negativo'}`}>
            <span className="kpi-title">{title}</span>
            <span className={`kpi-value ${positive ? 'kpi-value-positive' : 'kpi-value-negative'}`}>
                {showValues ? formatCurrency(value) : 'R$ ****'}
            </span>
            {percentage !== null && (
                <div className="kpi-footer">
                    <span className={`kpi-percent ${positive ? 'kpi-percent-positive' : 'kpi-percent-negative'}`}>
                        {showValues ? `${percentage}%` : '**%'}
                    </span>
                    <span className="kpi-footer-text">do previsto</span>
                </div>
            )}
        </div>
    );
};

const DoughnutChartCard = ({ title, chartData, showValues }) => {
    const data = useMemo(() => {
        return {
            labels: chartData.map(c => c.nome),
            datasets: [{
                data: chartData.map(c => parseFloat(c.total)),
                backgroundColor: chartData.map(c => generateColor(c.nome)),
                borderColor: '#fff',
                borderWidth: 2,
            }],
        };
    }, [chartData]);

    const options = useMemo(() => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: { boxWidth: 10, padding: 15 }
            },
            title: { display: false },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
                        if (total === 0) return `${label}: R$ 0,00 (0%)`;
                        const percentage = ((value / total) * 100).toFixed(1);
                        return showValues ? `${label}: ${formatCurrency(value)} (${percentage}%)` : `${label}: R$ **** (${percentage}%)`;
                    }
                }
            }
        },
    }), [showValues]);

    return (
        <div className="dashboard-card doughnut-chart-card">
            <h3 className="chart-title">{title}</h3>
            {chartData && chartData.length > 0 ? (
                <div className="doughnut-chart-container">
                    <Doughnut data={data} options={options} />
                </div>
            ) : (
                <div className="empty-chart">Nenhum dado para exibir no período.</div>
            )}
        </div>
    );
};

const FinancialSummaryCard = ({ data, showValues }) => {
    const [isFlipped, setIsFlipped] = useState(false);

    const handleFlip = () => {
        setIsFlipped(!isFlipped);
    };

    const {
        contas,
        cartoes,
        totalChequeEspecialDisponivel,
        totalCreditoDisponivelCartao,
        totalSaldoContas,
    } = data;

    // Calcular os dados para os gráficos de pizza
    const totalLimiteCheque = contas.reduce((acc, conta) => acc + parseFloat(conta.cheque_especial_limite || 0), 0);
    const totalLimiteCartoes = cartoes.reduce((acc, cartao) => acc + parseFloat(cartao.limite || 0), 0);
    const totalCreditoUsadoCartao = cartoes.reduce((acc, cartao) => acc + parseFloat(cartao.utilizado || 0), 0);
    const totalChequeEspecialUsado = contas.reduce((acc, conta) => acc + parseFloat(conta.cheque_especial_usado || 0), 0);

    const totalSaldoGeral = totalSaldoContas + totalChequeEspecialDisponivel + totalCreditoDisponivelCartao;
    const totalSaldoGeralColor = totalSaldoGeral >= 0 ? 'text-success' : 'text-danger';

    // Data para os gráficos de pizza
    const contasData = contas.map(c => ({ nome: c.nome, total: parseFloat(c.saldo) }));
    const creditoData = [
        { nome: 'Limite em Cartões', total: totalLimiteCartoes },
        { nome: 'Limite em Cheque Especial', total: totalLimiteCheque },
    ];
    const chequeEspecialData = [
        { nome: 'Disponível', total: totalChequeEspecialDisponivel },
        { nome: 'Utilizado', total: totalChequeEspecialUsado },
    ];
    const cartoesData = cartoes.map(c => ({ nome: c.nome, total: parseFloat(c.limite) }));
    const cartoesDisponivelData = cartoes.map(c => ({ nome: c.nome, total: parseFloat(c.limite - c.utilizado) }));

    return (
        <div className={`dashboard-card card-flipper ${isFlipped ? 'is-flipped' : ''}`} onClick={handleFlip}>
            <div className="card-front">
                <h3 className="card-title">Resumo Financeiro</h3>
                <div className="summary-list">
                    <div className="summary-item">
                        <span className="summary-label">Saldo em Contas:</span>
                        <span className="summary-value text-success">{showValues ? formatCurrency(totalSaldoContas) : 'R$ ****'}</span>
                    </div>
                    <div className="summary-item">
                        <span className="summary-label">Cheque Especial Disponível:</span>
                        <span className="summary-value text-success">{showValues ? formatCurrency(totalChequeEspecialDisponivel) : 'R$ ****'}</span>
                    </div>
                    <div className="summary-item">
                        <span className="summary-label">Crédito Disponível em Cartões:</span>
                        <span className="summary-value text-success">{showValues ? formatCurrency(totalCreditoDisponivelCartao) : 'R$ ****'}</span>
                    </div>
                    <div className="summary-item total">
                        <span className="summary-label">Saldo Total:</span>
                        <span className={`summary-value ${totalSaldoGeralColor}`}>{showValues ? formatCurrency(totalSaldoGeral) : 'R$ ****'}</span>
                    </div>
                </div>
                <div className="card-footer-info">Clique para detalhes</div>
            </div>

            <div className="card-back">
                <h3 className="card-title">Detalhes por Conta</h3>
                <div className="back-charts-grid">
                    <DoughnutChartCard title="Saldo em Contas" chartData={contasData} showValues={showValues} />
                    <DoughnutChartCard title="Limite Total de Crédito" chartData={creditoData} showValues={showValues} />
                    <DoughnutChartCard title="Cheque Especial" chartData={chequeEspecialData} showValues={showValues} />
                    <DoughnutChartCard title="Limites de Cartão" chartData={cartoesData} showValues={showValues} />
                    <DoughnutChartCard title="Disponível em Cartões" chartData={cartoesDisponivelData} showValues={showValues} />
                </div>
                <div className="card-footer-info">Clique para voltar</div>
            </div>
        </div>
    );
};

const LatestTransactionsCard = ({ latestTransactions, showValues }) => {
    const [sortConfig, setSortConfig] = useState({ key: 'data', direction: 'desc' });

    const sortedTransactions = useMemo(() => {
        if (!latestTransactions || latestTransactions.length === 0) return [];
        let sortableItems = [...latestTransactions];
        if (sortConfig.key !== null) {
            sortableItems.sort((a, b) => {
                let valA = a[sortConfig.key];
                let valB = b[sortConfig.key];
                if (sortConfig.key === 'data') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                } else if (sortConfig.key === 'valor') {
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                }
                if (valA < valB) return sortConfig.direction === 'asc' ? -1 : 1;
                if (valA > valB) return sortConfig.direction === 'asc' ? 1 : -1;
                return 0;
            });
        }
        return sortableItems.slice(0, 10);
    }, [latestTransactions, sortConfig]);

    const handleSort = (key) => {
        let newDirection = 'asc';
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            newDirection = 'desc';
        }
        setSortConfig({ key, direction: newDirection });
    };

    const getSortIndicator = (columnKey) => {
        if (sortConfig.key !== columnKey) return <span style={{ opacity: 0.5, fontSize: '0.8em' }}> ↕</span>;
        return sortConfig.direction === 'asc' ? ' 🔼' : ' 🔽';
    };

    return (
        <div className="dashboard-card">
            <h3 className="table-title">Últimos Lançamentos</h3>
            <div className="table-wrapper">
                <table className="data-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th onClick={() => handleSort('tipo')} style={{ cursor: 'pointer' }}>Tipo{getSortIndicator('tipo')}</th>
                            <th onClick={() => handleSort('categoria_nome')} style={{ cursor: 'pointer' }}>Categoria{getSortIndicator('categoria_nome')}</th>
                            <th onClick={() => handleSort('data')} style={{ cursor: 'pointer' }}>Data{getSortIndicator('data')}</th>
                            <th onClick={() => handleSort('valor')} style={{ textAlign: 'right', cursor: 'pointer' }}>Valor{getSortIndicator('valor')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sortedTransactions.length > 0 ? sortedTransactions.map(t => (
                            <tr key={`${t.tipo}-${t.id}`}>
                                <td><span className={`transaction-icon ${t.tipo === 'receita' ? 'receita' : 'despesa'}`}>{t.tipo === 'receita' ? '💰' : '💸'}</span></td>
                                <td>{t.tipo === 'receita' ? 'Receita' : 'Despesa'}</td>
                                <td>{t.categoria_nome}</td>
                                <td>{new Date(t.data).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                                <td className={t.tipo === 'receita' ? 'text-success' : 'text-danger'} style={{ textAlign: 'right' }}>{showValues ? `${t.tipo === 'receita' ? '+' : '-'} ${formatCurrency(t.valor)}` : 'R$ ****'}</td>
                            </tr>
                        )) : (
                            <tr><td colSpan="5" className="empty-state">Nenhum lançamento recente.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            <div className="table-footer"><a href="/controleflex/lancamentos">Ver todos os lançamentos</a></div>
        </div>
    );
};

// =================================================================
// Componente Principal do Dashboard
// =================================================================
export default function Dashboard() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [dashboardData, setDashboardData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [showValues, setShowValues] = useState(true);
    const [period, setPeriod] = useState('this_month');

    const fetchDashboardData = useCallback(async (currentPeriod) => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user) { navigate('/'); return; }
        setUsuario(user);
        setIsLoading(true);
        const { inicio, fim } = getPeriodDates(currentPeriod);
        try {
            const response = await axios.get(`${API_BASE_URL}/dashboard.php`, {
                params: { usuario_id: user.id, inicio, fim }
            });
            setDashboardData(response.data);
        } catch (error) {
            console.error("Erro ao carregar dados do dashboard:", error);
        } finally {
            setIsLoading(false);
        }
    }, [navigate]);

    useEffect(() => {
        fetchDashboardData(period);
    }, [fetchDashboardData, period]);

    const toggleShowValues = () => setShowValues(!showValues);
    const handlePeriodChange = (e) => setPeriod(e.target.value);

    const processedData = useMemo(() => {
        if (!dashboardData) return null;

        const { kpi, realizados, saldos, latestTransactions, ...charts } = dashboardData;

        const contas = saldos?.bancarios || [];
        const cartoes = saldos?.cartoes || [];

        const totalSaldoContas = contas.reduce((acc, conta) => acc + parseFloat(conta.saldo || 0), 0);
        const totalChequeEspecialDisponivel = contas.reduce((acc, conta) => acc + parseFloat(conta.cheque_especial_disponivel || 0), 0);
        const totalCreditoDisponivelCartao = cartoes.reduce((acc, cartao) => acc + parseFloat(cartao.disponivel || 0), 0);

        const receitaPrevista = parseFloat(kpi?.total_receitas) || 0;
        const receitaRealizada = parseFloat(realizados?.receitas_realizadas) || 0;
        const despesaPrevista = parseFloat(kpi?.total_despesas) || 0;
        const despesaRealizada = parseFloat(realizados?.despesas_realizadas) || 0;

        // Cálculos melhorados de percentuais
        const percentualReceita = formatPercentage(calculatePercentage(receitaRealizada, receitaPrevista, 'receita'));
        const percentualDespesa = formatPercentage(calculatePercentage(despesaRealizada, despesaPrevista, 'despesa'));

        const saldoPrevisto = receitaPrevista - despesaPrevista;
        const saldoRealizado = receitaRealizada - despesaRealizada;

        // Cálculo melhorado do percentual de saldo
        const percentualSaldoRealizado = formatPercentage(calculatePercentage(saldoRealizado, saldoPrevisto, 'saldo'));

        // Cálculo adicional: percentual do saldo previsto (para comparação)
        const percentualSaldoPrevisto = saldoPrevisto !== 0 ? 100 : 0;

        return {
            receitaPrevista, receitaRealizada, despesaPrevista, despesaRealizada,
            percentualReceita, percentualDespesa,
            saldoPrevisto, saldoRealizado,
            percentualSaldoRealizado, percentualSaldoPrevisto,
            contas,
            cartoes,
            totalSaldoContas,
            latestTransactions: latestTransactions || [],
            charts: charts || {},
            totalChequeEspecialDisponivel,
            totalCreditoDisponivelCartao,
        };
    }, [dashboardData]);


    const investmentChartData = useMemo(() => {
        if (!dashboardData) return { labels: [], datasets: [] };
        return {
            labels: dashboardData.investmentChart?.labels || [],
            datasets: [
                { type: 'bar', label: 'Rendimento Mensal', data: dashboardData.investmentChart?.rendimentos || [], backgroundColor: 'rgba(255, 193, 7, 0.7)' },
                { type: 'line', label: 'Patrimônio Acumulado', data: dashboardData.investmentChart?.patrimonio || [], borderColor: 'rgba(0, 123, 255, 1)', backgroundColor: 'rgba(0, 123, 255, 0.2)', fill: true, tension: 0.1 },
            ],
        };
    }, [dashboardData]);

    const annualChartData = useMemo(() => {
        if (!dashboardData || !dashboardData.annualChart) return { labels: [], datasets: [] };
        return {
            labels: dashboardData.annualChart?.labels || [],
            datasets: [
                { label: 'Receitas', data: dashboardData.annualChart?.receitas || [], backgroundColor: 'rgba(52, 168, 83, 0.7)', borderColor: 'rgba(52, 168, 83, 1)', borderWidth: 1 },
                { label: 'Despesas', data: dashboardData.annualChart?.despesas || [], backgroundColor: 'rgba(234, 67, 53, 0.7)', borderColor: 'rgba(234, 67, 53, 1)', borderWidth: 1 },
            ],
        };
    }, [dashboardData]);

    const chartOptions = (title) => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: title, font: { size: 16 }, color: '#333' },
            tooltip: { callbacks: { label: (context) => showValues ? `${context.dataset.label}: ${formatCurrency(context.parsed.y)}` : `${context.dataset.label}: R$ ****` } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: (value) => showValues ? formatCurrency(value) : 'R$ ****' } } }
    });

    if (isLoading) {
        return <div className="page-container"><Spinner /></div>;
    }

    if (!processedData) {
        return <div className="page-container"><div className="error-message">Erro ao carregar dados do dashboard.</div></div>;
    }

    const { charts } = processedData;

    return (
        <div className="page-container dashboard-container">
            <div className="dashboard-header">
                <h1>Dashboard Financeiro</h1>
                <div className="header-controls">
                    <div className="period-filter">
                        <label htmlFor="period">Período:</label>
                        <select id="period" value={period} onChange={handlePeriodChange}>
                            <option value="this_month">Este Mês</option>
                            <option value="last_month">Último Mês</option>
                            <option value="next_month">Próximo Mês</option>
                            <option value="this_year">Este Ano</option>
                            <option value="last_year">Último Ano</option>
                            <option value="next_year">Próximo Ano</option>
                        </select>
                    </div>
                    <button className="toggle-values-button" onClick={toggleShowValues}>
                        {showValues ? 'Ocultar Valores' : 'Mostrar Valores'}
                    </button>
                </div>
            </div>

            <hr className="divider-line" />

            <div className="kpi-grid">
                <KpiCard title="Receita Prevista" value={processedData.receitaPrevista} theme="receita" showValues={showValues} percentage={null} />
                <KpiCard title="Despesa Prevista" value={processedData.despesaPrevista} theme="despesa" showValues={showValues} percentage={null} />
                <SaldoCard title="Saldo Previsto" value={processedData.saldoPrevisto} percentage={processedData.percentualSaldoPrevisto} showValues={showValues} />
                <KpiCard title="Receita Realizada" value={processedData.receitaRealizada} theme="receita" showValues={showValues} percentage={processedData.percentualReceita} />
                <KpiCard title="Despesa Realizada" value={processedData.despesaRealizada} theme="despesa" showValues={showValues} percentage={processedData.percentualDespesa} />
                <SaldoCard title="Saldo Realizado" value={processedData.saldoRealizado} percentage={processedData.percentualSaldoRealizado} showValues={showValues} />
            </div>

            <hr className="divider-line" />

            <div className="main-content-grid">
                <div className="main-panel">
                    <FinancialSummaryCard data={processedData} showValues={showValues} />
                    <div className="dashboard-card annual-chart-card">
                        <Bar options={chartOptions('Fluxo de Caixa Anual')} data={annualChartData} />
                    </div>
                    <div className="dashboard-card annual-chart-card">
                        <Bar options={chartOptions('Evolução Patrimonial')} data={investmentChartData} />
                    </div>
                    <LatestTransactionsCard latestTransactions={processedData.latestTransactions} showValues={showValues} />
                </div>

                <div className="side-panel">
                    <DoughnutChartCard title="Despesas por Categoria" chartData={charts.expensesByCategory || []} showValues={showValues} />
                    <DoughnutChartCard title="Receitas por Categoria" chartData={charts.incomesByCategory || []} showValues={showValues} />
                    <DoughnutChartCard title="Despesas por Familiar" chartData={charts.expensesByFamilyMember || []} showValues={showValues} />
                    <DoughnutChartCard title="Receitas por Familiar" chartData={charts.incomesByFamilyMember || []} showValues={showValues} />
                </div>
            </div>
        </div>
    );
}