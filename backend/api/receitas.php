<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

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
    $dbUser = 'control';
    $dbPass = '100%Control!!';
}

// Criar conexão PDO
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro na conexão com o banco de dados"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["erro" => "Método não suportado"]);
    exit;
}

$usuarioId = $_GET['usuario_id'] ?? null;
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;

if (!$usuarioId || !$inicio || !$fim) {
    echo json_encode(["erro" => "Parâmetros insuficientes"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nome AS categoria_nome
        FROM receitas r
        LEFT JOIN categorias c ON r.categoria_id = c.id
        WHERE r.usuario_id = :usuario_id
        AND DATE(r.data_recebimento) BETWEEN :inicio AND :fim
        ORDER BY r.data_recebimento DESC
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':inicio' => $inicio,
        ':fim' => $fim
    ]);

    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($receitas);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao buscar receitas: " . $e->getMessage()]);
}
