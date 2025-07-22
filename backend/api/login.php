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

// Detectar ambiente local ou produção
$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocal) {
    $dbHost = 'localhost';
    $dbName = 'controleflex';
    $dbUser = 'root';
    $dbPass = '';
} else {
    $dbHost = 'localhost';
    $dbName = 'inves783_controleflex';
    $dbUser = 'inves783_control';
    $dbPass = '100%Control!!';
}

// Criar conexão PDO
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro na conexão com o banco de dados']);
    exit;
}

// Recebe dados JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['usuario']) || !isset($data['senha'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados incompletos']);
    exit;
}

$email = $data['usuario'];
$senha = $data['senha'];

try {
    // Busca o usuário pelo e-mail
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($senha, $user['senha'])) {
            echo json_encode([
                'sucesso' => true,
                'msg' => 'Login bem-sucedido',
                'id' => $user['id'],
                'email' => $user['email'],
                'nome' => $user['nome'],
                'foto' => $user['foto']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['erro' => 'Senha incorreta']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Usuário não encontrado']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao consultar o banco de dados']);
}
