<?php

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$id = isset($data['id']) ? (int)$data['id'] : null;
$quem_comprou = $data['quem_comprou'];
$onde_comprou = $data['onde_comprou'];
$categoria_id = (int)$data['categoria_id'];
$forma_pagamento = $data['forma_pagamento'];
$data_compra = $data['data_compra'];
$valor = (float)$data['valor'];
$recorrente = isset($data['recorrente']) ? (int)$data['recorrente'] : 0;
$recorrente_infinita = isset($data['recorrente_infinita']) ? (int)$data['recorrente_infinita'] : 0;
$parcelas = isset($data['parcelas']) ? (int)$data['parcelas'] : 0;
$observacoes = $data['observacoes'] ?? '';

function adicionarMes($data, $quantidade) {
    $date = new DateTime($data);
    $date->modify("+{$quantidade} month");
    return $date->format('Y-m-d');
}

try {
    if ($id) {
        // Atualizar despesa existente
        $stmt = $pdo->prepare("UPDATE despesas SET
            quem_comprou = :quem_comprou,
            onde_comprou = :onde_comprou,
            categoria_id = :categoria_id,
            forma_pagamento = :forma_pagamento,
            data_compra = :data_compra,
            valor = :valor,
            recorrente = :recorrente,
            observacoes = :observacoes
            WHERE id = :id
        ");

        $stmt->execute([
            ':quem_comprou' => $quem_comprou,
            ':onde_comprou' => $onde_comprou,
            ':categoria_id' => $categoria_id,
            ':forma_pagamento' => $forma_pagamento,
            ':data_compra' => $data_compra,
            ':valor' => $valor,
            ':recorrente' => $recorrente,
            ':observacoes' => $observacoes,
            ':id' => $id
        ]);

        echo json_encode(['sucesso' => true, 'mensagem' => 'Despesa atualizada com sucesso']);
    } else {
        if ($recorrente && !$recorrente_infinita && $parcelas > 1) {
            // Inserir parcelas
            $stmt = $pdo->prepare("INSERT INTO despesas 
                (quem_comprou, onde_comprou, categoria_id, forma_pagamento, data_compra, valor, recorrente, observacoes)
                VALUES (:quem_comprou, :onde_comprou, :categoria_id, :forma_pagamento, :data_compra, :valor, 1, :observacoes)");

            for ($i = 0; $i < $parcelas; $i++) {
                $nova_data = adicionarMes($data_compra, $i);
                $stmt->execute([
                    ':quem_comprou' => $quem_comprou,
                    ':onde_comprou' => $onde_comprou,
                    ':categoria_id' => $categoria_id,
                    ':forma_pagamento' => $forma_pagamento,
                    ':data_compra' => $nova_data,
                    ':valor' => $valor,
                    ':observacoes' => $observacoes
                ]);
            }
        } else {
            // Lançamento único ou recorrente infinito
            $stmt = $pdo->prepare("INSERT INTO despesas 
                (quem_comprou, onde_comprou, categoria_id, forma_pagamento, data_compra, valor, recorrente, observacoes)
                VALUES (:quem_comprou, :onde_comprou, :categoria_id, :forma_pagamento, :data_compra, :valor, :recorrente, :observacoes)");

            $stmt->execute([
                ':quem_comprou' => $quem_comprou,
                ':onde_comprou' => $onde_comprou,
                ':categoria_id' => $categoria_id,
                ':forma_pagamento' => $forma_pagamento,
                ':data_compra' => $data_compra,
                ':valor' => $valor,
                ':recorrente' => $recorrente_infinita,
                ':observacoes' => $observacoes
            ]);
        }

        echo json_encode(['sucesso' => true, 'mensagem' => 'Despesa cadastrada com sucesso']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar despesa: ' . $e->getMessage()]);
}
