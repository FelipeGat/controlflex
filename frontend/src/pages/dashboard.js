import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { Bar, Doughnut } from 'react-chartjs-2';

import './dashboard.css';
import { API_BASE_URL } from '../apiConfig';
import Spinner from '../components/Spinner';

// Componente genérico para gráficos de Pizza/Doughnut
const DoughnutChartCard = ({ title, chartData }) => {
    const data = {
        labels: chartData.map(c => c.nome), // Usando 'nome' como chave padrão
        datasets: [{
            data: chartData.map(c => c.total),
            backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#8A2BE2'],
            borderColor: '#fff',
            borderWidth: 2,
        }],
    };

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
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((acc, curr) => acc + curr, 0);
                        if (total === 0) return `${label}: R$ 0,00 (0%)`;
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: R$ ${value.toLocaleString('pt-BR', {minimumFractionDigits: 2})} (${percentage}%)`;
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


const KpiCard = ({ title, value, backgroundColor, icon, variation }) => (
    <div className="kpi-card" style={{ backgroundColor: backgroundColor }}>
        <div className="kpi-content">
            <span className="kpi-title">{title}</span>
            <span className="kpi-value">{`R$ ${parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}</span>
            {variation !== undefined && (
                <span className="kpi-variation">
                    {variation >= 0 ? '▲' : '▼'} {variation.toFixed(2)}%
                </span>
            )}
        </div>
        <div className="kpi-icon">{icon}</div>
    </div>
);

