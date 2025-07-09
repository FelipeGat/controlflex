<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$conn = new mysqli('localhost', 'root', '', 'controleflex');
if ($conn->connect_error) {
    echo json_encode(['erro' => 'Erro ao conectar ao banco']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$quemComprou = $conn->real_escape_string($data['quemComprou']);
$ondeComprou = $conn->real_escape_string($data['ondeComprou']);
$categoria = (int) $data['categoria'];
$formaPagamento = $conn->real_escape_string($data['formaPagamento']);
$dataCompra = $conn->real_escape_string($data['dataCompra']);
$valor = (float) $data['valor'];
$recorrencia = $conn->real_escape_string($data['recorrencia']);
$observacoes = $conn->real_escape_string($data['observacoes']);

$sql = "INSERT INTO despesas (quem_comprou, onde_comprou, categoria_id, forma_pagamento, data_compra, valor, recorrencia, observacoes)
        VALUES ('$quemComprou', '$ondeComprou', $categoria, '$formaPagamento', '$dataCompra', $valor, '$recorrencia', '$observacoes')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['erro' => 'Erro ao salvar despesa']);
}

$conn->close();
?>
