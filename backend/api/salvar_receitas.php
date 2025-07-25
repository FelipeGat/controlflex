<?php
// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
require_once '../conexao.php';

if (!isset($_GET['usuario_id'])) {
  echo json_encode([]);
  exit;
}

$usuario_id = intval($_GET['usuario_id']);

$sql = "SELECT r.* FROM receitas r 
        INNER JOIN categorias c ON r.categoria_id = c.id 
        WHERE c.tipo = 'receita' 
        AND r.usuario_id = ? 
        ORDER BY r.data_recebimento DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$receitas = [];
while ($row = $result->fetch_assoc()) {
  $receitas[] = $row;
}

echo json_encode($receitas);