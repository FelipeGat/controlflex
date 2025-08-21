import React, { useState, useEffect, useCallback, useMemo } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { Bar, Doughnut } from 'react-chartjs-2';
import './dashboard.css'; // Certifique-se de que o caminho do CSS está correto
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

// =================================================================
// Componentes Reutilizáveis
// =================================================================

const DoughnutChartCard = ({ title, chartData }) => {
    const data = useMemo(() => {
        return {
            labels: chartData.map(c => c.nome),
            datasets: [{
                data: chartData.map(c => c.total),
                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#8A2BE2'],
                borderColor: '#fff',
                borderWidth: 2,
            }],
        };
    }, [chartData]);

    const options = {
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
                    label: function (context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
                        if (total === 0) return `${label}: R$ 0,00 (0%)`;
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: R$ ${value.toLocaleString('pt-BR', { minimumFractionDigits: 2 })} (${percentage}%)`;
                    }
                }
            }
        },
    };

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

const KpiCard = ({ title, value, backgroundColor, icon, variation, kpiRealizado, previsto }) => {
    // Calcula o percentual de realização
    const percentualRealizado = useMemo(() => {
        // Se o previsto for 0, o percentual também é 0 para evitar divisão por zero
        if (isNaN(parseFloat(previsto)) || parseFloat(previsto) === 0) {
            return '0.00';
        }
        // Se o realizado não for um número válido, o percentual é 0
        if (isNaN(parseFloat(kpiRealizado))) {
            return '0.00';
        }
        const calculatedPercentage = ((parseFloat(kpiRealizado) / parseFloat(previsto)) * 100);
        return calculatedPercentage.toFixed(2);
    }, [kpiRealizado, previsto]);

    return (
        <div className="kpi-card" style={{ backgroundColor: backgroundColor }}>
            <div className="kpi-content">
                <span className="kpi-title">{title}</span>
                <span className="kpi-value">{`R$ ${parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}</span>

                {/* Condição para exibir a variação (apenas em "Previstos") ou o percentual de realização (em "Realizados") */}
                {/* Se a prop 'variation' existir, exibe a variação */}
                {variation !== undefined && (
                    <span className="kpi-variation">
                        {variation >= 0 ? '▲' : '▼'} {variation.toFixed(2)}%
                    </span>
                )}

                {/* Se a prop 'kpiRealizado' existir e o título não for de 'Total Investido', exibe o percentual de realização */}
                {kpiRealizado !== undefined && title !== 'Total Investido' && (
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
        // Separa as transações por tipo (receita e despesa)
        const receitas = latestTransactions.filter(t => t.tipo === 'receita');
        const despesas = latestTransactions.filter(t => t.tipo === 'despesa');

        // Ordena cada lista separadamente por data, da mais recente para a mais antiga
        const sortedReceitas = receitas.sort((a, b) => new Date(b.data) - new Date(a.data));
        const sortedDespesas = despesas.sort((a, b) => new Date(b.data) - new Date(a.data));

        // Pega os 5 primeiros de cada lista
        const ultimasReceitas = sortedReceitas.slice(0, 5);
        const ultimasDespesas = sortedDespesas.slice(0, 5);

        // Combina as duas listas e aplica a ordenação do usuário, se houver
        const combinedTransactions = [...ultimasReceitas, ...ultimasDespesas];

        let sortableItems = combinedTransactions;

        if (sortConfig.key !== null) {
            sortableItems = [...combinedTransactions].sort((a, b) => {
                let valA = a[sortConfig.key];
                let valB = b[sortConfig.key];

                if (sortConfig.key === 'data') {
                    valA = new Date(valA);
                    valB = new Date(valB);
                } else if (sortConfig.key === 'valor') {
                    valA = parseFloat(valA);
                    valB = parseFloat(valB);
                }

                if (valA < valB) {
                    return sortConfig.direction === 'asc' ? -1 : 1;
                }
                if (valA > valB) {
                    return sortConfig.direction === 'asc' ? 1 : -1;
                }
                return 0;
            });
        }
        return sortableItems;
    }, [latestTransactions, sortConfig]);

    const handleSort = (key) => {
        let newDirection = 'asc';
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            newDirection = 'desc';
        }
        setSortConfig({ key, direction: newDirection });
    };

    const getSortIndicator = (columnKey) => {
        if (sortConfig.key !== columnKey) {
            return <span style={{ opacity: 0.5, fontSize: '0.8em' }}> ↕</span>;
        }
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
                                <td className={t.tipo === 'receita' ? 'text-success' : 'text-danger'} style={{ textAlign: 'right' }}>{`${t.tipo === 'receita' ? '+' : '-'} R$ ${parseFloat(t.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}</td>
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

    // Estados para controlar a visibilidade dos blocos
    const [showPrevistos, setShowPrevistos] = useState(true);
    const [showRealizados, setShowRealizados] = useState(true);

    const fetchDashboardData = useCallback(async (selectedPeriod) => {
        if (!usuario) return;
        setIsLoading(true);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        let inicio, fim;
        switch (selectedPeriod) {
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
                break;
        }
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

    const handlePeriodChange = (e) => {
        setPeriod(e.target.value);
    };

    const annualChartData = useMemo(() => {
        if (!dashboardData || !dashboardData.annualChart) return { labels: [], datasets: [] };
        return {
            labels: dashboardData.annualChart.labels,
            datasets: [
                { label: 'Receitas', data: dashboardData.annualChart.receitas, backgroundColor: 'rgba(40, 167, 69, 0.7)' },
                { label: 'Despesas', data: dashboardData.annualChart.despesas, backgroundColor: 'rgba(220, 53, 69, 0.7)' },
            ],
        };
    }, [dashboardData]);

    const investmentChartData = useMemo(() => {
        if (!dashboardData || !dashboardData.investmentChart) return { labels: [], datasets: [] };
        return {
            labels: dashboardData.investmentChart.labels,
            datasets: [
                {
                    type: 'bar',
                    label: 'Rendimento Mensal',
                    data: dashboardData.investmentChart.rendimentos,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'Patrimônio Acumulado',
                    data: dashboardData.investmentChart.patrimonio,
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    fill: true,
                    tension: 0.1,
                    yAxisID: 'y',
                },
            ],
        };
    }, [dashboardData]);


    const annualChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Fluxo de Caixa Anual' },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: R$ ${value.toLocaleString('pt-BR')}`;
                    }
                }
            }
        }
    };

    const investmentChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Evolução Patrimonial (Anual)' },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: R$ ${value.toLocaleString('pt-BR')}`;
                    }
                }
            }
        },
        scales: { y: { beginAtZero: true } }
    };

    if (isLoading) {
        return <div className="page-container"><Spinner /></div>;
    }

    if (!dashboardData) {
        return <div className="page-container">Não foi possível carregar os dados.</div>;
    }

    // Desestrutura os novos dados 'realizados' da API
    const { kpi, realizados, annualChart, investments, investmentChart, latestTransactions, ...charts } = dashboardData;

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
                </div>
            </div>

            {/* SEÇÃO PREVISTOS */}
            <div className="dashboard-section">
                <h3 className="section-title" onClick={() => setShowPrevistos(!showPrevistos)}>
                    Previstos
                    <span className={`toggle-icon ${showPrevistos ? 'up' : ''}`}>▼</span>
                </h3>
                <div className={`kpi-grid ${showPrevistos ? '' : 'collapsed'}`}>
                    <KpiCard title="Saldo do Período" value={kpi.saldo} backgroundColor="#007bff" icon="⚖️" variation={kpi.variacao_saldo} />
                    <KpiCard title="Receitas" value={kpi.total_receitas} backgroundColor="#28a745" icon="💰" variation={kpi.variacao_receitas} />
                    <KpiCard title="Despesas" value={kpi.total_despesas} backgroundColor="#dc3545" icon="💸" variation={kpi.variacao_despesas} />
                    <KpiCard title="Total Investido" value={investments.total_investido} backgroundColor="#ffc107" icon="📈" />
                </div>
            </div>

            {/* SEÇÃO REALIZADOS */}
            <div className="dashboard-section">
                <h3 className="section-title" onClick={() => setShowRealizados(!showRealizados)}>
                    Realizados
                    <span className={`toggle-icon ${showRealizados ? 'up' : ''}`}>▼</span>
                </h3>
                <div className={`kpi-grid ${showRealizados ? '' : 'collapsed'}`}>
                    <KpiCard title="Saldo Realizado" value={realizados.saldo_realizado} backgroundColor="#007bff" icon="💼" kpiRealizado={realizados.saldo_realizado} previsto={kpi.saldo} />
                    <KpiCard title="Receitas Realizadas" value={realizados.receitas_realizadas} backgroundColor="#28a745" icon="✅" kpiRealizado={realizados.receitas_realizadas} previsto={kpi.total_receitas} />
                    <KpiCard title="Despesas Pagas" value={realizados.despesas_realizadas} backgroundColor="#dc3545" icon="📄" kpiRealizado={realizados.despesas_realizadas} previsto={kpi.total_despesas} />
                </div>
            </div>

            <div className="main-content-grid">
                <div className="main-panel">
                    <div className="chart-card chart-large">
                        <Bar options={annualChartOptions} data={annualChartData} />
                    </div>
                    <LatestTransactionsCard latestTransactions={latestTransactions} />
                    <div className="chart-card chart-large">
                        <Bar data={investmentChartData} options={investmentChartOptions} />
                    </div>
                </div>

                <div className="side-panel">
                    <DoughnutChartCard title="Despesas por Categoria" chartData={charts.expensesByCategory} />
                    <DoughnutChartCard title="Receitas por Categoria" chartData={charts.incomesByCategory} />
                    <DoughnutChartCard title="Despesas por Familiar" chartData={charts.expensesByFamilyMember} />
                    <DoughnutChartCard title="Receitas por Familiar" chartData={charts.incomesByFamilyMember} />
                </div>
            </div>
        </div>
    );
}