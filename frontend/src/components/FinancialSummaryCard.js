import React, { useMemo } from 'react';
// Importa a função de formatação do arquivo principal do dashboard
import { formatCurrency } from '../pages/dashboard.js';

const FinancialSummaryCard = ({ contas, cartoes, showValues }) => {
    // Garante que os dados de entrada sejam arrays, evitando erros se forem nulos.
    const contasSafe = Array.isArray(contas) ? contas : [];
    const cartoesSafe = Array.isArray(cartoes) ? cartoes : [];

    const totals = useMemo(() => {
        // --- Saldos de Contas e Cheque Especial ---
        const totalSaldoEmContas = contasSafe.reduce((acc, conta) => acc + (parseFloat(conta.saldo) || 0), 0);
        const totalChequeEspecialDisponivel = contasSafe.reduce((acc, conta) => acc + (parseFloat(conta.cheque_especial_disponivel) || 0), 0);

        // --- Saldos de Cartões de Crédito ---
        // Utiliza 'limite' e 'utilizado' conforme definido no backend corrigido
        const totalLimiteCartao = cartoesSafe.reduce((acc, cartao) => acc + (parseFloat(cartao.limite) || 0), 0);
        const totalCreditoUtilizado = cartoesSafe.reduce((acc, cartao) => acc + (parseFloat(cartao.utilizado) || 0), 0);
        const totalCreditoDisponivel = cartoesSafe.reduce((acc, cartao) => acc + (parseFloat(cartao.disponivel) || 0), 0);

        // --- Saldo Geral ---
        // Soma o saldo real das contas + o crédito disponível dos cartões + o cheque especial disponível
        const saldoGeralDisponivel = totalSaldoEmContas + totalCreditoDisponivel + totalChequeEspecialDisponivel;

        return {
            totalSaldoEmContas,
            totalChequeEspecialDisponivel,
            totalLimiteCartao,
            totalCreditoUtilizado,
            totalCreditoDisponivel,
            saldoGeralDisponivel
        };
    }, [contasSafe, cartoesSafe]);

    // Função auxiliar para mostrar ou ocultar os valores
    const formatValue = (value) => showValues ? formatCurrency(value) : 'R$ ****';

    return (
        <div className="dashboard-card financial-summary-card">
            <h3 className="card-title">Resumo Financeiro</h3>
            <div className="summary-list">

                <div className="summary-item">
                    <span className="summary-label">Saldo em Contas:</span>
                    <span className={`summary-value ${totals.totalSaldoEmContas >= 0 ? 'text-success' : 'text-danger'}`}>
                        {formatValue(totals.totalSaldoEmContas)}
                    </span>
                </div>

                <div className="summary-item">
                    <span className="summary-label">Cheque Especial Disponível:</span>
                    <span className="summary-value text-success">
                        {formatValue(totals.totalChequeEspecialDisponivel)}
                    </span>
                </div>

                <div className="summary-item">
                    <span className="summary-label">Crédito Disponível em Cartões:</span>
                    <span className="summary-value text-success">
                        {formatValue(totals.totalCreditoDisponivel)}
                    </span>
                </div>

                <div className="summary-item total">
                    <span className="summary-label">Saldo Geral Disponível:</span>
                    <span className={`summary-value ${totals.saldoGeralDisponivel >= 0 ? 'text-success' : 'text-danger'}`}>
                        {formatValue(totals.saldoGeralDisponivel)}
                    </span>
                </div>

                {/* Detalhes adicionais sobre os cartões */}
                <hr className="summary-divider" />

                <div className="summary-item detail">
                    <span className="summary-label">Limite Total dos Cartões:</span>
                    <span className="summary-value">
                        {formatValue(totals.totalLimiteCartao)}
                    </span>
                </div>

                <div className="summary-item detail">
                    <span className="summary-label">Crédito Utilizado:</span>
                    <span className="summary-value text-danger">
                        {formatValue(totals.totalCreditoUtilizado)}
                    </span>
                </div>

            </div>
        </div>
    );
};

export default FinancialSummaryCard;