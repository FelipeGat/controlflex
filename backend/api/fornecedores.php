<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$conn = new mysqli('localhost', 'root', '', 'controleflex');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        $usuarioId = $_GET['usuario_id'] ?? null;
        if (!$usuarioId) {
            echo json_encode(['erro' => 'Usuário não informado']);
            exit;
        }

        $sql = "SELECT * FROM fornecedores WHERE usuario_id = $usuarioId ORDER BY id DESC";
        $result = $conn->query($sql);
        $fornecedores = [];

        while ($row = $result->fetch_assoc()) {
            $fornecedores[] = $row;
        }

        echo json_encode($fornecedores);
        break;

    case 'POST':
        $nome = $conn->real_escape_string($data['nome']);
        $usuarioId = (int)$data['usuario_id'];

        if (!$nome || !$usuarioId) {
            echo json_encode(['erro' => 'Campos obrigatórios ausentes']);
            exit;
        }

        $sql = "INSERT INTO fornecedores (nome, usuario_id) VALUES ('$nome', $usuarioId)";
        if ($conn->query($sql)) {
            echo json_encode(['sucesso' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['erro' => 'Erro ao salvar fornecedor']);
        }
        break;

    case 'PUT':
        $id = (int)$data['id'];
        $nome = $conn->real_escape_string($data['nome']);

        if (!$id || !$nome) {
            echo json_encode(['erro' => 'Campos obrigatórios ausentes']);
            exit;
        }

        $sql = "UPDATE fornecedores SET nome = '$nome' WHERE id = $id";
        if ($conn->query($sql)) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['erro' => 'Erro ao atualizar fornecedor']);
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = (int)($_DELETE['id'] ?? 0);

        if (!$id) {
            echo json_encode(['erro' => 'ID não informado']);
            exit;
        }

        $sql = "DELETE FROM fornecedores WHERE id = $id";
        if ($conn->query($sql)) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['erro' => 'Erro ao excluir fornecedor']);
        }
        break;
}

$conn->close();
