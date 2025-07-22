<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder OPTIONS para CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID não fornecido.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM rendas WHERE familiar_id = ?")->execute([$id]);
    // Corrigido para a tabela correta:
    $pdo->prepare("DELETE FROM familiares_bancos WHERE familiar_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM familiares WHERE id = ?")->execute([$id]);

    $pdo->commit();
    echo json_encode(['sucesso' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao excluir familiar: ' . $e->getMessage()]);
}
