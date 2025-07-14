<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, PUT, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

$conn = new PDO("mysql:host=localhost;dbname=controleflex;charset=utf8", "root", "");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// GET: retorna contas pendentes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $contas = [];

    // Despesas pendentes
    $stmt = $conn->prepare("
      SELECT id, 'pagar' AS tipo, onde_comprou AS descricao, valor, data_compra AS vencimento
      FROM despesas
    ");
    $stmt->execute();
    $contas = array_merge($contas, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // Receitas pendentes
    $stmt = $conn->prepare("
      SELECT id, 'receber' AS tipo, origem_receita AS descricao, valor, data_recebimento AS vencimento
      FROM receitas
    ");
    $stmt->execute();
    $contas = array_merge($contas, $stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode($contas);
    exit;
}

// PUT: marca como paga/recebida
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['tipo'])) {
        echo json_encode(['erro' => 'ID ou tipo inválido']);
        exit;
    }

    if ($data['tipo'] === 'pagar') {
        $stmt = $conn->prepare("
            UPDATE despesas SET status = 'pago' WHERE id = ?
        ");
        $stmt->execute([$data['id']]);
    } else {
        $stmt = $conn->prepare("
            UPDATE receitas SET status = 'recebido' WHERE id = ?
        ");
        $stmt->execute([$data['id']]);
    }

    echo json_encode(['sucesso' => true]);
    exit;
}

echo json_encode(['erro' => 'Método não suportado']);
