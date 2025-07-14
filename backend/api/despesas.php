<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once '../conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["erro" => "Método não suportado"]);
    exit;
}

$usuarioId = $_GET['usuario_id'] ?? null;
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;

if (!$usuarioId || !$inicio || !$fim) {
    echo json_encode(["erro" => "Parâmetros insuficientes"]);
    exit;
}

$sql = "
    SELECT d.*, c.nome AS categoria_nome
    FROM despesas d
    LEFT JOIN categorias c ON d.categoria_id = c.id
    WHERE d.usuario_id = ?
    AND DATE(d.data_compra) BETWEEN ? AND ?
    ORDER BY d.data_compra DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $usuarioId, $inicio, $fim);
$stmt->execute();
$result = $stmt->get_result();

$despesas = [];
while ($row = $result->fetch_assoc()) {
    $despesas[] = $row;
}

echo json_encode($despesas);
