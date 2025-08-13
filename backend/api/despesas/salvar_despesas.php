<?php
// CORS: pré-flight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200 );
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405 );
        echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
        exit;
    }

    $camposObrigatorios = ['usuario_id', 'quem_comprou', 'onde_comprou', 'categoria_id', 'forma_pagamento', 'valor', 'data_compra'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo]) || ($input[$campo] === '' && $input[$campo] !== 0)) {
            http_response_code(400 );
            echo json_encode(['sucesso' => false, 'erro' => "O campo '{$campo}' é obrigatório."]);
            exit;
        }
    }

    $id = $input['id'] ?? null;
    $usuario_id = intval($input['usuario_id']);
    $quem_comprou = intval($input['quem_comprou']);
    $onde_comprou = intval($input['onde_comprou']);
    $categoria_id = intval($input['categoria_id']);
    $forma_pagamento = $input['forma_pagamento'];
    $valor = floatval($input['valor']);
    $data_compra = $input['data_compra'];
    $observacoes = $input['observacoes'] ?? '';
    $recorrente = isset($input['recorrente']) ? intval($input['recorrente']) : 0;
    $parcelas_input = isset($input['parcelas']) ? intval($input['parcelas']) : 1;

    $pdo->beginTransaction();

    if ($id) {
        // LÓGICA DE ATUALIZAÇÃO (UPDATE)
        $stmt = $pdo->prepare("
            UPDATE despesas SET
                quem_comprou = :quem_comprou, onde_comprou = :onde_comprou, categoria_id = :categoria_id,
                forma_pagamento = :forma_pagamento, valor = :valor, data_compra = :data_compra,
                recorrente = :recorrente, parcelas = :parcelas, observacoes = :observacoes
            WHERE id = :id AND usuario_id = :usuario_id
        ");
        $stmt->execute([
            ':quem_comprou' => $quem_comprou,
            ':onde_comprou' => $onde_comprou,
            ':categoria_id' => $categoria_id,
            ':forma_pagamento' => $forma_pagamento,
            ':valor' => $valor,
            ':data_compra' => $data_compra,
            ':recorrente' => $recorrente,
            ':parcelas' => $parcelas_input,
            ':observacoes' => $observacoes,
            ':id' => $id,
            ':usuario_id' => $usuario_id
        ]);
        $mensagem = 'Despesa atualizada com sucesso!';

    } else {
        // LÓGICA DE CRIAÇÃO (INSERT)
        $stmt = $pdo->prepare("
            INSERT INTO despesas
            (usuario_id, quem_comprou, onde_comprou, categoria_id, forma_pagamento, valor, data_compra, recorrente, parcelas, observacoes, grupo_recorrencia_id)
            VALUES (:usuario_id, :quem_comprou, :onde_comprou, :categoria_id, :forma_pagamento, :valor, :data_compra, :recorrente, :parcelas, :observacoes, :grupo_id)
        ");

        $numero_de_lancamentos = 1;
        $grupo_id = null;

        if ($recorrente === 1 && $parcelas_input != 1) {
            $grupo_id = uniqid('desp_rec_', true);
            if ($parcelas_input === 0) {
                $numero_de_lancamentos = 60;
            } else {
                $numero_de_lancamentos = $parcelas_input;
            }
        }

        $data_base = new DateTime($data_compra);

        for ($i = 0; $i < $numero_de_lancamentos; $i++) {
            // A CADA ITERAÇÃO, CRIA UM NOVO OBJETO DE DATA A PARTIR DO ORIGINAL
            // E SÓ ENTÃO MODIFICA. ESTA É A CORREÇÃO.
            $data_parcela_atual = new DateTime($data_compra);
            $data_parcela_atual->modify("+$i month");

            $obs_parcela = $observacoes;
            if ($numero_de_lancamentos > 1) {
                $obs_parcela = trim($observacoes . " (Parcela " . ($i + 1) . " de $numero_de_lancamentos)");
            }

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':quem_comprou' => $quem_comprou,
                ':onde_comprou' => $onde_comprou,
                ':categoria_id' => $categoria_id,
                ':forma_pagamento' => $forma_pagamento,
                ':valor' => $valor,
                ':data_compra' => $data_parcela_atual->format('Y-m-d'),
                ':recorrente' => $recorrente,
                ':parcelas' => $numero_de_lancamentos,
                ':observacoes' => $obs_parcela,
                ':grupo_id' => $grupo_id
            ]);
        }
        $mensagem = 'Despesa(s) cadastrada(s) com sucesso!';
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500 );
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro no servidor: ' . $e->getMessage(),
        'linha' => $e->getLine()
    ]);
}
?>
