import { useEffect, useState } from 'react';
import { Line } from 'react-chartjs-2';
import 'chart.js/auto';
import './InvestmentChartForecast.css';
// 1. Importa as variáveis de configuração
import { API_BASE_URL, PUBLIC_URL } from '../apiConfig';

export default function InvestmentChartForecast() {
  const [chartData, setChartData] = useState(null);
  const [totais, setTotais] = useState(null);
  const [bancos, setBancos] = useState([]);
  const [bancoSelecionado, setBancoSelecionado] = useState(null);

  const fetchData = (bancoId = null) => {
    // 2. Monta a URL dinamicamente
    const url = bancoId
      ? `${API_BASE_URL}/investimentos_forecast.php?banco_id=${bancoId}`
      : `${API_BASE_URL}/investimentos_forecast.php`;

    fetch(url)
      .then(res => res.json())
      .then(data => {
        setChartData({
          labels: data.labels,
          datasets: [
            {
              label: 'Valor Investido',
              data: data.investidos,
              borderColor: '#4caf50',
              backgroundColor: 'rgba(76, 175, 80, 0.2)',
              fill: true
            },
            {
              label: 'Lucro',
              data: data.lucros,
              borderColor: '#ff9800',
              backgroundColor: 'rgba(255, 152, 0, 0.2)',
              fill: true
            }
          ]
        });

        setTotais(data.totais);
        setBancos(data.totais.bancosDetalhados || []);
        setBancoSelecionado(bancoId);
      })
      .catch(err => {
        console.error('Erro ao buscar dados de investimentos:', err);
      });
  };

  useEffect(() => {
    fetchData(); // inicia com dados gerais
  }, []);

  if (!chartData || !totais) return <p>Carregando gráfico de investimentos...</p>;

  return (
    <div className="investment-chart-container">
      <h2 className="title-center">Previsão de Investimentos</h2>
      <Line data={chartData} />

      <div className="bank-icons-container">
        {bancos.map((banco) => (
          <div
            key={banco.id}
            className={`bank-icon-item ${bancoSelecionado === banco.id ? 'active' : ''}`}
            onClick={() => fetchData(banco.id)}
          >
            <img
              src={banco.icone}
              alt={banco.nome}
              width={50}
              height={50}
              style={{ borderRadius: '6px', cursor: 'pointer' }}
              onError={(e) => {
                // 3. Corrige a URL de fallback da imagem
                e.target.src = `${PUBLIC_URL}/assets/img/default-bank.png`;
              }}
            />
            <p className="bank-name">{banco.nome}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
