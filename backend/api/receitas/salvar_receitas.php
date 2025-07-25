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

$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (
            !isset($input['usuario_id'], $input['quem_recebeu'], $input['categoria_id'],
                   $input['forma_recebimento'], $input['valor'], $input['data_recebimento'])
        ) {
            http_response_code(400);
            echo json_encode(['erro' => 'Campos obrigatórios ausentes']);
            exit;
        }

        $id = isset($input['id']) ? intval($input['id']) : null;
        $usuario_id = intval($input['usuario_id']);
        $quem_recebeu = intval($input['quem_recebeu']);
        $categoria_id = intval($input['categoria_id']);
        $forma_recebimento = intval($input['forma_recebimento']);
        $valor = floatval($input['valor']);
        $data_recebimento = $input['data_recebimento'];
        $recorrente = isset($input['recorrente']) ? intval($input['recorrente']) : 0;
        $observacoes = $input['observacoes'] ?? '';

        if ($id) {
            // Atualiza receita existente
            $stmt = $pdo->prepare("UPDATE receitas SET
                quem_recebeu = :quem_recebeu,
                categoria_id = :categoria_id,
                forma_recebimento = :forma_recebimento,
                valor = :valor,
                data_recebimento = :data_recebimento,
                recorrente = :recorrente,
                observacoes = :observacoes
                WHERE id = :id AND usuario_id = :usuario_id");

            $stmt->execute([
                ':quem_recebeu' => $quem_recebeu,
                ':categoria_id' => $categoria_id,
                ':forma_recebimento' => $forma_recebimento,
                ':valor' => $valor,
                ':data_recebimento' => $data_recebimento,
                ':recorrente' => $recorrente,
                ':observacoes' => $observacoes,
                ':id' => $id,
                ':usuario_id' => $usuario_id
            ]);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Receita atualizada com sucesso']);
        } else {
            // Insere receita nova
            $stmt = $pdo->prepare("INSERT INTO receitas
                (usuario_id, quem_recebeu, categoria_id, forma_recebimento, valor, data_recebimento, recorrente, observacoes)
                VALUES (:usuario_id, :quem_recebeu, :categoria_id, :forma_recebimento, :valor, :data_recebimento, :recorrente, :observacoes)");

            $stmt->execute([
                ':usuario_id' => $usuario_id,
                ':quem_recebeu' => $quem_recebeu,
                ':categoria_id' => $categoria_id,
                ':forma_recebimento' => $forma_recebimento,
                ':valor' => $valor,
                ':data_recebimento' => $data_recebimento,
                ':recorrente' => $recorrente,
                ':observacoes' => $observacoes
            ]);
            echo json_encode(['sucesso' => true, 'mensagem' => 'Receita cadastrada com sucesso']);
        }
        exit;
    }

    // DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['erro' => 'ID não fornecido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Receita excluída com sucesso']);
        } else {
            http_response_code(404);
            echo json_encode(['erro' => 'Receita não encontrada']);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao salvar receita: ' . $e->getMessage()]);
}
