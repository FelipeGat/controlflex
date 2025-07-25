import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import './contasPorPeriodoContainer.css';

const ContasPorPeriodoContainer = () => {
  const [contasReceber, setContasReceber] = useState([]);
  const [contasPagar, setContasPagar] = useState([]);

  useEffect(() => {
    async function carregarContas() {
      const responseReceber = await api.get('/dashboard/contas-receber');
      const responsePagar = await api.get('/dashboard/contas-pagar');
      setContasReceber(responseReceber.data);
      setContasPagar(responsePagar.data);
    }
    carregarContas();
  }, []);

  const getLinhaClasse = (data) => {
    const hoje = new Date().toISOString().split('T')[0];
    if (data < hoje) return 'linha-vermelha';
    if (data === hoje) return 'linha-verde';
    return '';
  };

  return (
    <div className="container-contas-dashboard">
      <h3>Contas a Pagar x Receber por Período</h3>
      <div className="grid-contas">
        <div>
          <h4>Contas a Receber</h4>
          <table className="tabela-contas">
            <thead>
              <tr>
                <th>Data</th>
                <th>Categoria</th>
                <th>Fornecedor</th>
                <th>Familiar</th>
                <th>Valor</th>
                <th>Observações</th>
                <th>Forma de Pagamento</th>
              </tr>
            </thead>
            <tbody>
              {contasReceber.map((item) => (
                <tr key={item.id} className={getLinhaClasse(item.data)}>
                  <td>{item.data}</td>
                  <td>{item.categoria}</td>
                  <td>{item.fornecedor}</td>
                  <td>{item.familiar}</td>
                  <td>R$ {item.valor.toFixed(2)}</td>
                  <td>{item.observacoes}</td>
                  <td>{item.forma_pagamento}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div>
          <h4>Contas a Pagar</h4>
          <table className="tabela-contas">
            <thead>
              <tr>
                <th>Data</th>
                <th>Categoria</th>
                <th>Fornecedor</th>
                <th>Familiar</th>
                <th>Valor</th>
                <th>Observações</th>
                <th>Forma de Pagamento</th>
              </tr>
            </thead>
            <tbody>
              {contasPagar.map((item) => (
                <tr key={item.id} className={getLinhaClasse(item.data)}>
                  <td>{item.data}</td>
                  <td>{item.categoria}</td>
                  <td>{item.fornecedor}</td>
                  <td>{item.familiar}</td>
                  <td>R$ {item.valor.toFixed(2)}</td>
                  <td>{item.observacoes}</td>
                  <td>{item.forma_pagamento}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default ContasPorPeriodoContainer;
