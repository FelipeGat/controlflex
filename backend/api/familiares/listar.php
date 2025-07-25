<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/db.php';

try {
    if (isset($_GET['id'])) {
        // Buscar apenas 1 familiar
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT id, nome, foto, renda_total, limiteCartao, limiteCheque FROM familiares WHERE id = ?");
        $stmt->execute([$id]);
        $familiar = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($familiar) {
            echo json_encode($familiar);
        } else {
            echo json_encode(['erro' => 'Familiar não encontrado.']);
        }
    } else {
        // Listar todos
        $stmt = $pdo->query("SELECT id, nome, foto, renda_total, limiteCartao, limiteCheque FROM familiares ORDER BY nome ASC");
        $familiares = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($familiares);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar familiares: ' . $e->getMessage()]);
}
