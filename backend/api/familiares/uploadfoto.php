<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$uploadDir = __DIR__ . '/../../uploads/';
$uploadUrl = 'http://localhost/ControleFlex/backend/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['foto'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhum arquivo enviado.']);
    exit;
}

$file = $_FILES['foto'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$nomeArquivo = uniqid('foto_', true) . '.' . $ext;
$caminhoFinal = $uploadDir . $nomeArquivo;

if (move_uploaded_file($file['tmp_name'], $caminhoFinal)) {
    echo json_encode(['url' => $uploadUrl . $nomeArquivo]);
} else {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao mover o arquivo.']);
}
