<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Conexão com banco
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "controleflex";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco de dados']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Retorna todas categorias
    $sql = "SELECT id, nome, tipo, icone FROM categorias ORDER BY tipo, nome";
    $result = $conn->query($sql);
    $categorias = [];

    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    echo json_encode($categorias);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['nome']) || !isset($data['tipo']) || !isset($data['icone'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados incompletos']);
        exit;
    }

    $nome = $conn->real_escape_string($data['nome']);
    $tipo = $conn->real_escape_string($data['tipo']); // 'receita' ou 'despesa'
    $icone = $conn->real_escape_string($data['icone']);

    $sql = "INSERT INTO categorias (nome, tipo, icone) VALUES ('$nome', '$tipo', '$icone')";
    if ($conn->query($sql)) {
        echo json_encode(['sucesso' => true, 'msg' => 'Categoria criada com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao criar categoria']);
    }
}

$conn->close();
