export default function FinancialCard({ title, value, color }) {
  // Define a cor conforme a propriedade
  const getColor = (color) => {
    switch (color) {
      case 'green': return '#28a745';
      case 'red': return '#dc3545';
      case 'blue': return '#007bff';
      default: return '#333';
    }
  };

  return (
    <div className="financial-card">
      <div className="title">{title}</div>
      <div className="value" style={{ color: getColor(color) }}>
        {typeof value === 'number' ? value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : value}
      </div>
    </div>
  );
}
