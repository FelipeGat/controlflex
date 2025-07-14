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

$quem_comprou = $conn->real_escape_string($data['quem_comprou']);
$onde_comprou = $conn->real_escape_string($data['onde_comprou']);
$categoria_id = (int) $data['categoria_id'];
$forma_pagamento = $conn->real_escape_string($data['forma_pagamento']);
$data_compra = $conn->real_escape_string($data['data_compra']);
$valor = (float) $data['valor'];
$recorrente = isset($data['recorrente']) ? (int) $data['recorrente'] : 0;
$recorrente_infinita = isset($data['recorrente_infinita']) ? (int) $data['recorrente_infinita'] : 0;
$parcelas = isset($data['parcelas']) ? (int) $data['parcelas'] : 0;
$observacoes = $conn->real_escape_string($data['observacoes']);

function adicionarMes($data, $quantidade) {
    $date = new DateTime($data);
    $date->modify("+{$quantidade} month");
    return $date->format('Y-m-d');
}

$sucesso = true;

if ($recorrente && !$recorrente_infinita && $parcelas > 1) {
    // Insere parcelas
    for ($i = 0; $i < $parcelas; $i++) {
        $nova_data = adicionarMes($data_compra, $i);
        $sql = "INSERT INTO despesas 
            (quem_comprou, onde_comprou, categoria_id, forma_pagamento, data_compra, valor, recorrente, observacoes)
            VALUES ('$quem_comprou', '$onde_comprou', $categoria_id, '$forma_pagamento', '$nova_data', $valor, 1, '$observacoes')";

        if (!$conn->query($sql)) {
            $sucesso = false;
            break;
        }
    }
} else {
    // Lançamento único ou recorrente infinito (registramos 1x só com flag)
    $sql = "INSERT INTO despesas 
        (quem_comprou, onde_comprou, categoria_id, forma_pagamento, data_compra, valor, recorrente, observacoes)
        VALUES ('$quem_comprou', '$onde_comprou', $categoria_id, '$forma_pagamento', '$data_compra', $valor, $recorrente_infinita, '$observacoes')";

    if (!$conn->query($sql)) {
        $sucesso = false;
    }
}

echo json_encode(['sucesso' => $sucesso]);

$conn->close();
?>
