<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS"); // Permitir GET e PUT
header("Content-Type: application/json; charset=UTF-8");

// Usaremos PDO, que você já estava usando, é uma ótima escolha.
$conn = new PDO("mysql:host=localhost;dbname=controleflex;charset=utf8", "root", "");

// --- ROTEAMENTO DA API ---

// Preflight para requisições PUT/POST (necessário para CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Se a requisição for do tipo PUT, trata a atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['tipo'])) {
        http_response_code(400 ); // Bad Request
        echo json_encode(['erro' => 'ID ou tipo da conta são inválidos.']);
        exit;
    }

    // Assumindo que você vai adicionar uma coluna 'status' no futuro
    // Se não tiver a coluna 'status', esta parte vai dar erro.
    // Por enquanto, vou comentar para não quebrar o código.
    /*
    if ($data['tipo'] === 'pagar') {
        $stmt = $conn->prepare("UPDATE despesas SET status = 'pago' WHERE id = ?");
        $stmt->execute([$data['id']]);
    } else {
        $stmt = $conn->prepare("UPDATE receitas SET status = 'recebido' WHERE id = ?");
        $stmt->execute([$data['id']]);
    }
    */

    // Se você não tem a coluna 'status', o PUT não tem o que fazer ainda.
    // Vamos retornar um sucesso temporário.
    echo json_encode(['sucesso' => true, 'mensagem' => 'Funcionalidade de atualização a ser implementada.']);
    exit;
}

// Se a requisição for do tipo GET, decide o que fazer com base nos parâmetros
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Se um parâmetro 'acao' com valor 'listar_contas' for passado, lista as contas pendentes
    if (isset($_GET['acao']) && $_GET['acao'] === 'listar_contas') {
        $contas = [];

        // Despesas pendentes (cuja data ainda não passou)
        $stmtDespesas = $conn->prepare("
          SELECT id, 'pagar' AS tipo, onde_comprou AS descricao, valor, data_compra AS vencimento
          FROM despesas WHERE data_compra >= CURDATE() ORDER BY data_compra ASC
        ");
        $stmtDespesas->execute();
        $contas = array_merge($contas, $stmtDespesas->fetchAll(PDO::FETCH_ASSOC));

        // Receitas pendentes (cuja data ainda não passou)
        $stmtReceitas = $conn->prepare("
          SELECT id, 'receber' AS tipo, origem_receita AS descricao, valor, data_recebimento AS vencimento
          FROM receitas WHERE data_recebimento >= CURDATE() ORDER BY data_recebimento ASC
        ");
        $stmtReceitas->execute();
        $contas = array_merge($contas, $stmtReceitas->fetchAll(PDO::FETCH_ASSOC));

        // Ordena a lista final por data de vencimento
        usort($contas, function($a, $b) {
            return strtotime($a['vencimento']) - strtotime($b['vencimento']);
        });

        echo json_encode($contas);
        exit;
    }

    // --- LÓGICA PADRÃO: BUSCAR DADOS PARA OS GRÁFICOS DO DASHBOARD ---
    
    $inicio = $_GET['inicio'] ?? date('Y-m-01');
    $fim = $_GET['fim'] ?? date('Y-m-t');
    $ano_selecionado = date('Y', strtotime($inicio));

    // Função auxiliar para executar queries
    function executeQuery($conn, $sql, $params = []) {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dados do período filtrado
    $receitas_periodo = executeQuery($conn, "SELECT * FROM receitas WHERE data_recebimento BETWEEN ? AND ?", [$inicio, $fim]);
    $despesas_periodo = executeQuery($conn, "SELECT d.*, c.nome as categoria_nome FROM despesas d LEFT JOIN categorias c ON d.categoria_id = c.id WHERE d.data_compra BETWEEN ? AND ?", [$inicio, $fim]);

    // Dados para o gráfico anual
    $sqlChart = "
      SELECT m.mes AS mes_num, COALESCE(r.total, 0) AS receitas, COALESCE(d.total, 0) AS despesas
      FROM (SELECT 1 AS mes UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) AS m
      LEFT JOIN (SELECT MONTH(data_recebimento) AS mes, SUM(valor) AS total FROM receitas WHERE YEAR(data_recebimento) = ? GROUP BY mes) r ON m.mes = r.mes
      LEFT JOIN (SELECT MONTH(data_compra) AS mes, SUM(valor) AS total FROM despesas WHERE YEAR(data_compra) = ? GROUP BY mes) d ON m.mes = d.mes
      ORDER BY m.mes
    ";
    $annual_data_raw = executeQuery($conn, $sqlChart, [$ano_selecionado, $ano_selecionado]);

    $annual_chart_data = [
        'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        'receitas' => array_column($annual_data_raw, 'receitas'),
        'despesas' => array_column($annual_data_raw, 'despesas')
    ];

    // Montagem final do JSON para o dashboard
    echo json_encode([
        'receitas' => $receitas_periodo,
        'despesas' => $despesas_periodo,
        'annualChart' => $annual_chart_data
    ]);
    exit;
}

// Se nenhum dos métodos acima for correspondido
http_response_code(405 ); // Method Not Allowed
echo json_encode(['erro' => 'Método não suportado.']);
?>
