<?php
// CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Conexão com PDO
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categorias);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar categorias: ' . $e->getMessage()]);
}
