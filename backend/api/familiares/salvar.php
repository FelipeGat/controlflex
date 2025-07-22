<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/db.php';

// Verifica se é multipart/form-data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Validação e dados recebidos
$nome = $_POST['nome'] ?? '';
$rendaTotal = floatval($_POST['renda_total'] ?? 0);
$limiteCartao = floatval($_POST['limiteCartao'] ?? 0);
$limiteCheque = floatval($_POST['limiteCheque'] ?? 0);
$usuarioId = $_POST['usuario_id'] ?? null;
$id = $_POST['id'] ?? null;

// Validar campos obrigatórios
if (!$nome || (!$id && !$usuarioId)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nome e usuário são obrigatórios.']);
    exit;
}

// Upload da imagem (se houver)
$fotoNome = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $fotoNome = uniqid('foto_') . '.' . $ext;
    $destino = __DIR__ . '/../../uploads/' . $fotoNome;
    move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
}

try {
    if ($id) {
        // Atualizar
        $query = "UPDATE familiares SET nome = ?, renda_total = ?, limiteCartao = ?, limiteCheque = ?";
        $params = [$nome, $rendaTotal, $limiteCartao, $limiteCheque];

        if ($fotoNome) {
            $query .= ", foto = ?";
            $params[] = $fotoNome;
        }

        $query .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } else {
        // Inserir
        $query = "INSERT INTO familiares (nome, renda_total, limiteCartao, limiteCheque, foto, usuario_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nome, $rendaTotal, $limiteCartao, $limiteCheque, $fotoNome, $usuarioId]);
        $id = $pdo->lastInsertId();
    }

    echo json_encode(['sucesso' => true, 'id' => $id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar familiar: ' . $e->getMessage()]);
}
