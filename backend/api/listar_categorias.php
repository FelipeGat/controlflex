<?php
// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Detectar ambiente local ou produção
$isLocal = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['HTTP_HOST'] === 'localhost';

if ($isLocal) {
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'controleflex';
} else {
    $dbHost = 'localhost';
    $dbUser = 'control';
    $dbPass = '100%Control!!';
    $dbName = 'inves783_controleflex';
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, nome FROM categorias ORDER BY nome ASC";
$result = $conn->query($sql);

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

echo json_encode($categorias);
$conn->close();
