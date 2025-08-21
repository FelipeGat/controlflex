import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { Bar, Doughnut } from 'react-chartjs-2';
import './dashboard.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

// =================================================================
// Funções Auxiliares
// =================================================================

const formatCurrency = (value) => {
    return `R$ ${parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
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

const DoughnutChartCard = ({ title, chartData }) => {
    const data = useMemo(() => {
        return {
            labels: chartData.map(c => c.nome),
            datasets: [{
                data: chartData.map(c => parseFloat(c.total)),
                backgroundColor: ['#1a73e8', '#34a853', '#ea4335', '#fbbc05', '#4285f4', '#007bff', '#dc3545', '#ffc107'],
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
        <div className="chart-card chart-card-doughnut">
            <h3 className="chart-title">{title}</h3>
            {chartData && chartData.length > 0 ? (
                <div className="chart-container">
                    <Doughnut data={data} options={options} />
                </div>
            ) : (
                <div className="empty-chart">Nenhum dado para exibir no período.</div>
            )}
        </div>
    );
};

// Componente KpiCard com lógica para esconder valores
const KpiCard = ({ title, value, theme, icon, variation, kpiRealizado, previsto, showValues }) => {
    const percentualRealizado = useMemo(() => {
        const pValue = parseFloat(previsto);
        const rValue = parseFloat(kpiRealizado);
        if (isNaN(pValue) || pValue === 0 || isNaN(rValue)) {
            return '0.00';
        }
        return ((rValue / pValue) * 100).toFixed(2);
    }, [kpiRealizado, previsto]);

    return (
        <div className={`kpi-card ${theme}`}>
            <div className="kpi-content">
                <span className="kpi-title">{title}</span>
                <span className="kpi-value">
                    {showValues ? formatCurrency(value) : '***'}
                </span>
                {variation !== undefined && showValues && (
                    <span className={`kpi-variation ${variation >= 0 ? 'positive' : 'negative'}`}>
                        {variation >= 0 ? '▲' : '▼'} {variation.toFixed(2)}%
                    </span>
                )}
                {kpiRealizado !== undefined && title !== 'Total Investido' && showValues && (
                    <span className="kpi-realizado-percentual">
                        {percentualRealizado}% realizado
                    </span>
                )}
            </div>
            <div className="kpi-icon">{icon}</div>
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
        <div className="transactions-card">
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

// =================================================================
// Componente Principal do Dashboard
// =================================================================

export default function Dashboard() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [dashboardData, setDashboardData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [period, setPeriod] = useState('this_month');
    const [showPrevistos, setShowPrevistos] = useState(true);
    const [showRealizados, setShowRealizados] = useState(true);
    const [showBank, setShowBank] = useState(true);
    const [showCreditCard, setShowCreditCard] = useState(true);
    const [showValues, setShowValues] = useState(true);

    const fetchDashboardData = useCallback(async (selectedPeriod) => {
        if (!usuario) return;
        setIsLoading(true);
        const { inicio, fim } = getPeriodDates(selectedPeriod);
        try {
            const response = await axios.get(`${API_BASE_URL}/dashboard.php`, {
                params: { usuario_id: usuario.id, inicio, fim }
            });
            setDashboardData(response.data);
        } catch (error) {
            console.error("Erro ao carregar dados do dashboard:", error);
        } finally {
            setIsLoading(false);
        }
    }, [usuario]);

    useEffect(() => {
        const user = JSON.parse(localStorage.getItem('usuarioLogado'));
        if (!user) navigate('/');
        else setUsuario(user);
    }, [navigate]);

    useEffect(() => {
        if (usuario) {
            fetchDashboardData(period);
        }
    }, [usuario, period, fetchDashboardData]);

    const handlePeriodChange = (e) => setPeriod(e.target.value);
    const toggleShowValues = () => setShowValues(!showValues);

    const chartData = useMemo(() => {
        if (!dashboardData) return { annualChart: {}, investmentChart: {} };
        return {
            annualChart: {
                labels: dashboardData.annualChart?.labels || [],
                datasets: [
                    { label: 'Receitas', data: dashboardData.annualChart?.receitas || [], backgroundColor: 'rgba(40, 167, 69, 0.7)' },
                    { label: 'Despesas', data: dashboardData.annualChart?.despesas || [], backgroundColor: 'rgba(220, 53, 69, 0.7)' },
                ],
            },
            investmentChart: {
                labels: dashboardData.investmentChart?.labels || [],
                datasets: [
                    {
                        type: 'bar',
                        label: 'Rendimento Mensal',
                        data: dashboardData.investmentChart?.rendimentos || [],
                        backgroundColor: 'rgba(255, 193, 7, 0.7)',
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Patrimônio Acumulado',
                        data: dashboardData.investmentChart?.patrimonio || [],
                        borderColor: 'rgba(0, 123, 255, 1)',
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        fill: true,
                        tension: 0.1,
                        yAxisID: 'y',
                    },
                ],
            },
        };
    }, [dashboardData]);

    const annualChartOptions = useMemo(() => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Fluxo de Caixa Anual' },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: ${formatCurrency(value)}`;
                    }
                }
            }
        },
    }), []);

    const investmentChartOptions = useMemo(() => ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Evolução Patrimonial (Anual)' },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: ${formatCurrency(value)}`;
                    }
                }
            }
        },
        scales: { y: { beginAtZero: true } }
    }), []);

    if (isLoading) {
        return <div className="page-container"><Spinner /></div>;
    }

    if (!dashboardData) {
        return <div className="page-container">Não foi possível carregar os dados.</div>;
    }

    const { kpi, realizados, latestTransactions, saldos, ...charts } = dashboardData;

    // Lógica para determinar a classe de tema para os cards de saldo
    const getBalanceTheme = (value) => {
        const valueNum = parseFloat(value);
        if (valueNum > 0) return 'positive';
        if (valueNum < 0) return 'negative';
        return 'neutral';
    };

    return (
        <div className="page-container dashboard-container">
            <div className="dashboard-header">
                <div className="period-filter">
                    <label htmlFor="period">Mostrar período:</label>
                    <select id="period" value={period} onChange={handlePeriodChange} className="form-control">
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
                    <button className="toggle-values-button" onClick={toggleShowValues}>
                        {showValues ? 'Ocultar valores' : 'Mostrar valores'}
                    </button>
                </div>
            </div>

            <div className="dashboard-section">
                <h3 className="section-title" onClick={() => setShowPrevistos(!showPrevistos)}>
                    Previstos <span className={`toggle-icon ${showPrevistos ? 'up' : 'down'}`}>▼</span>
                </h3>
                <div className={`kpi-grid ${showPrevistos ? '' : 'collapsed'}`}>
                    <KpiCard title="Saldo do Período" value={kpi?.saldo || 0} theme="primary" icon="⚖️" variation={kpi?.variacao_saldo} showValues={showValues} />
                    <KpiCard title="Receitas" value={kpi?.total_receitas || 0} theme="success" icon="💰" variation={kpi?.variacao_receitas} showValues={showValues} />
                    <KpiCard title="Despesas" value={kpi?.total_despesas || 0} theme="danger" icon="💸" variation={kpi?.variacao_despesas} showValues={showValues} />
                    <KpiCard title="Total Investido" value={dashboardData.investments?.total_investido || 0} theme="warning" icon="📈" showValues={showValues} />
                </div>
            </div>

            <div className="dashboard-section">
                <h3 className="section-title" onClick={() => setShowRealizados(!showRealizados)}>
                    Realizados <span className={`toggle-icon ${showRealizados ? 'up' : 'down'}`}>▼</span>
                </h3>
                <div className={`kpi-grid ${showRealizados ? '' : 'collapsed'}`}>
                    <KpiCard title="Saldo Realizado" value={realizados?.saldo_realizado || 0} theme="primary" icon="💼" kpiRealizado={realizados?.saldo_realizado} previsto={kpi?.saldo} showValues={showValues} />
                    <KpiCard title="Receitas Realizadas" value={realizados?.receitas_realizadas || 0} theme="success" icon="✅" kpiRealizado={realizados?.receitas_realizadas} previsto={kpi?.total_receitas} showValues={showValues} />
                    <KpiCard title="Despesas Pagas" value={realizados?.despesas_realizadas || 0} theme="danger" icon="📄" kpiRealizado={realizados?.despesas_realizadas} previsto={kpi?.total_despesas} showValues={showValues} />
                </div>
            </div>

            <div className="dashboard-section">
                <h3 className="section-title" onClick={() => setShowBank(!showBank)}>
                    Saldos Bancários <span className={`toggle-icon ${showBank ? 'up' : 'down'}`}>▼</span>
                </h3>
                <div className={`kpi-grid ${showBank ? '' : 'collapsed'}`}>
                    {saldos?.bancarios?.map(banco => (
                        <KpiCard
                            key={banco.id}
                            title={banco.nome}
                            value={banco.saldo}
                            icon="🏦"
                            showValues={showValues}
                            theme={getBalanceTheme(banco.saldo)}
                        />
                    ))}
                </div>
            </div>

            <div className="dashboard-section">
                <h3 className="section-title" onClick={() => setShowCreditCard(!showCreditCard)}>
                    Saldos Cartões <span className={`toggle-icon ${showCreditCard ? 'up' : 'down'}`}>▼</span>
                </h3>
                <div className={`kpi-grid ${showCreditCard ? '' : 'collapsed'}`}>
                    {saldos?.cartoes?.map(cartao => {
                        const saldoCartao = parseFloat(cartao.limite_credito) - parseFloat(cartao.credito_utilizado);
                        return (
                            <KpiCard
                                key={cartao.id}
                                title={cartao.nome}
                                value={saldoCartao}
                                icon="💳"
                                showValues={showValues}
                                theme={getBalanceTheme(saldoCartao)}
                            >
                                <div className="credit-info-container">
                                    <span className="credit-info-item">
                                        {showValues ? `Limite: ${formatCurrency(cartao.limite_credito)}` : 'Limite: ***'}
                                    </span>
                                    <span className="credit-info-item">
                                        {showValues ? `Utilizado: ${formatCurrency(cartao.credito_utilizado)}` : 'Utilizado: ***'}
                                    </span>
                                </div>
                            </KpiCard>
                        );
                    })}
                </div>
            </div>

            <div className="main-content-grid">
                <div className="main-panel">
                    <div className="chart-card chart-large">
                        <Bar options={annualChartOptions} data={chartData.annualChart} />
                    </div>
                    <LatestTransactionsCard latestTransactions={latestTransactions} />
                    <div className="chart-card chart-large">
                        <Bar data={chartData.investmentChart} options={investmentChartOptions} />
                    </div>
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