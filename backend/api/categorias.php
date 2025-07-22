<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Detecta ambiente
$isLocalhost = $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocalhost) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "controleflex";
} else {
    $servername = "localhost";
    $username = "inves783_control";
    $password = "100%Control!!";
    $dbname = "inves783_controleflex";
}

// Conexão
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco de dados']);
    exit;
}

// === GET: listar categorias ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, nome, tipo, icone FROM categorias ORDER BY tipo, nome";
    $result = $conn->query($sql);
    $categorias = [];

    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }

    echo json_encode($categorias);
    $conn->close();
    exit;
}

// === POST: adicionar categoria ===
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

    $conn->close();
    exit;
}

// Se chegou aqui, método não permitido
http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
