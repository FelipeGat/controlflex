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

const formatCurrency = (value) => {
    const numValue = parseFloat(value);
    if (isNaN(numValue)) return 'R$ 0,00';
    return `R$ ${numValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const colorPalette = [
    '#1f77b4', '#ff7f0e', '#2ca02c', '#9467bd', '#8c564b',
    '#e377c2', '#7f7f7f', '#bcbd22', '#17becf', '#aec7e8',
    '#ffbb78', '#98df8a', '#c5b0d5', '#c49c94', '#f7b6d2'
];

const generateColor = (str) => {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const index = Math.abs(hash % colorPalette.length);
    return colorPalette[index];
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
// Componentes Reutilizáveis
// =================================================================
const KpiCard = ({ title, value, percentage, theme, showValues }) => {
    const valueColor = theme === 'receita' ? 'kpi-value-positive' : 'kpi-value-negative';
    const percentageColor = parseFloat(percentage) >= 100 ? 'kpi-percent-positive' : 'kpi-percent-negative';
    return (
        <div className={`kpi-card ${theme}`}>
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

const SaldoMeter = ({ title, items, showValues, color, type }) => {
    const [currentIndex, setCurrentIndex] = useState(-1);

    const handleInteraction = () => {
        console.log(`Clique processado em SaldoMeter. Itens: ${items?.length}`);

        if (!items || items.length === 0) {
            return;
        }

        setCurrentIndex(prevIndex => {
            const nextIndex = prevIndex + 1;
            if (nextIndex >= items.length) {
                return -1;
            }

            return nextIndex;
        });
    };

    useEffect(() => {
        setCurrentIndex(-1);
    }, [items]);

    const isTotalView = currentIndex === -1 || items.length === 0;
    const currentItem = isTotalView ? null : items[currentIndex];

    const totalValue = useMemo(() => {
        if (!items) return 0;
        return items.reduce((acc, item) => {
            const value = type === 'conta'
                ? parseFloat(item.saldo)
                : (parseFloat(item.limite_credito) - parseFloat(item.credito_utilizado));
            return acc + (isNaN(value) ? 0 : value);
        }, 0);
    }, [items, type]);

    const displayValue = isTotalView
        ? totalValue
        : (type === 'conta'
            ? parseFloat(currentItem.saldo)
            : (parseFloat(currentItem.limite_credito) - parseFloat(currentItem.credito_utilizado)));

    const displayTitle = isTotalView ? title : currentItem.nome;
    const displayColor = isTotalView ? color : (type === 'conta' ? (displayValue >= 0 ? generateColor(currentItem.nome) : '#dc3545') : generateColor(currentItem.nome));

    const data = {
        datasets: [{
            data: [100],
            backgroundColor: [displayColor],
            circumference: 360,
            borderWidth: 0,
        }]
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '80%',
        plugins: { tooltip: { enabled: false }, legend: { display: false } }
    };

    const formattedValue = showValues ? formatCurrency(displayValue) : 'R$ ****';
    const valueClassName = `saldo-meter-value ${formattedValue.length > 10 ? 'extra-small-text' : ''}`;

    return (
        // A classe 'saldo-meter-interactive' agora serve mais para o CSS (cursor: pointer)
        <div className="saldo-meter-card saldo-meter-interactive">
            <h3 className="chart-title" onClick={handleInteraction}>{displayTitle}</h3>
            <div className="saldo-meter-chart-container" onClick={handleInteraction}>
                <Doughnut data={data} options={options} />
                <div className={valueClassName} style={{ color: displayColor }}>
                    {formattedValue}
                </div>
            </div>
            <div className="pagination-dots">
                {items && items.length > 1 && (
                    <>
                        <div className={`dot ${isTotalView ? 'active' : ''}`}></div>
                        {items.map((_, index) => (
                            <div key={index} className={`dot ${index === currentIndex ? 'active' : ''}`}></div>
                        ))}
                    </>
                )}
            </div>
        </div>
    );
};

const CardDetailsMeter = ({ title, items, showValues }) => {
    const [currentIndex, setCurrentIndex] = useState(-1);

    const handleInteraction = () => {
        console.log(`Clique processado em CardDetailsMeter. Itens: ${items?.length}`);

        if (!items || items.length === 0) {
            return;
        }

        setCurrentIndex(prevIndex => {
            const nextIndex = prevIndex + 1;

            if (nextIndex >= items.length) {
                return -1;
            }

            return nextIndex;
        });
    };

    useEffect(() => {
        setCurrentIndex(-1);
    }, [items]);

    const isTotalView = currentIndex === -1 || items.length === 0;
    const currentItem = isTotalView ? null : items[currentIndex];

    const totals = useMemo(() => {
        if (!items) return { limite: 0, utilizado: 0 };
        return items.reduce((acc, item) => {
            acc.limite += parseFloat(item.limite_credito) || 0;
            acc.utilizado += parseFloat(item.credito_utilizado) || 0;
            return acc;
        }, { limite: 0, utilizado: 0 });
    }, [items]);

    const limite = isTotalView ? totals.limite : parseFloat(currentItem.limite_credito);
    const utilizado = isTotalView ? totals.utilizado : parseFloat(currentItem.credito_utilizado);
    const disponivel = limite - utilizado;

    const displayTitle = isTotalView ? title : currentItem.nome;
    const displayColor = generateColor(isTotalView ? 'total' : currentItem.nome);

    return (
        <div className="saldo-meter-card saldo-meter-interactive">
            <h3 className="chart-title" style={{ color: displayColor }} onClick={handleInteraction}>{displayTitle}</h3>
            <div className="card-details-meter" onClick={handleInteraction}>
                <div className="card-detail-row">
                    <span className="label">Limite: </span>
                    <span className="value">{showValues ? formatCurrency(limite) : 'R$ ****'}</span>
                </div>
                <div className="card-detail-row">
                    <span className="label">Utilizado: </span>
                    <span className="value" style={{ color: '#ea4335' }}>{showValues ? formatCurrency(utilizado) : 'R$ ****'}</span>
                </div>
                <div className="card-detail-row">
                    <span className="label">Disponível: </span>
                    <span className="value" style={{ color: '#34a853' }}>{showValues ? formatCurrency(disponivel) : 'R$ ****'}</span>
                </div>
            </div>
            <div className="pagination-dots">
                {items && items.length > 1 && (
                    <>
                        <div className={`dot ${isTotalView ? 'active' : ''}`}></div>
                        {items.map((_, index) => (
                            <div key={index} className={`dot ${index === currentIndex ? 'active' : ''}`}></div>
                        ))}
                    </>
                )}
            </div>
        </div>
    );
};

const SaldosBarChart = ({ contas, cartoes, showValues }) => {
    const chartData = useMemo(() => {
        const allSaldos = [
            ...contas.map(c => ({ ...c, tipo: 'conta' })),
            ...cartoes.map(c => ({ ...c, tipo: 'cartao', saldo: parseFloat(c.limite_credito) - parseFloat(c.credito_utilizado) }))
        ];

        return {
            labels: allSaldos.map(item => item.nome),
            datasets: [{
                label: 'Saldo',
                data: allSaldos.map(item => showValues ? item.saldo : 0),
                backgroundColor: allSaldos.map(item => {
                    const saldo = parseFloat(item.saldo);
                    if (saldo < 0) return 'rgba(234, 67, 53, 0.7)';
                    return generateColor(item.nome);
                }),
                borderColor: allSaldos.map(item => {
                    const saldo = parseFloat(item.saldo);
                    if (saldo < 0) return 'rgb(234, 67, 53)';
                    return generateColor(item.nome);
                }),
                borderWidth: 1,
            }],
        };
    }, [contas, cartoes, showValues]);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Detalhes dos Saldos', font: { size: 16 }, color: '#333' },
            tooltip: { callbacks: { label: (context) => `Saldo: ${formatCurrency(context.parsed.x)}` } }
        },
        scales: { x: { beginAtZero: true, ticks: { callback: (value) => formatCurrency(value) } } }
    };

    return (
        <div className="saldos-bar-chart-container">
            <Bar data={chartData} options={options} />
        </div>
    );
};

const InfoCard = ({ title, value, showValues, theme }) => (
    <div className={`dashboard-card info-card ${theme}`}>
        <span className="info-card-title">{title}</span>
        <span className="info-card-value">{showValues ? formatCurrency(value) : 'R$ ****'}</span>
    </div>
);

const DoughnutChartCard = ({ title, chartData }) => {
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
                        return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                    }
                }
            }
        },
    }), []);

    return (
        <div className="dashboard-card">
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

const LatestTransactionsCard = ({ latestTransactions }) => {
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
                                <td className={t.tipo === 'receita' ? 'text-success' : 'text-danger'} style={{ textAlign: 'right' }}>{`${t.tipo === 'receita' ? '+' : '-'} ${formatCurrency(t.valor)}`}</td>
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
        const { kpi, realizados, saldos, lastMonth, nextMonth, latestTransactions, ...charts } = dashboardData;

        const receitaPrevista = parseFloat(kpi?.total_receitas) || 0;
        const receitaRealizada = parseFloat(realizados?.receitas_realizadas) || 0;
        const despesaPrevista = parseFloat(kpi?.total_despesas) || 0;
        const despesaRealizada = parseFloat(realizados?.despesas_realizadas) || 0;

        const percentualReceita = receitaPrevista > 0 ? ((receitaRealizada / receitaPrevista) * 100).toFixed(1) : "0.0";
        const percentualDespesa = despesaPrevista > 0 ? ((despesaRealizada / despesaPrevista) * 100).toFixed(1) : "0.0";

        const saldoPrevisto = receitaPrevista - despesaPrevista;
        const saldoRealizado = receitaRealizada - despesaRealizada;

        const percentualSaldoPrevisto = receitaPrevista > 0 ? ((saldoPrevisto / receitaPrevista) * 100).toFixed(1) : "0.0";
        const percentualSaldoRealizado = receitaRealizada > 0 ? ((saldoRealizado / receitaRealizada) * 100).toFixed(1) : "0.0";

        const totalSaldoContas = saldos?.bancarios?.reduce((acc, conta) => acc + parseFloat(conta.saldo), 0) || 0;

        return {
            receitaPrevista, receitaRealizada, despesaPrevista, despesaRealizada,
            percentualReceita, percentualDespesa,
            saldoPrevisto, saldoRealizado,
            percentualSaldoPrevisto, percentualSaldoRealizado,
            contas: saldos?.bancarios || [],
            cartoes: saldos?.cartoes || [],
            totalSaldoContas,
            pagoUltimoMes: parseFloat(lastMonth?.pago) || 0,
            recebidoUltimoMes: parseFloat(lastMonth?.recebido) || 0,
            aPagarProximoMes: parseFloat(nextMonth?.previsto_despesas) || 0,
            aReceberProximoMes: parseFloat(nextMonth?.previsto_receitas) || 0,
            latestTransactions: latestTransactions || [],
            charts: charts || {}
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
            tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}` } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: (value) => formatCurrency(value) } } }
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
                            <option value="today">Hoje</option>
                            <option value="yesterday">Ontem</option>
                            <option value="tomorrow">Amanhã</option>
                            <option value="this_week">Esta Semana</option>
                            <option value="last_week">Última Semana</option>
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

            <div className="kpi-grid">
                <KpiCard title="Receita Prevista" value={processedData.receitaPrevista} theme="receita" showValues={showValues} percentage={null} />
                <KpiCard title="Despesa Prevista" value={processedData.despesaPrevista} theme="despesa" showValues={showValues} percentage={null} />
                <SaldoCard title="Saldo Previsto" value={processedData.saldoPrevisto} percentage={processedData.percentualSaldoPrevisto} showValues={showValues} />
                <KpiCard title="Receita Realizada" value={processedData.receitaRealizada} theme="receita" showValues={showValues} percentage={null} />
                <KpiCard title="Despesa Realizada" value={processedData.despesaRealizada} theme="despesa" showValues={showValues} percentage={null} />
                <SaldoCard title="Saldo Realizado" value={processedData.saldoRealizado} percentage={processedData.percentualSaldoRealizado} showValues={showValues} />
            </div>

            <div className="dashboard-card">
                <div className="saldos-container">
                    {/* Container da esquerda com os 3 gráficos */}
                    <div className="saldo-meters-grid">
                        {/* Gráfico de Saldo em Contas (ocupa a linha inteira) */}
                        <SaldoMeter
                            title="Saldo Total em Contas"
                            items={processedData.contas}
                            showValues={showValues}
                            color={processedData.totalSaldoContas >= 0 ? '#28a745' : '#dc3545'}
                            type="conta"
                        />
                        {/* Linha para os dois gráficos de cartão, que ficarão lado a lado */}
                        <div className="card-meters-row">
                            <SaldoMeter
                                title="Crédito Total Disponível"
                                items={processedData.cartoes}
                                showValues={showValues}
                                color="#1a73e8"
                                type="cartao"
                            />
                            <CardDetailsMeter
                                title="Detalhes do Cartão"
                                items={processedData.cartoes}
                                showValues={showValues}
                            />
                        </div>
                    </div>

                    {/* Container da direita com o gráfico de barras */}
                    <SaldosBarChart
                        contas={processedData.contas}
                        cartoes={processedData.cartoes}
                        showValues={showValues}
                    />
                </div>
            </div>

            <div className="info-grid">
                <InfoCard title="Valor Pago (Último Mês)" value={processedData.pagoUltimoMes} showValues={showValues} theme="despesa" />
                <InfoCard title="Valor Recebido (Último Mês)" value={processedData.recebidoUltimoMes} showValues={showValues} theme="receita" />
                <InfoCard title="A Pagar (Proximo Mês)" value={processedData.aPagarProximoMes} showValues={showValues} theme="despesa" />
                <InfoCard title="A Receber (Proximo Mês)" value={processedData.aReceberProximoMes} showValues={showValues} theme="receita" />
            </div>

            <div className="main-content-grid">
                <div className="main-panel">
                    <div className="dashboard-card annual-chart-card">
                        <Bar options={chartOptions('Fluxo de Caixa Anual')} data={annualChartData} />
                    </div>
                    <div className="dashboard-card annual-chart-card">
                        <Bar options={chartOptions('Evolução Patrimonial (Anual)')} data={investmentChartData} />
                    </div>
                    <LatestTransactionsCard latestTransactions={processedData.latestTransactions} />
                </div>

                <div className="side-panel">
                    <DoughnutChartCard title="Despesas por Categoria" chartData={charts.expensesByCategory || []} />
                    <DoughnutChartCard title="Receitas por Categoria" chartData={charts.incomesByCategory || []} />
                    <DoughnutChartCard title="Despesas por Familiar" chartData={charts.expensesByFamilyMember || []} />
                    <DoughnutChartCard title="Receitas por Familiar" chartData={charts.incomesByFamilyMember || []} />
                </div>
            </div>
        </div>
    );
}
