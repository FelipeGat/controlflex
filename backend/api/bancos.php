<?php
// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Responder preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === Conexão com o banco (automática: local ou produção) ===
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

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco de dados']);
    exit;
}

// === GET: Listar bancos do usuário ===
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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

// === POST: Inserir novo banco ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['nome']) || !isset($data['usuario_id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados incompletos']);
        exit;
    }

    $nome = $conn->real_escape_string($data['nome']);
    $codigo_banco = isset($data['codigo_banco']) ? $conn->real_escape_string($data['codigo_banco']) : null;
    $agencia = isset($data['agencia']) ? $conn->real_escape_string($data['agencia']) : null;
    $conta = isset($data['conta']) ? $conn->real_escape_string($data['conta']) : null;
    $saldo = isset($data['saldo']) ? floatval($data['saldo']) : 0;
    $limite_cartao = isset($data['limite_cartao']) ? floatval($data['limite_cartao']) : 0;
    $cheque_especial = isset($data['cheque_especial']) ? floatval($data['cheque_especial']) : 0;
    $icone = isset($data['icone']) ? $conn->real_escape_string($data['icone']) : null;
    $usuario_id = intval($data['usuario_id']);

    $sql = "INSERT INTO bancos (nome, codigo_banco, agencia, conta, saldo, limite_cartao, cheque_especial, icone, usuario_id)
            VALUES ('$nome', '$codigo_banco', '$agencia', '$conta', $saldo, $limite_cartao, $cheque_especial, '$icone', $usuario_id)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['sucesso' => true, 'id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar banco: ' . $conn->error]);
    }

    $conn->close();
    exit();
}

// === PUT: Atualizar banco existente ===
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || !isset($data['nome']) || !isset($data['usuario_id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados incompletos']);
        exit;
    }

    $id = intval($data['id']);
    $nome = $conn->real_escape_string($data['nome']);
    $codigo_banco = isset($data['codigo_banco']) ? $conn->real_escape_string($data['codigo_banco']) : null;
    $agencia = isset($data['agencia']) ? $conn->real_escape_string($data['agencia']) : null;
    $conta = isset($data['conta']) ? $conn->real_escape_string($data['conta']) : null;
    $saldo = isset($data['saldo']) ? floatval($data['saldo']) : 0;
    $limite_cartao = isset($data['limite_cartao']) ? floatval($data['limite_cartao']) : 0;
    $cheque_especial = isset($data['cheque_especial']) ? floatval($data['cheque_especial']) : 0;
    $icone = isset($data['icone']) ? $conn->real_escape_string($data['icone']) : null;

    $sql = "UPDATE bancos SET 
                nome = '$nome',
                codigo_banco = '$codigo_banco',
                agencia = '$agencia',
                conta = '$conta',
                saldo = $saldo,
                limite_cartao = $limite_cartao,
                cheque_especial = $cheque_especial,
                icone = '$icone'
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['sucesso' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao atualizar banco: ' . $conn->error]);
    }

    $conn->close();
    exit();
}

// === DELETE: Excluir banco ===
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID não especificado']);
        exit;
    }

    $id = intval($_GET['id']);
    $sql = "DELETE FROM bancos WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['sucesso' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao excluir banco: ' . $conn->error]);
    }

    $conn->close();
    exit();
}

// Método não permitido
http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
