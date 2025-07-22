<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Detecta ambiente
$isLocalhost = $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocalhost) {
    $dbhost = "localhost";
    $dbname = "controleflex";
    $dbuser = "root";
    $dbpass = "";
} else {
    $dbhost = "localhost";
    $dbname = "inves783_controleflex";
    $dbuser = "inves783_control";
    $dbpass = "100%Control!!";
}

try {
    $conn = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()]);
    exit;
}

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// PUT: atualização futura
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['tipo'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID ou tipo da conta são inválidos.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'mensagem' => 'Funcionalidade de atualização a ser implementada.']);
    exit;
}

// GET: dashboard ou contas pendentes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Contas futuras (receber/pagar)
    if (isset($_GET['acao']) && $_GET['acao'] === 'listar_contas') {
        $contas = [];

        // Despesas (contas a pagar)
        $stmtDespesas = $conn->prepare("
            SELECT 
                d.id,
                'despesa' AS tipo,
                c.nome AS categoria_nome,
                d.quem_comprou AS fornecedor,
                d.onde_comprou AS familiar,
                d.valor,
                d.data_compra AS vencimento,
                d.observacoes,
                d.forma_pagamento
            FROM despesas d
            LEFT JOIN categorias c ON d.categoria_id = c.id
            WHERE d.data_compra >= CURDATE()
            ORDER BY d.data_compra ASC
        ");
        $stmtDespesas->execute();
        $contas = array_merge($contas, $stmtDespesas->fetchAll(PDO::FETCH_ASSOC));

        // Receitas (contas a receber)
        $stmtReceitas = $conn->prepare("
            SELECT 
                r.id,
                'receita' AS tipo,
                c.nome AS categoria_nome,
                r.origem_receita AS fornecedor,
                r.quem_recebeu AS familiar,
                r.valor,
                r.data_recebimento AS vencimento,
                r.observacoes,
                r.forma_recebimento AS forma_pagamento
            FROM receitas r
            LEFT JOIN categorias c ON r.categoria_id = c.id
            WHERE r.data_recebimento >= CURDATE()
            ORDER BY r.data_recebimento ASC
        ");
        $stmtReceitas->execute();
        $contas = array_merge($contas, $stmtReceitas->fetchAll(PDO::FETCH_ASSOC));

        // Ordenar por vencimento
        usort($contas, function ($a, $b) {
            return strtotime($a['vencimento']) - strtotime($b['vencimento']);
        });

        echo json_encode($contas);
        exit;
    }

    // Dados principais do dashboard
    $inicio = $_GET['inicio'] ?? date('Y-m-01');
    $fim = $_GET['fim'] ?? date('Y-m-t');
    $ano_selecionado = date('Y', strtotime($inicio));

    function executeQuery($conn, $sql, $params = []) {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Receitas e despesas do período
    $receitas_periodo = executeQuery($conn, "SELECT * FROM receitas WHERE data_recebimento BETWEEN ? AND ?", [$inicio, $fim]);

    $despesas_periodo = executeQuery($conn, "
        SELECT d.*, c.nome as categoria_nome
        FROM despesas d
        LEFT JOIN categorias c ON d.categoria_id = c.id
        WHERE d.data_compra BETWEEN ? AND ?
    ", [$inicio, $fim]);

    // Gráfico anual (por mês)
    $sqlChart = "
        SELECT m.mes AS mes_num, COALESCE(r.total, 0) AS receitas, COALESCE(d.total, 0) AS despesas
        FROM (SELECT 1 AS mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
              UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS m
        LEFT JOIN (
            SELECT MONTH(data_recebimento) AS mes, SUM(valor) AS total
            FROM receitas WHERE YEAR(data_recebimento) = ? GROUP BY mes
        ) r ON m.mes = r.mes
        LEFT JOIN (
            SELECT MONTH(data_compra) AS mes, SUM(valor) AS total
            FROM despesas WHERE YEAR(data_compra) = ? GROUP BY mes
        ) d ON m.mes = d.mes
        ORDER BY m.mes
    ";
    $annual_data_raw = executeQuery($conn, $sqlChart, [$ano_selecionado, $ano_selecionado]);

    $annual_chart_data = [
        'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        'receitas' => array_column($annual_data_raw, 'receitas'),
        'despesas' => array_column($annual_data_raw, 'despesas')
    ];

    echo json_encode([
        'receitas' => $receitas_periodo,
        'despesas' => $despesas_periodo,
        'annualChart' => $annual_chart_data
    ]);
    exit;
}

// Método não permitido
http_response_code(405);
echo json_encode(['erro' => 'Método não suportado.']);
