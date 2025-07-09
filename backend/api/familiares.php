<?php
// Headers CORS — permite acesso do frontend
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Responder preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = 'localhost';
$db = 'controleflex';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro na conexão com o banco']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    $usuario_id = $_GET['usuario_id'] ?? null;
    if (!$usuario_id) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do usuário é obrigatório']);
        exit;
    }

    $sql = "SELECT * FROM familiares WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $familiares = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $row['foto'] = 'http://localhost/ControleFlex/backend/uploads/' . $row['foto'];
        }
        $familiares[] = $row;
    }

    echo json_encode($familiares);
    exit;
}

if ($metodo === 'POST') {
    // Usando FormData, vem via $_POST e $_FILES
    $nome = $_POST['nome'] ?? '';
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $salario = $_POST['salario'] !== '' ? floatval($_POST['salario']) : null;
    $banco = $_POST['banco'] ?? null;
    $limiteCartao = $_POST['limiteCartao'] !== '' ? floatval($_POST['limiteCartao']) : null;
    $limiteCheque = $_POST['limiteCheque'] !== '' ? floatval($_POST['limiteCheque']) : null;
    $alertaGastos = $_POST['alertaGastos'] !== '' ? floatval($_POST['alertaGastos']) : null;

    // Upload de imagem
    $foto_nome = null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nome = uniqid() . '.' . $ext;
        $destino = __DIR__ . '/uploads/' . $foto_nome;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            http_response_code(500);
            echo json_encode(['erro' => 'Falha no upload da foto']);
            exit;
        }
    }

    $sql = "INSERT INTO familiares (nome, usuario_id, salario, banco, limiteCartao, limiteCheque, alertaGastos, foto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Tipos para bind_param:
    // nome (string), usuario_id (int), salario (double), banco (string), limiteCartao (double), limiteCheque (double), alertaGastos (double), foto (string)
    // Para valores nulos passamos NULL, para bind_param usamos "s" e passamos null no PHP
    $stmt->bind_param(
        "sdsdddss",
        $nome,
        $usuario_id,
        $salario,
        $banco,
        $limiteCartao,
        $limiteCheque,
        $alertaGastos,
        $foto_nome
    );

    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode([
            'sucesso' => true,
            'familiar' => [
                'nome' => $nome,
                'salario' => $salario,
                'banco' => $banco,
                'limiteCartao' => $limiteCartao,
                'limiteCheque' => $limiteCheque,
                'alertaGastos' => $alertaGastos,
                'foto' => $foto_nome ? 'http://localhost/ControleFlex/backend/uploads/' . $foto_nome : null
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao inserir familiar: ' . $stmt->error]);
    }

    exit;
}

// Se método não suportado
http_response_code(405);
echo json_encode(['erro' => 'Método não permitido']);
