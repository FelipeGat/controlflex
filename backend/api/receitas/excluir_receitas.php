<?php
// CORS: pré-flight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

// CORS: headers principais
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Verifica método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Lê JSON enviado
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID não fornecido']);
    exit;
}

// Conecta ao banco
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Receita excluída com sucesso']);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Receita não encontrada']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao excluir receita: ' . $e->getMessage()]);
}
