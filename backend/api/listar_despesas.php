<?php
header('Content-Type: application/json');
require_once '../conexao.php';

if (!isset($_GET['usuario_id'])) {
  echo json_encode([]);
  exit;
}

$usuario_id = intval($_GET['usuario_id']);

// Verifica se foram enviados parâmetros de data
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;

$sql = "SELECT d.id, d.quem_comprou, d.onde_comprou, d.categoria_id, d.forma_pagamento, d.valor, 
               d.data_compra, d.recorrente, d.observacoes, c.nome AS categoria_nome
        FROM despesas d
        LEFT JOIN categorias c ON d.categoria_id = c.id
        WHERE d.usuario_id = ?";

// Aplica filtro de datas, se enviado
if ($inicio && $fim) {
  $sql .= " AND d.data_compra BETWEEN ? AND ?";
}

$sql .= " ORDER BY d.data_compra DESC";

$stmt = $conn->prepare($sql);

if ($inicio && $fim) {
  $stmt->bind_param("iss", $usuario_id, $inicio, $fim);
} else {
  $stmt->bind_param("i", $usuario_id);
}

$stmt->execute();
$result = $stmt->get_result();

$despesas = [];
while ($row = $result->fetch_assoc()) {
  $despesas[] = $row;
}

echo json_encode($despesas);
