<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["erro" => "Método não suportado"]);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["erro" => "Parâmetro 'id' é obrigatório"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT d.*, f.nome AS quem_comprou_nome, c.nome AS categoria_nome
        FROM despesas d
        LEFT JOIN familiares f ON f.id = d.quem_comprou
        LEFT JOIN categorias c ON c.id = d.categoria_id
        WHERE d.id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);

    $despesa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$despesa) {
        echo json_encode(["erro" => "Despesa não encontrada"]);
        exit;
    }

    echo json_encode($despesa);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao buscar despesa: " . $e->getMessage()]);
}
