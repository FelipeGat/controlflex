<?php
// Headers CORS — permite acesso do frontend em localhost:3000
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Responder preflight OPTIONS
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Buscar bancos do usuário
    if (!isset($_GET['usuario_id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Usuário não especificado']);
        exit;
    }

    $usuario_id = $conn->real_escape_string($_GET['usuario_id']);
    $sql = "SELECT * FROM bancos WHERE usuario_id = '$usuario_id'";
    $result = $conn->query($sql);

    $bancos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bancos[] = $row;
        }
    }

    echo json_encode($bancos);
    $conn->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebe dados JSON
    $data = json_decode(file_get_contents('php://input'), true);

    if (
        !isset($data['nome']) ||
        !isset($data['usuario_id'])
    ) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados incompletos']);
        exit;
    }

    $nome = $conn->real_escape_string($data['nome']);
    $agencia = isset($data['agencia']) ? $conn->real_escape_string($data['agencia']) : null;
    $conta = isset($data['conta']) ? $conn->real_escape_string($data['conta']) : null;
    $saldo = isset($data['saldo']) ? floatval($data['saldo']) : 0;
    $limite_cartao = isset($data['limite_cartao']) ? floatval($data['limite_cartao']) : 0;
    $cheque_especial = isset($data['cheque_especial']) ? floatval($data['cheque_especial']) : 0;
    $icone = isset($data['icone']) ? $conn->real_escape_string($data['icone']) : null;
    $usuario_id = intval($data['usuario_id']);

    $sql = "INSERT INTO bancos (nome, agencia, conta, saldo, limite_cartao, cheque_especial, icone, usuario_id)
            VALUES ('$nome', '$agencia', '$conta', $saldo, $limite_cartao, $cheque_especial, '$icone', $usuario_id)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            'sucesso' => true,
            'id' => $conn->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar banco: ' . $conn->error]);
    }

    $conn->close();
    exit();
}

// Se método não suportado
http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
