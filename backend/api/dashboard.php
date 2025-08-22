<?php
// /api/dashboard.php

// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
$frontend_url = "http://localhost:3000";
header("Access-Control-Allow-Origin: " . $frontend_url);
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../config/db.php';

try {
    $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
    if (!$usuario_id) {
        throw new Exception("ID do usuário é obrigatório.", 400);
    }

    $inicio = $_GET['inicio'] ?? date('Y-m-01');
    $fim    = $_GET['fim']    ?? date('Y-m-t');
    $ano_selecionado = date('Y', strtotime($inicio));

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- 1. DADOS PARA OS CARDS PRINCIPAIS (KPIs) ---
    $stmtCards = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = :uid1 AND data_prevista_recebimento BETWEEN :inicio1 AND :fim1) as total_receitas,
            (SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = :uid2 AND data_compra BETWEEN :inicio2 AND :fim2) as total_despesas
    ");
    $stmtCards->execute([
        ':uid1' => $usuario_id, ':inicio1' => $inicio, ':fim1' => $fim,
        ':uid2' => $usuario_id, ':inicio2' => $inicio, ':fim2' => $fim
    ]);
    $kpi_data = $stmtCards->fetch();

    $inicio_anterior = date('Y-m-d', strtotime($inicio . ' -1 month'));
    $fim_anterior    = date('Y-m-d', strtotime($fim    . ' -1 month'));

    $stmtCardsAnterior = $pdo->prepare("
        SELECT
            (SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = :uid1 AND data_prevista_recebimento BETWEEN :inicio1 AND :fim1) as total_receitas,
            (SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = :uid2 AND data_compra BETWEEN :inicio2 AND :fim2) as total_despesas
    ");
    $stmtCardsAnterior->execute([
        ':uid1' => $usuario_id, ':inicio1' => $inicio_anterior, ':fim1' => $fim_anterior,
        ':uid2' => $usuario_id, ':inicio2' => $inicio_anterior, ':fim2' => $fim_anterior
    ]);
    $kpi_data_anterior = $stmtCardsAnterior->fetch();

    // Variações
    $calculate_variation = function($current, $previous) {
        if ($previous == 0) return ($current > 0) ? 100.0 : 0.0;
        return (($current - $previous) / abs($previous)) * 100;
    };

    $kpi_data['saldo']            = $kpi_data['total_receitas'] - $kpi_data['total_despesas'];
    $kpi_data_anterior['saldo']   = $kpi_data_anterior['total_receitas'] - $kpi_data_anterior['total_despesas'];
    $kpi_data['variacao_receitas'] = round($calculate_variation($kpi_data['total_receitas'],  $kpi_data_anterior['total_receitas']), 2);
    $kpi_data['variacao_despesas'] = round($calculate_variation($kpi_data['total_despesas'],  $kpi_data_anterior['total_despesas']), 2);
    $kpi_data['variacao_saldo']    = round($calculate_variation($kpi_data['saldo'],           $kpi_data_anterior['saldo']), 2);

    // --- 2. GRÁFICO ANUAL (FLUXO DE CAIXA) ---
    $stmtAnnual = $pdo->prepare("
        SELECT m.mes AS mes_num, COALESCE(r.total, 0) AS receitas, COALESCE(d.total, 0) AS despesas
        FROM (SELECT 1 AS mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS m
        LEFT JOIN (
            SELECT MONTH(data_prevista_recebimento) AS mes, SUM(valor) AS total
            FROM receitas
            WHERE usuario_id = :uid1 AND YEAR(data_prevista_recebimento) = :ano1
            GROUP BY mes
        ) r ON m.mes = r.mes
        LEFT JOIN (
            SELECT MONTH(data_compra) AS mes, SUM(valor) AS total
            FROM despesas
            WHERE usuario_id = :uid2 AND YEAR(data_compra) = :ano2
            GROUP BY mes
        ) d ON m.mes = d.mes
        ORDER BY m.mes
    ");
    $stmtAnnual->execute([
        ':uid1' => $usuario_id, ':ano1' => $ano_selecionado,
        ':uid2' => $usuario_id, ':ano2' => $ano_selecionado
    ]);
    $annual_data_raw = $stmtAnnual->fetchAll();
    $annual_chart_data = [
        'labels' => ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
        'receitas' => array_column($annual_data_raw, 'receitas'),
        'despesas' => array_column($annual_data_raw, 'despesas')
    ];

    // --- 3. GRÁFICOS DE PIZZA ---
    $stmtExpCat = $pdo->prepare("
        SELECT c.nome, SUM(d.valor) as total
        FROM despesas d
        JOIN categorias c ON d.categoria_id = c.id
        WHERE d.usuario_id = :uid AND d.data_compra BETWEEN :inicio AND :fim
        GROUP BY c.nome HAVING total > 0
        ORDER BY total DESC
    ");
    $stmtExpCat->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $expensesByCategory = $stmtExpCat->fetchAll();

    $stmtIncCat = $pdo->prepare("
        SELECT c.nome, SUM(r.valor) as total
        FROM receitas r
        JOIN categorias c ON r.categoria_id = c.id
        WHERE r.usuario_id = :uid AND r.data_prevista_recebimento BETWEEN :inicio AND :fim
        GROUP BY c.nome HAVING total > 0
        ORDER BY total DESC
    ");
    $stmtIncCat->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $incomesByCategory = $stmtIncCat->fetchAll();

    $stmtExpFam = $pdo->prepare("
        SELECT COALESCE(f.nome, 'Não especificado') as nome, SUM(d.valor) as total
        FROM despesas d
        LEFT JOIN familiares f ON d.quem_comprou = f.id
        WHERE d.usuario_id = :uid AND d.data_compra BETWEEN :inicio AND :fim
        GROUP BY nome HAVING total > 0
        ORDER BY total DESC
    ");
    $stmtExpFam->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $expensesByFamilyMember = $stmtExpFam->fetchAll();

    $stmtIncFam = $pdo->prepare("
        SELECT COALESCE(f.nome, 'Não especificado') as nome, SUM(r.valor) as total
        FROM receitas r
        LEFT JOIN familiares f ON r.quem_recebeu = f.id
        WHERE r.usuario_id = :uid AND r.data_prevista_recebimento BETWEEN :inicio AND :fim
        GROUP BY nome HAVING total > 0
        ORDER BY total DESC
    ");
    $stmtIncFam->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $incomesByFamilyMember = $stmtIncFam->fetchAll();

    // --- 4. DADOS DE INVESTIMENTOS ---
    $stmtInvestimentos = $pdo->prepare("
        SELECT COALESCE(SUM(valor_aportado), 0) as total_investido
        FROM investimentos
        WHERE usuario_id = :uid
    ");
    $stmtInvestimentos->execute([':uid' => $usuario_id]);
    $investments_data = $stmtInvestimentos->fetch();

    // --- 5. ÚLTIMOS LANÇAMENTOS ---
    $stmtLancamentos = $pdo->prepare("
        (SELECT r.id, 'receita' as tipo, r.valor, r.data_prevista_recebimento as data, COALESCE(c.nome, 'Sem categoria') as categoria_nome
           FROM receitas r LEFT JOIN categorias c ON r.categoria_id = c.id
          WHERE r.usuario_id = :uid1)
        UNION ALL
        (SELECT d.id, 'despesa' as tipo, d.valor, d.data_compra as data, COALESCE(c.nome, 'Sem categoria') as categoria_nome
           FROM despesas d LEFT JOIN categorias c ON d.categoria_id = c.id
          WHERE d.usuario_id = :uid2)
    ");
    $stmtLancamentos->execute([':uid1' => $usuario_id, ':uid2' => $usuario_id]);
    $ultimos_lancamentos = $stmtLancamentos->fetchAll();

    // --- 6. GRÁFICO DE EVOLUÇÃO PATRIMONIAL ---
    $stmtInvestChart = $pdo->prepare("
        SELECT m.mes, COALESCE(i.aportes_mes, 0) AS aportes_mes FROM 
            (SELECT 1 AS mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS m
        LEFT JOIN (
            SELECT MONTH(data_aporte) AS mes, SUM(valor_aportado) AS aportes_mes
            FROM investimentos
            WHERE usuario_id = :uid AND YEAR(data_aporte) = :ano
            GROUP BY MONTH(data_aporte)
        ) i ON m.mes = i.mes
        ORDER BY m.mes ASC
    ");
    $stmtInvestChart->execute([':uid' => $usuario_id, ':ano' => $ano_selecionado]);
    $invest_data_raw = $stmtInvestChart->fetchAll();

    // --- 7. KPIs: valores realizados no período selecionado ---
    $stmtRealizados = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(valor), 0)
               FROM receitas
              WHERE usuario_id = :uid1 AND data_recebimento BETWEEN :inicio1 AND :fim1) as receitas_realizadas,
            (SELECT COALESCE(SUM(valor), 0)
               FROM despesas
              WHERE usuario_id = :uid2 AND data_pagamento BETWEEN :inicio2 AND :fim2) as despesas_realizadas
    ");
    $stmtRealizados->execute([
        ':uid1' => $usuario_id, ':inicio1' => $inicio, ':fim1' => $fim,
        ':uid2' => $usuario_id, ':inicio2' => $inicio, ':fim2' => $fim
    ]);
    $realizados_data = $stmtRealizados->fetch();
    $realizados_data['saldo_realizado'] = $realizados_data['receitas_realizadas'] - $realizados_data['despesas_realizadas'];

    // Percentuais realizado x previsto (período selecionado)
    $realizados_data['perc_receita'] = $kpi_data['total_receitas'] > 0
        ? round(($realizados_data['receitas_realizadas'] / $kpi_data['total_receitas']) * 100, 2) : 0;
    $realizados_data['perc_despesa'] = $kpi_data['total_despesas'] > 0
        ? round(($realizados_data['despesas_realizadas'] / $kpi_data['total_despesas']) * 100, 2) : 0;
    $realizados_data['perc_saldo'] = $kpi_data['saldo'] != 0
        ? round(($realizados_data['saldo_realizado'] / $kpi_data['saldo']) * 100, 2) : 0;

    // --- 8. Simulação de gráfico de investimentos (acumulado) ---
    $patrimonio_acumulado = [];
    $rendimento_acumulado = [];
    $saldo_anterior = 0;
    $taxa_rendimento_mensal = 0.01; // 1% ao mês (simulação)

    foreach ($invest_data_raw as $row) {
        $rendimento_mes = $saldo_anterior * $taxa_rendimento_mensal;
        $saldo_atual = $saldo_anterior + $row['aportes_mes'] + $rendimento_mes;
        $patrimonio_acumulado[] = round($saldo_atual, 2);
        $rendimento_acumulado[] = round($rendimento_mes, 2);
        $saldo_anterior = $saldo_atual;
    }
    $investment_chart_data = [
        'labels' => ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
        'patrimonio' => $patrimonio_acumulado,
        'rendimentos' => $rendimento_acumulado
    ];

    // --- 9. SALDOS BANCÁRIOS E CARTÕES ---
    $saldos = ['bancarios' => [], 'cartoes' => []];

    $stmtBancos = $pdo->prepare("
        SELECT id, nome, tipo_conta, saldo, cheque_especial, limite_cartao, saldo_cartao
        FROM bancos
        WHERE usuario_id = :uid
        ORDER BY nome
    ");
    $stmtBancos->execute([':uid' => $usuario_id]);
    $contas_raw = $stmtBancos->fetchAll();

    foreach ($contas_raw as $conta) {
        if ($conta['tipo_conta'] === 'Dinheiro' || $conta['tipo_conta'] === 'Conta Corrente') {
            $saldo_real = $conta['saldo'] + $conta['cheque_especial'];
            $saldos['bancarios'][] = [
                'id' => $conta['id'],
                'nome' => $conta['nome'],
                'saldo' => number_format($saldo_real, 2, '.', '')
            ];
        }
        if (isset($conta['limite_cartao']) && $conta['limite_cartao'] > 0) {
            $saldo_disponivel_cartao = $conta['limite_cartao'] - $conta['saldo_cartao'];
            $saldos['cartoes'][] = [
                'id' => $conta['id'],
                'nome' => $conta['nome'],
                'saldo' => number_format($saldo_disponivel_cartao, 2, '.', ''),
                'limite_credito' => number_format($conta['limite_cartao'], 2, '.', ''),
                'credito_utilizado' => number_format($conta['saldo_cartao'], 2, '.', '')
            ];
        }
    }

    // === 10. INFOCARDS: ÚLTIMO MÊS (pago/recebido) e PRÓXIMO MÊS (previstos) ===
    // Períodos relativos ao HOJE:
    $primeiroDiaUltimoMes  = date('Y-m-01', strtotime('-1 month'));
    $ultimoDiaUltimoMes    = date('Y-m-t',  strtotime('-1 month'));
    $primeiroDiaProximoMes = date('Y-m-01', strtotime('+1 month'));
    $ultimoDiaProximoMes   = date('Y-m-t',  strtotime('+1 month'));

    // Valor Pago (Último Mês) -> despesas com data_pagamento preenchida no último mês
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor),0) AS pago
    FROM despesas
    WHERE usuario_id = :uid
      AND data_pagamento IS NOT NULL
      AND data_pagamento BETWEEN :ini AND :fim
");
$stmt->execute([':uid' => $usuario_id, ':ini' => $primeiroDiaUltimoMes, ':fim' => $ultimoDiaUltimoMes]);
$lastMonthPago = (float)$stmt->fetchColumn();

// Valor Recebido (Último Mês) -> receitas com data_recebimento preenchida no último mês
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor),0) AS recebido
    FROM receitas
    WHERE usuario_id = :uid
      AND data_recebimento IS NOT NULL
      AND data_recebimento BETWEEN :ini AND :fim
");
$stmt->execute([':uid' => $usuario_id, ':ini' => $primeiroDiaUltimoMes, ':fim' => $ultimoDiaUltimoMes]);
$lastMonthRecebido = (float)$stmt->fetchColumn();
    $stmt->execute([':uid' => $usuario_id, ':ini' => $primeiroDiaUltimoMes, ':fim' => $ultimoDiaUltimoMes]);
    $lastMonthRecebido = (float)$stmt->fetchColumn();

    // À Pagar (Próximo Mês) -> despesas previstas (data_compra) no próximo mês (independe do status)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor),0) AS previsto_despesas
        FROM despesas
        WHERE usuario_id = :uid
          AND data_compra BETWEEN :ini AND :fim
    ");
    $stmt->execute([':uid' => $usuario_id, ':ini' => $primeiroDiaProximoMes, ':fim' => $ultimoDiaProximoMes]);
    $nextMonthPrevistoDespesas = (float)$stmt->fetchColumn();

    // À Receber (Próximo Mês) -> receitas previstas (data_prevista_recebimento) no próximo mês (independe do status)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor),0) AS previsto_receitas
        FROM receitas
        WHERE usuario_id = :uid
          AND data_prevista_recebimento BETWEEN :ini AND :fim
    ");
    $stmt->execute([':uid' => $usuario_id, ':ini' => $primeiroDiaProximoMes, ':fim' => $ultimoDiaProximoMes]);
    $nextMonthPrevistoReceitas = (float)$stmt->fetchColumn();

    $lastMonth = [
        'pago'     => $lastMonthPago,
        'recebido' => $lastMonthRecebido
    ];
    $nextMonth = [
        'previsto_despesas' => $nextMonthPrevistoDespesas,
        'previsto_receitas' => $nextMonthPrevistoReceitas
    ];

    // --- MONTA A RESPOSTA FINAL ---
    echo json_encode([
        'kpi' => $kpi_data,
        'realizados' => $realizados_data,
        'annualChart' => $annual_chart_data,
        'investments' => $investments_data,
        'latestTransactions' => $ultimos_lancamentos,
        'investmentChart' => $investment_chart_data,
        'expensesByCategory' => $expensesByCategory,
        'incomesByCategory' => $incomesByCategory,
        'expensesByFamilyMember' => $expensesByFamilyMember,
        'incomesByFamilyMember' => $incomesByFamilyMember,
        'saldos' => $saldos,
        // >>> Novos blocos para os 4 InfoCards:
        'lastMonth' => $lastMonth,
        'nextMonth' => $nextMonth
    ]);

} catch (\Throwable $e) {
    $httpCode = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode);
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>