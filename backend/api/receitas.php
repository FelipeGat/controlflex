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
    SELECT r.*, c.nome AS categoria_nome
    FROM receitas r
    LEFT JOIN categorias c ON r.categoria_id = c.id
    WHERE r.usuario_id = ?
    AND DATE(r.data_recebimento) BETWEEN ? AND ?
    ORDER BY r.data_recebimento DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $usuarioId, $inicio, $fim);
$stmt->execute();
$result = $stmt->get_result();

$receitas = [];
while ($row = $result->fetch_assoc()) {
    $receitas[] = $row;
}

echo json_encode($receitas);
