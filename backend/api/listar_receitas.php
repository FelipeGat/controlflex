<?php
header('Content-Type: application/json');
require_once '../conexao.php';

if (!isset($_GET['usuario_id'])) {
  echo json_encode([]);
  exit;
}

$usuario_id = intval($_GET['usuario_id']);

// Se houver parâmetros de data, adiciona o filtro
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;

$sql = "SELECT r.id, r.quem_recebeu, r.origem_receita, r.categoria_id, r.forma_recebimento, r.valor, 
               r.data_recebimento, r.recorrente, r.observacoes, c.nome AS categoria_nome
        FROM receitas r
        LEFT JOIN categorias c ON r.categoria_id = c.id
        WHERE r.usuario_id = ?";

// Adiciona filtro de data, se fornecido
if ($inicio && $fim) {
  $sql .= " AND r.data_recebimento BETWEEN ? AND ?";
}

$sql .= " ORDER BY r.data_recebimento DESC";

$stmt = $conn->prepare($sql);

if ($inicio && $fim) {
  $stmt->bind_param("iss", $usuario_id, $inicio, $fim);
} else {
  $stmt->bind_param("i", $usuario_id);
}

$stmt->execute();
$result = $stmt->get_result();

$receitas = [];
while ($row = $result->fetch_assoc()) {
  $receitas[] = $row;
}

echo json_encode($receitas);