// ========= INÍCIO DA CORREÇÃO: LÓGICA DE ORDENAÇÃO E LINK CORRIGIDO =========
const LatestTransactionsCard = ({ latestTransactions }) => {
    const [transactions, setTransactions] = useState([]);
    const [sortConfig, setSortConfig] = useState({ key: 'data', direction: 'desc' });

    // Efeito para inicializar e ordenar os dados quando a prop é recebida
    useEffect(() => {
        let initialTransactions = [...latestTransactions];
        // Ordena os dados iniciais com base na configuração padrão (data, desc)
        initialTransactions.sort((a, b) => {
            const valA = new Date(a.data);
            const valB = new Date(b.data);
            if (valA < valB) return 1;
            if (valA > valB) return -1;
            return 0;
        });
        setTransactions(initialTransactions);
    }, [latestTransactions]);

    const handleSort = (key) => {
        let newDirection = 'asc';
        // Se a chave for a mesma, inverte a direção
        if (sortConfig.key === key && sortConfig.direction === 'asc') {
            newDirection = 'desc';
        }

        const sortedTransactions = [...transactions].sort((a, b) => {
            let valA = a[key];
            let valB = b[key];

            // Tratamento especial para data e valor
            if (key === 'data') {
                valA = new Date(valA);
                valB = new Date(valB);
            } else if (key === 'valor') {
                valA = parseFloat(valA);
                valB = parseFloat(valB);
            }

            if (valA < valB) {
                return newDirection === 'asc' ? -1 : 1;
            }
            if (valA > valB) {
                return newDirection === 'asc' ? 1 : -1;
            }
            return 0;
        });

        setTransactions(sortedTransactions);
        setSortConfig({ key, direction: newDirection });
    };

    // Função para obter o ícone de ordenação para o cabeçalho
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
                            <th onClick={() => handleSort('tipo')} style={{ cursor: 'pointer' }}>
                                Tipo{getSortIndicator('tipo')}
                            </th>
                            <th onClick={() => handleSort('categoria_nome')} style={{ cursor: 'pointer' }}>
                                Categoria{getSortIndicator('categoria_nome')}
                            </th>
                            <th onClick={() => handleSort('data')} style={{ cursor: 'pointer' }}>
                                Data{getSortIndicator('data')}
                            </th>
                            <th onClick={() => handleSort('valor')} style={{ textAlign: 'right', cursor: 'pointer' }}>
                                Valor{getSortIndicator('valor')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {transactions.length > 0 ? transactions.map(t => (
                            <tr key={`${t.tipo}-${t.id}`}>
                                <td>
                                    <span className={`transaction-icon ${t.tipo === 'receita' ? 'receita' : 'despesa'}`}>
                                        {t.tipo === 'receita' ? '💰' : '💸'}
                                    </span>
                                </td>
                                <td>{t.tipo === 'receita' ? 'Receita' : 'Despesa'}</td>
                                <td>{t.categoria_nome}</td>
                                <td>{new Date(t.data).toLocaleDateString('pt-BR', { timeZone: 'UTC' })}</td>
                                <td className={t.tipo === 'receita' ? 'text-success' : 'text-danger'} style={{ textAlign: 'right' }}>
                                    {`${t.tipo === 'receita' ? '+' : '-'} R$ ${parseFloat(t.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
                                </td>
                            </tr>
                        )) : (
                            <tr><td colSpan="5" className="empty-state">Nenhum lançamento recente.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>
            <div className="table-footer">
                {/* Link corrigido para o ambiente local */}
                <a href="/controleflex/lancamentos">Ver todos os lançamentos</a>
            </div>
        </div>
    );
};
// ========= FIM DA CORREÇÃO =========


// Componente Principal do Dashboard
export default function Dashboard() {
    const navigate = useNavigate();
    const [usuario, setUsuario] = useState(null);
    const [dashboardData, setDashboardData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [period, setPeriod] = useState('this_month'); // Alterado para 'Este Mês' como padrão
    
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

    if (isLoading) {
        return <div className="page-container"><Spinner /></div>;
    }

    if (!dashboardData) {
        return <div className="page-container">Não foi possível carregar os dados.</div>;
    }

    const { kpi, annualChart, investments, investmentChart, latestTransactions, ...charts } = dashboardData;

    const annualChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Fluxo de Caixa Anual' },
            tooltip: {
                callbacks: {
                    label: function(context) {
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
                    label: function(context) {
                        const label = context.dataset.label || '';
                        const value = context.parsed.y;
                        return `${label}: R$ ${value.toLocaleString('pt-BR')}`;
                    }
                }
            }
        },
        scales: { y: { beginAtZero: true } }
    };

    const investmentChartData = {
        labels: investmentChart.labels,
        datasets: [
            {
                type: 'bar',
                label: 'Rendimento Mensal',
                data: investmentChart.rendimentos,
                backgroundColor: 'rgba(255, 193, 7, 0.7)',
                yAxisID: 'y',
            },
            {
                type: 'line',
                label: 'Patrimônio Acumulado',
                data: investmentChart.patrimonio,
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                fill: true,
                tension: 0.1,
                yAxisID: 'y',
            },
        ],
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
                </div>
            </div>

            <div className="kpi-grid">
                <KpiCard title="Saldo do Período" value={kpi.saldo} backgroundColor="#007bff" icon="⚖️" variation={kpi.variacao_saldo} />
                <KpiCard title="Receitas" value={kpi.total_receitas} backgroundColor="#28a745" icon="💰" variation={kpi.variacao_receitas} />
                <KpiCard title="Despesas" value={kpi.total_despesas} backgroundColor="#dc3545" icon="💸" variation={kpi.variacao_despesas} />
                <KpiCard title="Total Investido" value={investments.total_investido} backgroundColor="#ffc107" icon="📈" />
            </div>

            <div className="main-content-grid">
                <div className="main-panel">
                    <div className="chart-card chart-large">
                        <Bar options={annualChartOptions} data={{
                            labels: annualChart.labels,
                            datasets: [
                                { label: 'Receitas', data: annualChart.receitas, backgroundColor: 'rgba(40, 167, 69, 0.7)' },
                                { label: 'Despesas', data: annualChart.despesas, backgroundColor: 'rgba(220, 53, 69, 0.7)' },
                            ],
                        }} />
                    </div>
                    {/* ÚLTIMOS LANÇAMENTOS MOVIDO PARA CÁ */}
                    <LatestTransactionsCard latestTransactions={latestTransactions} />
                    <div className="chart-card chart-large">
                        <Bar data={investmentChartData} options={investmentChartOptions} />
                    </div>
                </div>
                
                <div className="side-panel">
                    {/* NOVOS GRÁFICOS ADICIONADOS */}
                    <DoughnutChartCard title="Despesas por Categoria" chartData={charts.expensesByCategory} />
                    <DoughnutChartCard title="Receitas por Categoria" chartData={charts.incomesByCategory} />
                    <DoughnutChartCard title="Despesas por Familiar" chartData={charts.expensesByFamilyMember} />
                    <DoughnutChartCard title="Receitas por Familiar" chartData={charts.incomesByFamilyMember} />
                </div>
            </div>
        </div>
    );
}
