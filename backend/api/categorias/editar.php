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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = intval($data['id'] ?? 0);
$nome = trim($data['nome'] ?? '');
$tipo = trim($data['tipo'] ?? '');
$icone = trim($data['icone'] ?? '');

if (!$id || !$nome || !$tipo || !$icone) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados incompletos.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE categorias SET nome = :nome, tipo = :tipo, icone = :icone WHERE id = :id");
    $stmt->execute([
        ':id' => $id,
        ':nome' => $nome,
        ':tipo' => $tipo,
        ':icone' => $icone
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria atualizada com sucesso.']);
    } else {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Nenhuma alteração feita.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao editar categoria: ' . $e->getMessage()]);
}
