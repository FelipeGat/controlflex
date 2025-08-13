<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once(__DIR__ . '/../../config/db.php');

$usuario_id = $_GET['usuario_id'] ?? null;

if (!$usuario_id) {
  echo json_encode([]);
  exit;
}

$sql = "SELECT 
          d.id, 
          d.valor, 
          d.data_compra, 
          d.quem_comprou,  -- Adicionado para fallback no frontend
          f.nome AS quem_comprou_nome, 
          c.nome AS categoria 
        FROM despesas d
        LEFT JOIN familiares f ON f.id = d.quem_comprou
        LEFT JOIN categorias c ON c.id = d.categoria_id
        WHERE d.usuario_id = :usuario_id
        ORDER BY d.data_compra DESC, d.id DESC
        LIMIT 10";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();

$despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($despesas);
