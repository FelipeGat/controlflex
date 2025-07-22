<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Detectar ambiente
$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocal) {
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'controleflex';
} else {
    $dbHost = 'localhost';
    $dbUser = 'inves783_control';
    $dbPass = '100%Control!!';
    $dbName = 'inves783_controleflex';
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function getJsonInput() {
    $input = file_get_contents("php://input");
    return json_decode($input, true);
}

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

switch ($method) {
    case 'GET':
        $usuarioId = $_GET['usuario_id'] ?? null;
        if (!$usuarioId) {
            http_response_code(400);
            echo json_encode(['erro' => 'Usuário não informado']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM fornecedores WHERE usuario_id = ? ORDER BY id DESC");
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();

        $fornecedores = [];
        while ($row = $result->fetch_assoc()) {
            $fornecedores[] = $row;
        }

        echo json_encode($fornecedores);
        break;

    case 'POST':
        $data = getJsonInput();
        $nome = $data['nome'] ?? '';
        $contato = $data['contato'] ?? '';
        $cnpj = $data['cnpj'] ?? '';
        $observacoes = $data['observacoes'] ?? '';
        $usuarioId = isset($data['usuario_id']) ? (int)$data['usuario_id'] : 0;

        if (!$nome || !$usuarioId) {
            http_response_code(400);
            echo json_encode(['erro' => 'Campos obrigatórios ausentes']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO fornecedores (nome, contato, cnpj, observacoes, usuario_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $nome, $contato, $cnpj, $observacoes, $usuarioId);

        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true, 'id' => $stmt->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao salvar fornecedor']);
        }
        break;

    case 'PUT':
        $data = getJsonInput();
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $nome = $data['nome'] ?? '';
        $contato = $data['contato'] ?? '';
        $cnpj = $data['cnpj'] ?? '';
        $observacoes = $data['observacoes'] ?? '';

        if (!$id || !$nome) {
            http_response_code(400);
            echo json_encode(['erro' => 'Campos obrigatórios ausentes']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE fornecedores SET nome = ?, contato = ?, cnpj = ?, observacoes = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $nome, $contato, $cnpj, $observacoes, $id);

        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao atualizar fornecedor']);
        }
        break;

    case 'DELETE':
        $data = getJsonInput();
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID não informado']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao excluir fornecedor']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido']);
}

$conn->close();
