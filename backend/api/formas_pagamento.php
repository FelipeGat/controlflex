<?php
// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../conexao.php';

$usuario_id = $_GET['usuario_id'];

$sql = "SELECT f.id as familiar_id, f.nome as familiar_nome, b.id as banco_id, b.nome as banco_nome, 
               fb.limiteCartao, fb.limiteCheque
        FROM familiares f
        JOIN familiares_bancos fb ON fb.familiar_id = f.id
        JOIN bancos b ON b.id = fb.banco_id
        WHERE f.usuario_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$formas = [];

while ($row = $result->fetch_assoc()) {
    if ($row['limiteCartao'] > 0) {
        $formas[] = [
            'label' => "{$row['familiar_nome']} – Cartão – {$row['banco_nome']}",
            'value' => "cartao-{$row['familiar_id']}-{$row['banco_id']}"
        ];
    }
    $formas[] = [
        'label' => "{$row['familiar_nome']} – Conta – {$row['banco_nome']}",
        'value' => "conta-{$row['familiar_id']}-{$row['banco_id']}"
    ];
}

echo json_encode($formas);
