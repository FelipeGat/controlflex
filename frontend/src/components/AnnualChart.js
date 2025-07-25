import React from 'react';
import { Bar } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// Registrar os componentes do Chart.js que serão utilizados
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ChartDataLabels // Registrar o plugin de labels
);

// O componente agora aceita a prop 'showDataLabels', com 'true' como valor padrão
export default function AnnualChart({ chartData, showDataLabels = true }) {
  // Estrutura dos dados para o gráfico
  const data = {
    labels: chartData.labels,
    datasets: [
      {
        label: 'A Receber',
        data: chartData.receitas,
        backgroundColor: 'rgba(40, 167, 69, 0.6)', // Verde
        borderColor: 'rgba(40, 167, 69, 1)',
        borderWidth: 1,
      },
      {
        label: 'A Pagar',
        data: chartData.despesas,
        backgroundColor: 'rgba(220, 53, 69, 0.6)', // Vermelho
        borderColor: 'rgba(220, 53, 69, 1)',
        borderWidth: 1,
      },
    ],
  };

  // Opções de configuração do gráfico
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
      },
      // AQUI ESTÁ A LÓGICA PRINCIPAL:
      // O plugin de labels é controlado pela nova propriedade.
      datalabels: {
        display: showDataLabels, // Se 'showDataLabels' for false, os valores não aparecem.
        color: '#333',
        font: {
          size: 9,
        },
        anchor: 'end',
        align: 'start',
        formatter: (value) => {
          // Formata o número para uma leitura mais fácil (ex: 10000 -> 10k)
          if (value >= 1000) {
            return (value / 1000).toFixed(1) + 'k';
          }
          return value;
        },
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          // Formata o eixo Y para exibir os valores de forma mais limpa
          callback: function(value) {
            return 'R$ ' + value.toLocaleString('pt-BR');
          }
        }
      },
    },
  };

  return <Bar data={data} options={options} />;
}
