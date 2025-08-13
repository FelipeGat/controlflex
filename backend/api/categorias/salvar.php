<?php
// CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

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

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$nome = trim($data['nome'] ?? '');
$tipo = trim($data['tipo'] ?? '');
$icone = trim($data['icone'] ?? '');

if (!$nome || !$tipo || !$icone) {
    http_response_code(400);
    echo json_encode(['erro' => 'Todos os campos são obrigatórios.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO categorias (nome, tipo, icone) VALUES (:nome, :tipo, :icone)");
    $stmt->execute([
        ':nome' => $nome,
        ':tipo' => $tipo,
        ':icone' => $icone
    ]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria salva com sucesso.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar categoria: ' . $e->getMessage()]);
}
