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

export default function InvestmentChart({ chartData }) {
  const data = {
    labels: chartData.labels,
    datasets: [
      {
        label: 'Investimentos Acumulados',
        borderColor: '#007bff',
        backgroundColor: 'rgba(0, 123, 255, 0.2)',
        data: chartData.valores,
        tension: 0.3,
      },
    ],
  };

  const options = {
    responsive: true,
    plugins: {
      legend: { position: 'top' },
      title: { display: true, text: 'Evolução dos Investimentos' },
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
