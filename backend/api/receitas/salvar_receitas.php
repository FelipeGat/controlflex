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

function converterData($data) {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) return $data;
    $partes = explode('/', $data);
    return count($partes) === 3 ? "{$partes[2]}-{$partes[1]}-{$partes[0]}" : null;
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
        http_response_code(405 );
        echo json_encode(['erro' => 'Método não permitido']);
        exit;
    }

    $camposObrigatorios = ['usuario_id', 'quem_recebeu', 'categoria_id', 'forma_recebimento', 'valor', 'data_recebimento'];
    foreach ($camposObrigatorios as $campo) {
        if (empty($input[$campo]) && $input[$campo] !== 0 && $input[$campo] !== '0') {
            http_response_code(400 );
            echo json_encode(['erro' => "O campo '{$campo}' é obrigatório."]);
            exit;
        }
    }

    $id = $input['id'] ?? null;
    $usuario_id = intval($input['usuario_id']);
    $quem_recebeu = intval($input['quem_recebeu']);
    $categoria_id = intval($input['categoria_id']);
    $forma_recebimento = intval($input['forma_recebimento']);
    $valor = floatval($input['valor']);
    $data_recebimento = converterData($input['data_recebimento']);
    $observacoes = $input['observacoes'] ?? '';
    $recorrente_do_input = isset($input['recorrente']) ? intval($input['recorrente']) : 0;
    $parcelas_do_input = isset($input['parcelas']) ? intval($input['parcelas']) : 1;

    if (!$data_recebimento) {
        http_response_code(400 );
        echo json_encode(['erro' => 'Data de recebimento inválida.']);
        exit;
    }

    $pdo->beginTransaction();

    if ($id) {
        // LÓGICA DE ATUALIZAÇÃO (UPDATE)
        $stmt = $pdo->prepare("UPDATE receitas SET quem_recebeu = :quem_recebeu, categoria_id = :categoria_id, forma_recebimento = :forma_recebimento, valor = :valor, data_recebimento = :data_recebimento, recorrente = :recorrente, parcelas = :parcelas, observacoes = :observacoes WHERE id = :id AND usuario_id = :usuario_id");
        $stmt->execute([':quem_recebeu' => $quem_recebeu, ':categoria_id' => $categoria_id, ':forma_recebimento' => $forma_recebimento, ':valor' => $valor, ':data_recebimento' => $data_recebimento, ':recorrente' => $recorrente_do_input, ':parcelas' => $parcelas_do_input, ':observacoes' => $observacoes, ':id' => $id, ':usuario_id' => $usuario_id]);
        $mensagem = 'Receita atualizada com sucesso!';
    } else {
        // LÓGICA DE CRIAÇÃO (INSERT)
        $stmt = $pdo->prepare("INSERT INTO receitas (usuario_id, quem_recebeu, categoria_id, forma_recebimento, valor, data_recebimento, recorrente, parcelas, observacoes, grupo_recorrencia_id) VALUES (:usuario_id, :quem_recebeu, :categoria_id, :forma_recebimento, :valor, :data_recebimento, :recorrente, :parcelas, :observacoes, :grupo_id)");

        $numero_de_lancamentos = 1;
        $grupo_id = null;

        if ($recorrente_do_input === 1) {
            $grupo_id = uniqid('rec_', true);
            if ($parcelas_do_input === 0) {
                $numero_de_lancamentos = 60;
            } elseif ($parcelas_do_input >= 1) {
                $numero_de_lancamentos = $parcelas_do_input;
            }
        }

        $data_base = new DateTime($data_recebimento);

        for ($i = 0; $i < $numero_de_lancamentos; $i++) {
            $data_parcela_atual = clone $data_base;
            $data_parcela_atual->modify("+$i month");
            $obs_parcela = ($numero_de_lancamentos > 1) ? trim($observacoes . " (Parcela " . ($i + 1) . " de $numero_de_lancamentos)") : $observacoes;

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':quem_recebeu' => $quem_recebeu,
                ':categoria_id' => $categoria_id,
                ':forma_recebimento' => $forma_recebimento,
                ':valor' => $valor,
                ':data_recebimento' => $data_parcela_atual->format('Y-m-d'),
                ':recorrente' => $recorrente_do_input,
                ':parcelas' => $numero_de_lancamentos,
                ':observacoes' => $obs_parcela,
                ':grupo_id' => $grupo_id
            ]);
        }
        $mensagem = 'Receita(s) cadastrada(s) com sucesso!';
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500 );
    echo json_encode(['sucesso' => false, 'erro' => 'Erro no servidor: ' . $e->getMessage(), 'linha' => $e->getLine()]);
}
?>
