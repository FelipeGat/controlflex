import React from 'react';

// --- Estilos CSS embutidos para simplicidade ---
// (Idealmente, estes estilos estariam no seu arquivo `dashboard.css` ou em um `MonthBox.css` dedicado)
const styles = {
  container: {
    display: 'grid',
    gridTemplateColumns: 'repeat(2, 1fr)', // Força o layout de 2 colunas
    gap: '16px', // Espaçamento entre os itens
  },
  item: {
    backgroundColor: '#f8f9fa',
    padding: '16px 12px',
    borderRadius: '8px',
    textAlign: 'center',
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'center',
    border: '1px solid #e9ecef',
  },
  label: {
    fontSize: '0.85rem',
    color: '#6c757d',
    marginBottom: '8px',
    textTransform: 'uppercase', // Deixa o label mais discreto e padronizado
  },
  value: {
    fontSize: '1.3rem', // Fonte maior para dar destaque ao valor
    fontWeight: '600',
  },
  // Cores para os valores
  green: { color: '#28a745' },
  red: { color: '#dc3545' },
  blue: { color: '#007bff' },
  orange: { color: '#fd7e14' },
};

// --- Função auxiliar para formatar os números como moeda brasileira ---
const formatCurrency = (value) => {
  // Garante que o valor seja um número antes de formatar
  const numericValue = Number(value) || 0;
  return numericValue.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });
};

export default function MonthBox({ monthData }) {
  // Se não houver dados, não renderiza nada para evitar erros
  if (!monthData) {
    return <div style={styles.container}><p>Dados não disponíveis.</p></div>;
  }

  // --- Estrutura de dados focada nos 6 itens mais importantes ---
  const items = [
    { label: 'Receita Realizada', value: monthData.receitaRealizada, color: 'green' },
    { label: 'Despesa Realizada', value: monthData.despesaRealizada, color: 'red' },
    { label: 'Saldo do Mês', value: monthData.saldo, color: monthData.saldo >= 0 ? 'green' : 'red' },
    { label: 'Saldo Previsto', value: monthData.saldoPrevisto, color: monthData.saldoPrevisto >= 0 ? 'blue' : 'red' },
    { label: 'Contas a Receber', value: monthData.aReceber, color: 'blue' },
    { label: 'Contas a Pagar', value: monthData.aPagar, color: 'orange' },
  ];

  return (
    <div style={styles.container}>
      {items.map((item, index) => (
        <div key={index} style={styles.item}>
          <span style={styles.label}>{item.label}</span>
          {/* Aplica o estilo de cor dinamicamente */}
          <span style={{ ...styles.value, ...styles[item.color] }}>
            {formatCurrency(item.value)}
          </span>
        </div>
      ))}
    </div>
  );
}
