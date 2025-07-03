<?php
// Cabeçalhos CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Responder pré-requisição (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexão com o banco
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

// Recebe dados JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['usuario']) || !isset($data['senha'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados incompletos']);
    exit;
}

$email = $conn->real_escape_string($data['usuario']);
$senha = $data['senha'];

// Busca o usuário pelo email
$sql = "SELECT * FROM usuarios WHERE email = '$email' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($senha, $user['senha'])) {
        echo json_encode([
            'sucesso' => true,
            'msg' => 'Login bem-sucedido',
            'email' => $user['email'],
            'nome' => $user['nome']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['erro' => 'Senha incorreta']);
    }
} else {
    http_response_code(404);
    echo json_encode(['erro' => 'Usuário não encontrado']);
}

$conn->close();
?>
