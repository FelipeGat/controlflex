<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Detectar ambiente
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocalhost) {
    $host = 'localhost';
    $db   = 'controleflex';
    $user = 'root';
    $pass = '';
} else {
    $host = 'localhost';
    $db   = 'inves783_controleflex';
    $user = 'inves783_control';
    $pass = '100%Control!!';
}

// Conexão PDO
try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500 );
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao conectar com o banco de dados: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204 ); // No Content
    exit();
}

// Função para enviar resposta JSON e sair
function responderJson($data, $statusCode = 200) {
    http_response_code($statusCode );
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT id, nome, email, perfil, status, foto FROM usuarios WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        responderJson($usuario ?: ['erro' => 'Usuário não encontrado'], $usuario ? 200 : 404);
    } else {
        $stmt = $conn->prepare("SELECT id, nome, email, perfil, status, foto FROM usuarios");
        $stmt->execute();
        responderJson($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

if ($method === 'POST') { // Usaremos POST para criar e atualizar (com FormData)
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $email = $_POST['email'] ?? null;
    $senha = $_POST['senha'] ?? null;
    $perfil = $_POST['nivel'] ?? 'Padrão';
    $status = $_POST['status'] ?? 'Ativo';

    if (empty($nome) || empty($email)) {
        responderJson(['sucesso' => false, 'erro' => 'Nome e Email são obrigatórios.'], 400);
    }

    $senhaHash = !empty($senha) ? password_hash($senha, PASSWORD_DEFAULT) : null;
    $fotoNomeFinal = null;

    // --- Lógica de Upload de Foto ---
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/'; // Caminho corrigido para a pasta uploads
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                responderJson(['sucesso' => false, 'erro' => 'Falha ao criar diretório de uploads.'], 500);
            }
        }

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $fotoNomeFinal = 'user_' . uniqid() . '.' . $ext;
        $uploadPath = $uploadDir . $fotoNomeFinal;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
            responderJson(['sucesso' => false, 'erro' => 'Falha ao mover arquivo de upload.'], 500);
        }
    }

    if ($id) { // Atualização
        // Buscar foto antiga para exclusão
        if ($fotoNomeFinal) { // Se uma nova foto foi enviada
            $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $fotoAntiga = $stmt->fetchColumn();
            if ($fotoAntiga && file_exists($uploadDir . $fotoAntiga)) {
                unlink($uploadDir . $fotoAntiga); // Apaga o arquivo antigo
            }
        }

        $sql = "UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil, status = :status";
        $params = [':nome' => $nome, ':email' => $email, ':perfil' => $perfil, ':status' => $status, ':id' => $id];

        if ($senhaHash) {
            $sql .= ", senha = :senha";
            $params[':senha'] = $senhaHash;
        }
        if ($fotoNomeFinal) {
            $sql .= ", foto = :foto";
            $params[':foto'] = $fotoNomeFinal;
        }

        $sql .= " WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        responderJson(['sucesso' => true, 'tipo' => 'atualizacao']);

    } else { // Inserção
        if (empty($senhaHash)) {
            responderJson(['sucesso' => false, 'erro' => 'Senha é obrigatória para novos usuários.'], 400);
        }
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil, status, foto) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nome, $email, $senhaHash, $perfil, $status, $fotoNomeFinal]);
        
        responderJson(['sucesso' => true, 'id' => $conn->lastInsertId(), 'tipo' => 'insercao'], 201);
    }
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        responderJson(['sucesso' => false, 'erro' => 'ID é obrigatório para exclusão'], 400);
    }

    // Apagar foto antes de deletar o usuário do banco
    $uploadDir = __DIR__ . '/../uploads/';
    $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $fotoParaApagar = $stmt->fetchColumn();
    if ($fotoParaApagar && file_exists($uploadDir . $fotoParaApagar)) {
        unlink($uploadDir . $fotoParaApagar);
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        responderJson(['sucesso' => true]);
    } else {
        responderJson(['sucesso' => false, 'erro' => 'Usuário não encontrado ou já excluído.'], 404);
    }
}

// Se nenhum método corresponder
responderJson(['sucesso' => false, 'erro' => 'Método não permitido'], 405);
?>
