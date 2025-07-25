import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

export default function ForecastChart({ chartData }) {
  const data = {
    labels: chartData.labels,
    datasets: [
      {
        label: 'Receitas Previstas',
        borderColor: 'rgba(40, 167, 69, 1)',
        backgroundColor: 'rgba(40, 167, 69, 0.2)',
        data: chartData.receitasPrevistas,
        tension: 0.3,
      },
      {
        label: 'Despesas Previstas',
        borderColor: 'rgba(220, 53, 69, 1)',
        backgroundColor: 'rgba(220, 53, 69, 0.2)',
        data: chartData.despesasPrevistas,
        tension: 0.3,
      },
    ],
  };

  const options = {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: { display: true, text: 'Previsão Orçamentária' },
    },
    scales: {
      y: { ticks: { callback: (value) => value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) } },
    },
  };

  return (
    <div style={{ width: '100%', maxWidth: '480px', backgroundColor: '#fff', padding: '20px', borderRadius: '12px', boxShadow: '0 3px 10px rgba(0,0,0,0.05)' }}>
      <Line data={data} options={options} />
    </div>
  );
}
