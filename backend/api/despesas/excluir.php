<?php
// Permite acesso de qualquer origem (ajuste se quiser restringir)
header("Access-Control-Allow-Origin: *");

// Permite os métodos POST, DELETE e OPTIONS
header("Access-Control-Allow-Methods: POST, DELETE, OPTIONS");

// Permite os headers Content-Type e Authorization (se usar)
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responde à requisição OPTIONS (preflight) para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Só aceita POST para exclusão (pode ajustar se quiser aceitar DELETE)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

// Lê o JSON enviado no corpo da requisição
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID não fornecido']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->prepare("DELETE FROM despesas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['sucesso' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Despesa não encontrada']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao excluir despesa: ' . $e->getMessage()]);
}
