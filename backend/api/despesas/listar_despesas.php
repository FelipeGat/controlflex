<?php
// Headers para CORS e JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/db.php';

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;
if (!$usuario_id) {
    echo json_encode([]);
    exit;
}

$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;
$mes = $_GET['mes'] ?? null;
$ano = $_GET['ano'] ?? null;

// Se não houver filtro de data, definir mês e ano atuais como padrão
if (!$inicio && !$fim && !$mes && !$ano) {
    $mes = date('m');
    $ano = date('Y');
}

$where = "WHERE d.usuario_id = :usuario_id";
$params = [':usuario_id' => $usuario_id];

// Filtra por intervalo de datas (inicio e fim)
if ($inicio && $fim) {
    $where .= " AND d.data_compra BETWEEN :inicio AND :fim";
    $params[':inicio'] = $inicio;
    $params[':fim'] = $fim;
}
// Se não tem intervalo, filtra por mês e ano
elseif ($mes && $ano) {
    $where .= " AND MONTH(d.data_compra) = :mes AND YEAR(d.data_compra) = :ano";
    $params[':mes'] = intval($mes);
    $params[':ano'] = intval($ano);
}

$sql = "SELECT
            d.id,
            d.valor,
            d.data_compra,
            d.quem_comprou AS quem_comprou_id,
            f.nome AS quem_comprou_nome,
            d.onde_comprou AS onde_comprou_id,
            c.nome AS categoria_nome,
            d.categoria_id,
            d.forma_pagamento,
            d.observacoes,
            d.recorrente,
            d.parcelas,
            d.grupo_recorrencia_id
        FROM despesas d
        LEFT JOIN familiares f ON f.id = d.quem_comprou
        LEFT JOIN categorias c ON c.id = d.categoria_id
        $where
        ORDER BY d.data_compra DESC, d.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($despesas as &$d) {
        $d['valor'] = (float)$d['valor'];
        $d['recorrente'] = (int)$d['recorrente'];
        $d['grupo_recorrencia_id'] = $d['grupo_recorrencia_id'] ?? null;
    }

    echo json_encode($despesas);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar despesas: ' . $e->getMessage()]);
}
?>
