<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

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

try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nome AS categoria_nome
        FROM receitas r
        LEFT JOIN categorias c ON r.categoria_id = c.id
        WHERE r.usuario_id = :usuario_id
        AND DATE(r.data_recebimento) BETWEEN :inicio AND :fim
        ORDER BY r.data_recebimento DESC
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':inicio' => $inicio,
        ':fim' => $fim
    ]);

    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($receitas);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao buscar receitas: " . $e->getMessage()]);
}
