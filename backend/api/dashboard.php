<?php
// /api/dashboard.php

header("Access-Control-Allow-Origin: http://localhost:3000" );
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200 );
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
    $fim = $_GET['fim'] ?? date('Y-m-t');
    $ano_selecionado = date('Y', strtotime($inicio));

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- 1. DADOS PARA OS CARDS PRINCIPAIS (KPIs) ---
    $stmtCards = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = :uid1 AND data_prevista_recebimento BETWEEN :inicio1 AND :fim1) as total_receitas,
            (SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = :uid2 AND data_compra BETWEEN :inicio2 AND :fim2) as total_despesas
    ");
    $stmtCards->execute([':uid1' => $usuario_id, ':inicio1' => $inicio, ':fim1' => $fim, ':uid2' => $usuario_id, ':inicio2' => $inicio, ':fim2' => $fim]);
    $kpi_data = $stmtCards->fetch();
    
    $inicio_anterior = date('Y-m-d', strtotime($inicio . ' -1 month'));
    $fim_anterior = date('Y-m-d', strtotime($fim . ' -1 month'));
    
    $stmtCardsAnterior = $pdo->prepare("
        SELECT
            (SELECT COALESCE(SUM(valor), 0) FROM receitas WHERE usuario_id = :uid1 AND data_prevista_recebimento BETWEEN :inicio1 AND :fim1) as total_receitas,
            (SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE usuario_id = :uid2 AND data_compra BETWEEN :inicio2 AND :fim2) as total_despesas
    ");
    $stmtCardsAnterior->execute([':uid1' => $usuario_id, ':inicio1' => $inicio_anterior, ':fim1' => $fim_anterior, ':uid2' => $usuario_id, ':inicio2' => $inicio_anterior, ':fim2' => $fim_anterior]);
    $kpi_data_anterior = $stmtCardsAnterior->fetch();
    
    $calculate_variation = fn($current, $previous) => ($previous == 0) ? ($current > 0 ? 100.0 : 0.0) : (($current - $previous) / abs($previous)) * 100;
    
    $kpi_data['saldo'] = $kpi_data['total_receitas'] - $kpi_data['total_despesas'];
    $kpi_data_anterior['saldo'] = $kpi_data_anterior['total_receitas'] - $kpi_data_anterior['total_despesas'];
    $kpi_data['variacao_receitas'] = round($calculate_variation($kpi_data['total_receitas'], $kpi_data_anterior['total_receitas']), 2);
    $kpi_data['variacao_despesas'] = round($calculate_variation($kpi_data['total_despesas'], $kpi_data_anterior['total_despesas']), 2);
    $kpi_data['variacao_saldo'] = round($calculate_variation($kpi_data['saldo'], $kpi_data_anterior['saldo']), 2);

    // --- 2. GRÁFICO ANUAL (FLUXO DE CAIXA) ---
    $stmtAnnual = $pdo->prepare("
        SELECT m.mes AS mes_num, COALESCE(r.total, 0) AS receitas, COALESCE(d.total, 0) AS despesas
        FROM (SELECT 1 AS mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS m
        LEFT JOIN (SELECT MONTH(data_prevista_recebimento) AS mes, SUM(valor) AS total FROM receitas WHERE usuario_id = :uid1 AND YEAR(data_prevista_recebimento) = :ano1 GROUP BY mes) r ON m.mes = r.mes
        LEFT JOIN (SELECT MONTH(data_compra) AS mes, SUM(valor) AS total FROM despesas WHERE usuario_id = :uid2 AND YEAR(data_compra) = :ano2 GROUP BY mes) d ON m.mes = d.mes
        ORDER BY m.mes
    ");
    $stmtAnnual->execute([':uid1' => $usuario_id, ':ano1' => $ano_selecionado, ':uid2' => $usuario_id, ':ano2' => $ano_selecionado]);
    $annual_data_raw = $stmtAnnual->fetchAll();
    $annual_chart_data = ['labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], 'receitas' => array_column($annual_data_raw, 'receitas'), 'despesas' => array_column($annual_data_raw, 'despesas')];

    // --- 3. GRÁFICOS DE PIZZA ---
    // Despesas por Categoria
    $stmtExpCat = $pdo->prepare("SELECT c.nome, SUM(d.valor) as total FROM despesas d JOIN categorias c ON d.categoria_id = c.id WHERE d.usuario_id = :uid AND d.data_compra BETWEEN :inicio AND :fim GROUP BY c.nome HAVING total > 0 ORDER BY total DESC");
    $stmtExpCat->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $expensesByCategory = $stmtExpCat->fetchAll();

    // Receitas por Categoria
    $stmtIncCat = $pdo->prepare("SELECT c.nome, SUM(r.valor) as total FROM receitas r JOIN categorias c ON r.categoria_id = c.id WHERE r.usuario_id = :uid AND r.data_prevista_recebimento BETWEEN :inicio AND :fim GROUP BY c.nome HAVING total > 0 ORDER BY total DESC");
    $stmtIncCat->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $incomesByCategory = $stmtIncCat->fetchAll();

    // Despesas por Familiar
    $stmtExpFam = $pdo->prepare("SELECT COALESCE(f.nome, 'Não especificado') as nome, SUM(d.valor) as total FROM despesas d LEFT JOIN familiares f ON d.quem_comprou = f.id WHERE d.usuario_id = :uid AND d.data_compra BETWEEN :inicio AND :fim GROUP BY nome HAVING total > 0 ORDER BY total DESC");
    $stmtExpFam->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $expensesByFamilyMember = $stmtExpFam->fetchAll();

    // Receitas por Familiar
    $stmtIncFam = $pdo->prepare("SELECT COALESCE(f.nome, 'Não especificado') as nome, SUM(r.valor) as total FROM receitas r LEFT JOIN familiares f ON r.quem_recebeu = f.id WHERE r.usuario_id = :uid AND r.data_prevista_recebimento BETWEEN :inicio AND :fim GROUP BY nome HAVING total > 0 ORDER BY total DESC");
    $stmtIncFam->execute([':uid' => $usuario_id, ':inicio' => $inicio, ':fim' => $fim]);
    $incomesByFamilyMember = $stmtIncFam->fetchAll();

    // --- 4. DADOS DE INVESTIMENTOS ---
    $stmtInvestimentos = $pdo->prepare("SELECT COALESCE(SUM(valor_aportado), 0) as total_investido FROM investimentos WHERE usuario_id = :uid");
    $stmtInvestimentos->execute([':uid' => $usuario_id]);
    $investments_data = $stmtInvestimentos->fetch();

    // --- 5. ÚLTIMOS LANÇAMENTOS ---
    $stmtLancamentos = $pdo->prepare("
        (SELECT r.id, 'receita' as tipo, r.valor, r.data_prevista_recebimento as data, c.nome as categoria_nome FROM receitas r LEFT JOIN categorias c ON r.categoria_id = c.id WHERE r.usuario_id = :uid1)
        UNION ALL
        (SELECT d.id, 'despesa' as tipo, d.valor, d.data_compra as data, c.nome as categoria_nome FROM despesas d LEFT JOIN categorias c ON d.categoria_id = c.id WHERE d.usuario_id = :uid2)
        ORDER BY data DESC LIMIT 10
    ");
    $stmtLancamentos->execute([':uid1' => $usuario_id, ':uid2' => $usuario_id]);
    $ultimos_lancamentos = $stmtLancamentos->fetchAll();

    // --- 6. GRÁFICO DE EVOLUÇÃO PATRIMONIAL ---
    $stmtInvestChart = $pdo->prepare("
        SELECT m.mes, COALESCE(i.aportes_mes, 0) AS aportes_mes FROM 
            (SELECT 1 AS mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS m
        LEFT JOIN (SELECT MONTH(data_aporte) AS mes, SUM(valor_aportado) AS aportes_mes FROM investimentos WHERE usuario_id = :uid AND YEAR(data_aporte) = :ano GROUP BY MONTH(data_aporte)) i ON m.mes = i.mes
        ORDER BY m.mes ASC
    ");
    $stmtInvestChart->execute([':uid' => $usuario_id, ':ano' => $ano_selecionado]);
    $invest_data_raw = $stmtInvestChart->fetchAll();
    
    $patrimonio_acumulado = [];
    $rendimento_acumulado = [];
    $saldo_anterior = 0;
    $taxa_rendimento_mensal = 0.01; // SIMULAÇÃO: 1% ao mês

    foreach ($invest_data_raw as $row) {
        $rendimento_mes = $saldo_anterior * $taxa_rendimento_mensal;
        $saldo_atual = $saldo_anterior + $row['aportes_mes'] + $rendimento_mes;
        array_push($patrimonio_acumulado, round($saldo_atual, 2));
        array_push($rendimento_acumulado, round($rendimento_mes, 2));
        $saldo_anterior = $saldo_atual;
    }
    $investment_chart_data = ['labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], 'patrimonio' => $patrimonio_acumulado, 'rendimentos' => $rendimento_acumulado];

    // --- MONTA A RESPOSTA FINAL ---
    echo json_encode([
        'kpi' => $kpi_data,
        'annualChart' => $annual_chart_data,
        'investments' => $investments_data,
        'latestTransactions' => $ultimos_lancamentos,
        'investmentChart' => $investment_chart_data,
        // Novos dados para os gráficos de pizza
        'expensesByCategory' => $expensesByCategory,
        'incomesByCategory' => $incomesByCategory,
        'expensesByFamilyMember' => $expensesByFamilyMember,
        'incomesByFamilyMember' => $incomesByFamilyMember,
    ]);

} catch (\Throwable $e) {
    $httpCode = is_int($e->getCode( )) && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
