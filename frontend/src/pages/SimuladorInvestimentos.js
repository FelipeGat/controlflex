import React, { useState, useMemo, useCallback } from 'react';
import './SimuladorInvestimentos.css';

// --- IMPORTAÇÕES DO RECHARTS ---
import {
    LineChart as RechartsLineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
    PieChart as RechartsPieChart,
    Pie,
    Cell
} from 'recharts';

const SIMULATOR_STORAGE_KEY = 'simulador_params';

// --- FUNÇÕES DE MÁSCARA/FORMATO DE MOEDA ---
// Converte número para string formatada em Real (Ex: 1234.56 -> R$ 1.234,56)
const formatCurrency = (value) => `R$ ${parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// Função para formatar o valor (Ex: 1000 -> 1.000,00) para o input text
const formatToInputDisplay = (value) => {
    if (value === null || value === undefined || value === '') return '';
    // Converte para string com duas casas decimais e usa vírgula
    return parseFloat(value).toFixed(2).replace('.', ',');
};

// Função auxiliar para carregar os parâmetros salvos
const loadParamsFromStorage = () => {
    try {
        const stored = localStorage.getItem(SIMULATOR_STORAGE_KEY);
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (e) {
        console.error("Erro ao carregar parâmetros do localStorage", e);
    }
    // Retorna valores padrão se nada estiver salvo ou houver erro
    return {
        valorInicial: 1000,
        aporteMensal: 500,
        rentabilidadeAnual: 10,
        metaAlcancada: 500000,
    };
};

// --- FUNÇÕES DE CÁLCULO FINANCEIRO (Mantidas) ---
const annualToMonthlyRate = (annualRate) => {
    return Math.pow(1 + annualRate / 100, 1 / 12) - 1;
};

const simulateInvestment = (initialValue, monthlyAporte, annualRate, monthsLimit = null, goalValue = null) => {
    // ... (Mantenha a função de simulação de investimento como está)
    const monthlyRate = annualToMonthlyRate(annualRate);
    let currentTotal = initialValue;
    let totalInvested = initialValue;
    let totalJuros = 0;
    const evolution = [];
    const maxIterations = monthsLimit || 60 * 12;

    for (let month = 1; month <= maxIterations; month++) {
        const jurosMonth = currentTotal * monthlyRate;
        currentTotal += jurosMonth;
        currentTotal += monthlyAporte;

        totalInvested += monthlyAporte;
        totalJuros += jurosMonth;

        evolution.push({
            month,
            year: Math.ceil(month / 12),
            currentTotal: currentTotal,
            totalInvested: totalInvested,
            totalJuros: totalJuros,
            monthlyRate: monthlyRate * 100,
            jurosMonth: jurosMonth
        });

        if (goalValue && currentTotal >= goalValue) {
            return {
                evolution,
                finalValue: currentTotal,
                totalInvested,
                totalJuros,
                monthsToGoal: month
            };
        }

        if (monthsLimit && month >= monthsLimit) {
            break;
        }
    }

    return {
        evolution,
        finalValue: currentTotal,
        totalInvested,
        totalJuros,
        monthsToGoal: goalValue ? null : maxIterations
    };
};


const formatMonthsToYears = (months) => {
    if (months === null || months >= 60 * 12) return 'Muito tempo (+60a)';
    const years = Math.floor(months / 12);
    const remainingMonths = Math.round(months % 12);
    return `${years}a ${remainingMonths}m`;
};

// Formata R$ para o Tooltip do Gráfico
const formatYAxis = (value) => {
    if (value >= 1000000) return `R$ ${(value / 1000000).toFixed(1)}M`;
    if (value >= 1000) return `R$ ${(value / 1000).toFixed(1)}K`;
    return `R$ ${value.toFixed(0)}`;
};

// --- COMPONENTES DE GRÁFICO REAIS (Mantidos) ---
const LineChart = ({ data, labels, title }) => {
    // ... (Mantenha o código do LineChart)
    if (!data || data.length === 0) return <div className="chart-placeholder"><h4>{title}</h4><p>Simule um investimento para ver o gráfico.</p></div>;

    const tickFormatter = (value, index) => {
        if (index % 12 === 0) { // Mostra a label apenas a cada ano (12 meses)
            return `Ano ${Math.ceil(value / 12)}`;
        }
        return '';
    };

    const CustomTooltip = ({ active, payload, label }) => {
        if (active && payload && payload.length) {
            return (
                <div className="custom-tooltip">
                    <p className="label">{`Mês ${label} (Ano ${Math.ceil(label / 12)})`}</p>
                    <p style={{ color: payload[0].color }}>{`${payload[0].name}: ${formatCurrency(payload[0].value)}`}</p>
                    <p style={{ color: payload[1].color }}>{`${payload[1].name}: ${formatCurrency(payload[1].value)}`}</p>
                </div>
            );
        }
        return null;
    };

    return (
        <div className="chart-wrapper">
            <h4>{title}</h4>
            <ResponsiveContainer width="100%" height={300}>
                <RechartsLineChart data={data}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#ccc" />
                    <XAxis
                        dataKey="month"
                        tickFormatter={tickFormatter}
                        height={50}
                        label={{ value: 'Tempo em Meses (Marcadores a cada Ano)', position: 'bottom', offset: 0 }}
                    />
                    <YAxis
                        tickFormatter={formatYAxis}
                        domain={[0, 'auto']}
                        width={80}
                    />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend iconType="circle" wrapperStyle={{ paddingTop: '10px' }} />
                    {/* Linha 1: Valor Total (Patrimônio) */}
                    <Line
                        type="monotone"
                        dataKey="total"
                        stroke="var(--primary-color)"
                        strokeWidth={2}
                        name="Valor Total"
                        dot={false}
                    />
                    {/* Linha 2: Total Investido (Aportes + Inicial) */}
                    <Line
                        type="monotone"
                        dataKey="investido"
                        stroke="#28a745" // Cor Verde
                        strokeWidth={2}
                        name="Valor Investido"
                        dot={false}
                        strokeDasharray="5 5"
                    />
                </RechartsLineChart>
            </ResponsiveContainer>
        </div>
    );
};

// 2. Gráfico de Pizza: Composição do Patrimônio
const PieChart = ({ data, title }) => {
    // ... (Mantenha o código do PieChart)
    if (!data || data.every(d => d.value === 0)) return <div className="chart-placeholder"><h4>{title}</h4><p>Simule um investimento para ver o gráfico.</p></div>;

    const COLORS = ['#0a3b66', '#4CAF50', '#FFC107']; // Azul, Verde, Amarelo

    const pieData = data.map((item, index) => ({
        name: item.label,
        value: item.value,
        color: COLORS[index % COLORS.length]
    }));

    const CustomTooltip = ({ active, payload }) => {
        if (active && payload && payload.length) {
            const dataItem = payload[0];
            return (
                <div className="custom-tooltip pie-tooltip">
                    <p className="label">{dataItem.name}</p>
                    <p>{formatCurrency(dataItem.value)}</p>
                </div>
            );
        }
        return null;
    };

    return (
        <div className="chart-wrapper">
            <h4>{title}</h4>
            <ResponsiveContainer width="100%" height={300}>
                <RechartsPieChart>
                    <Tooltip content={<CustomTooltip />} />
                    <Legend wrapperStyle={{ paddingTop: '10px' }} iconType="square" layout="vertical" align="right" verticalAlign="middle" />
                    <Pie
                        data={pieData}
                        dataKey="value"
                        nameKey="name"
                        cx="50%"
                        cy="50%"
                        outerRadius={100}
                        fill="#8884d8"
                        labelLine={false}
                        isAnimationActive={true}
                    >
                        {pieData.map((entry, index) => (
                            <Cell key={`cell-${index}`} fill={entry.color} />
                        ))}
                    </Pie>
                </RechartsPieChart>
            </ResponsiveContainer>
        </div>
    );
};


// --- COMPONENTE PRINCIPAL: SIMULADOR DE INVESTIMENTOS ---
export default function SimuladorInvestimentos() {
    // Carrega o estado inicial do localStorage
    const [params, setParams] = useState(() => loadParamsFromStorage());

    // Estados para a máscara visual dos campos de moeda (Valor Inicial, Aporte Mensal, Meta)
    const [displayValues, setDisplayValues] = useState(() => ({
        valorInicial: formatToInputDisplay(loadParamsFromStorage().valorInicial),
        aporteMensal: formatToInputDisplay(loadParamsFromStorage().aporteMensal),
        metaAlcancada: formatToInputDisplay(loadParamsFromStorage().metaAlcancada),
    }));

    // FUNÇÃO CENTRAL DE MUDANÇA (Com máscara e persistência)
    const handleCurrencyChange = (e) => {
        const { name, value } = e.target;
        let cleanedValue = value.replace(/\D/g, ''); // Remove tudo que não for dígito

        if (cleanedValue.length === 0) {
            // Se o campo estiver vazio, limpa os estados
            setDisplayValues(prev => ({ ...prev, [name]: '' }));
            setParams(prev => {
                const newParams = { ...prev, [name]: (name === 'rentabilidadeAnual' ? 0 : 0) }; // Rentabilidade pode ser 0
                localStorage.setItem(SIMULATOR_STORAGE_KEY, JSON.stringify(newParams));
                return newParams;
            });
            return;
        }

        // Divide por 100 para obter o valor em reais (Ex: 50000 -> 500.00)
        let numericValue = (parseInt(cleanedValue) / 100).toFixed(2);

        // Formata para display: 500.00 -> 500,00
        setDisplayValues(prev => ({ ...prev, [name]: numericValue.replace('.', ',') }));

        // Salva o valor numérico (com ponto) no estado do formulário e no localStorage
        setParams(prev => {
            const newParams = { ...prev, [name]: parseFloat(numericValue) };
            localStorage.setItem(SIMULATOR_STORAGE_KEY, JSON.stringify(newParams));
            return newParams;
        });
    };

    // Função para campos simples (Rentabilidade)
    const handleSimpleChange = (e) => {
        const { name, value } = e.target;
        const parsedValue = parseFloat(value);

        setParams(prev => {
            const newParams = { ...prev, [name]: parsedValue };
            localStorage.setItem(SIMULATOR_STORAGE_KEY, JSON.stringify(newParams));
            return newParams;
        });
    };

    // Função de Print
    const handlePrint = () => {
        window.print();
    };

    // --- LÓGICA CENTRAL DO SIMULADOR (Memoizada - Mantida) ---
    const simulationResult = useMemo(() => {
        const { valorInicial, aporteMensal, rentabilidadeAnual, metaAlcancada } = params;
        if (rentabilidadeAnual <= -100) return null;

        return simulateInvestment(valorInicial, aporteMensal, rentabilidadeAnual, null, metaAlcancada);
    }, [params]);

    // Resultados do KPI e Gráficos
    const {
        finalValue = 0,
        totalInvested = 0,
        totalJuros = 0,
        monthsToGoal = null,
        evolution = []
    } = simulationResult || {};

    // Dados para o Gráfico de Evolução do Patrimônio
    const chartData = useMemo(() => evolution.map(e => ({
        month: e.month,
        total: e.currentTotal,
        investido: e.totalInvested,
    })), [evolution]);

    // Dados para o Gráfico de Composição (último mês)
    const lastEvolution = evolution.find(e => e.month === monthsToGoal) || evolution[evolution.length - 1] || {};
    const jurosCompostos = lastEvolution.currentTotal - lastEvolution.totalInvested || 0;
    const aportes = (lastEvolution.totalInvested || 0) - params.valorInicial;

    const composicaoData = useMemo(() => [
        { label: 'Capital Inicial', value: params.valorInicial },
        { label: 'Aportes Mensais', value: aportes < 0 ? 0 : aportes },
        { label: 'Juros Compostos', value: jurosCompostos < 0 ? 0 : jurosCompostos },
    ], [params.valorInicial, aportes, jurosCompostos]);


    // --- Análises Hipotéticas "E SE..." (Mantida) ---
    const analisesHipoteticas = useMemo(() => {
        // ... (Lógica da Análise Hipotética)
        const { valorInicial, aporteMensal, rentabilidadeAnual, metaAlcancada } = params;
        if (rentabilidadeAnual <= -100 || monthsToGoal === null) {
            return [
                { id: 1, label: "Adicionando R$ 100/mês", result: "A simulação base não alcança a meta." },
                { id: 2, label: "Aumentando 1% a.a.", result: "A simulação base não alcança a meta." },
                { id: 3, label: "Começando 1 ano antes", result: "A simulação base não alcança a meta." },
                { id: 4, label: `Se a meta for R$ 500k`, result: "A simulação base não alcança a meta." },
            ];
        }

        const tempoAtual = monthsToGoal;

        // 1 - Mais R$ 100/mês
        const res1 = simulateInvestment(valorInicial, aporteMensal + 100, rentabilidadeAnual, null, metaAlcancada);
        const mesesDiminuidos1 = tempoAtual - (res1.monthsToGoal || 0);

        // 2 - Mais 1% a.a.
        const res2 = simulateInvestment(valorInicial, aporteMensal, rentabilidadeAnual + 1, null, metaAlcancada);
        const mesesDiminuidos2 = tempoAtual - (res2.monthsToGoal || 0);

        // 3 - Começando 1 ano antes (simular 1 ano a mais e ver o valor)
        const tempoTotal = monthsToGoal + 12;
        const res3 = simulateInvestment(valorInicial, aporteMensal, rentabilidadeAnual, tempoTotal);
        const valorAumentado3 = res3.finalValue - (evolution[tempoAtual - 1]?.currentTotal || 0);

        // 4 - Se a meta for R$ 500k (se a meta atual não for 500k)
        const novaMeta = 500000;
        const res4 = simulateInvestment(valorInicial, aporteMensal, rentabilidadeAnual, null, novaMeta);
        const mesesAumentados4 = (res4.monthsToGoal || 0) - tempoAtual;

        return [
            { id: 1, label: `Se eu adicionasse R$ 100,00/mês a mais`, result: `${formatMonthsToYears(Math.abs(mesesDiminuidos1))} ${mesesDiminuidos1 > 0 ? 'a menos' : 'a mais'}.` },
            { id: 2, label: `Se eu aumentasse 1% a.a. a mais`, result: `${formatMonthsToYears(Math.abs(mesesDiminuidos2))} ${mesesDiminuidos2 > 0 ? 'a menos' : 'a mais'}.` },
            { id: 3, label: `Se eu começasse 1 ano antes (em ${formatMonthsToYears(tempoTotal)})`, result: `Aumentaria ${formatCurrency(valorAumentado3)} no valor final.` },
            { id: 4, label: `Se a meta fosse R$ ${novaMeta.toLocaleString('pt-BR')}`, result: `${formatMonthsToYears(Math.abs(mesesAumentados4))} ${mesesAumentados4 > 0 ? 'a mais' : 'a menos'} (Tempo total: ${formatMonthsToYears(res4.monthsToGoal)}).` },
        ];
    }, [params, monthsToGoal, evolution]);


    // --- Marcos de Alcance (Timeline - Mantida) ---
    const marcos = useMemo(() => {
        // ... (Lógica de Marcos)
        const metas = [100000, 250000, 500000, 750000, 1000000];
        return metas.map(meta => {
            const result = evolution.find(e => e.currentTotal >= meta);
            return {
                valor: meta,
                tempo: result ? formatMonthsToYears(result.month) : 'Não alcançado',
            };
        });
    }, [evolution]);

    // --- Tabela Detalhada Ano a Ano (Mantida) ---
    const tabelaAnual = useMemo(() => {
        // ... (Lógica da Tabela Anual)
        const anualData = [];
        let ano = 0;

        evolution.forEach(e => {
            const currentYear = e.year;
            if (currentYear > ano) {
                ano = currentYear;
                anualData.push({
                    ano: ano,
                    investido: e.totalInvested,
                    juros: e.totalJuros,
                    total: e.currentTotal,
                });
            }
        });
        return anualData;
    }, [evolution]);

    // --- Comparativo de Cenários (Taxas Fixas - Mantida) ---
    const comparativoCenarios = useMemo(() => {
        // ... (Lógica do Comparativo de Cenários)
        const { valorInicial, aporteMensal, metaAlcancada } = params;
        const cenarios = [
            { nome: 'Cenário Atual', taxa: params.rentabilidadeAnual, cor: 'var(--primary-color)' },
            { nome: 'CDI (Hipótese 15% a.a.)', taxa: 15, cor: '#f8a614' },
            { nome: 'Poupança (Hipótese 8.5% a.a.)', taxa: 8.5, cor: '#28a745' },
            { nome: 'Moderado (Hipótese 12% a.a.)', taxa: 12, cor: '#007bff' },
        ];

        return cenarios.map(cenario => {
            const result = simulateInvestment(valorInicial, aporteMensal, cenario.taxa, null, metaAlcancada);
            return {
                ...cenario,
                tempo: formatMonthsToYears(result.monthsToGoal),
                valorFinal: formatCurrency(result.finalValue),
            };
        });
    }, [params]);


    return (
        <div className="page-container simulador-page">

            <div className="content-card no-print"> {/* Adiciona no-print para ocultar o formulário na impressão */}
                <h2 className="form-title">Simulador de Investimentos</h2>

                {/* BOTÃO DE PRINT MOVIDO PARA CIMA */}
                <div className="header-with-print">
                    <h3 className="section-title">Parâmetros da Simulação</h3>
                    <button onClick={handlePrint} className="btn btn-print">
                        <i className="fas fa-print"></i> Imprimir/PDF
                    </button>
                </div>


                {/* --- 1. FORMULÁRIO DE PARÂMETROS --- */}
                <form className="form-grid" onSubmit={(e) => e.preventDefault()}>
                    {/* Valor Inicial (R$) - AGORA COM MÁSCARA */}
                    <div className="form-group">
                        <label htmlFor="valorInicial">Valor Inicial (R$)</label>
                        <input
                            id="valorInicial"
                            name="valorInicial"
                            type="text" // Alterado para text
                            inputMode="numeric"
                            value={displayValues.valorInicial}
                            onChange={handleCurrencyChange}
                            className="form-control"
                            required
                        />
                    </div>
                    {/* Aporte Mensal (R$) - AGORA COM MÁSCARA */}
                    <div className="form-group">
                        <label htmlFor="aporteMensal">Aporte Mensal (R$)</label>
                        <input
                            id="aporteMensal"
                            name="aporteMensal"
                            type="text" // Alterado para text
                            inputMode="numeric"
                            value={displayValues.aporteMensal}
                            onChange={handleCurrencyChange}
                            className="form-control"
                            required
                        />
                    </div>
                    {/* Rentabilidade Anual (%) - SEM MÁSCARA, SIMPLES */}
                    <div className="form-group">
                        <label htmlFor="rentabilidadeAnual">Rentabilidade Anual (%)</label>
                        <input id="rentabilidadeAnual" name="rentabilidadeAnual" type="number" step="0.01" value={params.rentabilidadeAnual} onChange={handleSimpleChange} className="form-control" required />
                    </div>
                    {/* Meta a Alcançar (R$) - AGORA COM MÁSCARA */}
                    <div className="form-group">
                        <label htmlFor="metaAlcancada">Meta a Alcançar (R$)</label>
                        <input
                            id="metaAlcancada"
                            name="metaAlcancada"
                            type="text" // Alterado para text
                            inputMode="numeric"
                            value={displayValues.metaAlcancada}
                            onChange={handleCurrencyChange}
                            className="form-control"
                            required
                        />
                    </div>
                </form>
                {/* ... (Restante do conteúdo do content-card) ... */}
            </div>

            {/* O restante dos content-cards fica igual, mas o botão de print no topo da página
                garante que o usuário possa imprimir todo o resultado (KPIS, Gráficos, Tabelas) */}

            <div className="content-card">
                {/* --- 2. KPIS DE RESULTADO --- */}
                <h3 className="section-title">Resultados da Simulação</h3>
                <div className="kpi-grid">
                    <div className="kpi-card">
                        <span className="kpi-label">Tempo para a Meta</span>
                        <p className="kpi-value">{formatMonthsToYears(monthsToGoal)}</p>
                    </div>
                    <div className="kpi-card">
                        <span className="kpi-label">Total Investido</span>
                        <p className="kpi-value">{formatCurrency(totalInvested)}</p>
                    </div>
                    <div className="kpi-card">
                        <span className="kpi-label">Juros Ganhos</span>
                        <p className="kpi-value">{formatCurrency(totalJuros)}</p>
                    </div>
                    <div className="kpi-card">
                        <span className="kpi-label">Valor Final (na Meta)</span>
                        <p className="kpi-value">{formatCurrency(finalValue)}</p>
                    </div>
                </div>
            </div>

            <div className="content-card">
                {/* --- 3. GRÁFICOS (Implementados com Recharts) --- */}
                <h3 className="section-title">Análise Visual</h3>
                <div className="charts-container">
                    <div className="chart-item">
                        <LineChart
                            title="Evolução do Patrimônio"
                            data={chartData}
                            labels={['total', 'investido']}
                        />
                    </div>
                    <div className="chart-item">
                        <PieChart
                            title="Composição do Patrimônio (no fim)"
                            data={composicaoData}
                        />
                    </div>
                </div>
            </div>

            <div className="content-card">
                {/* --- 4. ANÁLISE HIPOTÉTICA "E SE..." --- */}
                <h3 className="section-title">Análise Hipotética "E Se..."</h3>
                <div className="analise-hipotetica-grid">
                    {analisesHipoteticas.map((analise) => (
                        <div key={analise.id} className="hipotetica-item">
                            <p className="hipotetica-label">{analise.label}</p>
                            <p className="hipotetica-result">{analise.result}</p>
                        </div>
                    ))}
                </div>
            </div>

            <div className="content-card">
                {/* --- 5. TIMELINE E MARCOS DE ALCANCE --- */}
                <h3 className="section-title">Marcos de Alcance da Riqueza</h3>
                <div className="marcos-grid">
                    {marcos.map((marco) => (
                        <div key={marco.valor} className="marco-item">
                            <span className="marco-valor">{formatCurrency(marco.valor).replace('R$', 'R$')}</span>
                            <span className="marco-tempo">{marco.tempo}</span>
                        </div>
                    ))}
                </div>
            </div>

            <div className="content-card">
                {/* --- 6. TABELA DETALHADA --- */}
                <h3 className="section-title">Evolução Detalhada Ano a Ano</h3>
                <div className="table-wrapper">
                    <table className="data-table detailed-table">
                        <thead>
                            <tr>
                                <th>Ano</th>
                                <th>Investido (Total)</th>
                                <th>Juros (Total)</th>
                                <th>Patrimônio (Total)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tabelaAnual.length > 0 ? tabelaAnual.map(row => (
                                <tr key={row.ano}>
                                    <td>{row.ano}° Ano</td>
                                    <td>{formatCurrency(row.investido)}</td>
                                    <td>{formatCurrency(row.juros)}</td>
                                    <td>{formatCurrency(row.total)}</td>
                                </tr>
                            )) : (
                                <tr><td colSpan="4" className="empty-state">Preencha os parâmetros para simular.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="content-card">
                {/* --- 7. COMPARATIVO DE CENÁRIOS --- */}
                <h3 className="section-title">Comparativo de Cenários (Taxas Fixas)</h3>
                <div className="cenarios-comparativo-grid">
                    {comparativoCenarios.map(cenario => (
                        <div key={cenario.nome} className="cenario-card" style={{ '--cenario-color': cenario.cor }}>
                            <p className="cenario-nome">{cenario.nome}</p>
                            <p className="cenario-taxa">{cenario.taxa}% a.a.</p>
                            <div className="cenario-result">
                                <span className="cenario-label">Tempo para a Meta:</span>
                                <span className="cenario-value">{cenario.tempo}</span>
                            </div>
                            <div className="cenario-result">
                                <span className="cenario-label">Valor Final:</span>
                                <span className="cenario-value">{cenario.valorFinal}</span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}