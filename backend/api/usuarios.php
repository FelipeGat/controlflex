<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Detectar se está rodando localmente ou em produção
$isLocalhost = $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocalhost) {
    // Ambiente local (XAMPP)
    $host = 'localhost';
    $db   = 'controleflex';
    $user = 'root';
    $pass = '';
} else {
    // Ambiente produção (HostGator)
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
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao conectar com o banco de dados: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    exit(0);
}

if ($method === 'GET') {
    $stmt = $conn->prepare("SELECT * FROM usuarios");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? null;
    $email = $_POST['email'] ?? null;
    $senha = $_POST['senha'] ?? null;
    $perfil = $_POST['nivel'] ?? 'Padrão';
    $status = $_POST['status'] ?? 'Ativo';

    $senhaHash = !empty($senha) ? password_hash($senha, PASSWORD_DEFAULT) : null;

    // Upload da foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], "../../uploads/" . $foto);
    }

    if ($id) {
        $sql = "UPDATE usuarios SET nome = ?, email = ?, perfil = ?, status = ?";
        $params = [$nome, $email, $perfil, $status];

        if (!empty($senhaHash)) {
            $sql .= ", senha = ?";
            $params[] = $senhaHash;
        }

        if ($foto) {
            $sql .= ", foto = ?";
            $params[] = $foto;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['sucesso' => true, 'tipo' => 'atualizacao']);
    } else {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, perfil, status, foto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senhaHash, $perfil, $status, $foto]);
        echo json_encode(['sucesso' => true, 'id' => $conn->lastInsertId(), 'tipo' => 'insercao']);
    }
    exit;
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents("php://input"), true);

    $id = $input['id'] ?? null;
    $nome = $input['nome'] ?? null;
    $email = $input['email'] ?? null;
    $senha = $input['senha'] ?? null;
    $perfil = $input['nivel'] ?? 'Padrão';
    $status = $input['status'] ?? 'Ativo';

    if (!$id) {
        echo json_encode(['erro' => 'ID é obrigatório para atualização']);
        exit;
    }

    $senhaHash = !empty($senha) ? password_hash($senha, PASSWORD_DEFAULT) : null;

    $sql = "UPDATE usuarios SET nome = ?, email = ?, perfil = ?, status = ?";
    $params = [$nome, $email, $perfil, $status];

    if (!empty($senhaHash)) {
        $sql .= ", senha = ?";
        $params[] = $senhaHash;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['sucesso' => true, 'tipo' => 'atualizacao']);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['erro' => 'ID é obrigatório para exclusão']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['sucesso' => true]);
    exit;
}
?>
