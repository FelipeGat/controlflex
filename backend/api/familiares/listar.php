<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->query("
        SELECT id, nome, foto, renda_total, limiteCartao, limiteCheque
        FROM familiares 
        ORDER BY nome ASC
    ");
    $familiares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($familiares);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar familiares: ' . $e->getMessage()]);
}
