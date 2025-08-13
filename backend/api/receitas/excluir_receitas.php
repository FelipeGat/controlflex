<?php
// CORS: pré-flight (requisições OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200 );
    exit;
}

// Headers principais para a resposta
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405 );
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
    exit;
}

// Conexão com o banco de dados
require_once __DIR__ . '/../../config/db.php';

// Lê o corpo da requisição JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validação dos dados de entrada
if (!isset($input['id']) || !isset($input['escopo_exclusao'])) {
    http_response_code(400 );
    echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos. ID e escopo de exclusão são obrigatórios.']);
    exit;
}

$id = intval($input['id']);
$escopo = $input['escopo_exclusao'];

try {
    $pdo->beginTransaction();

    // 1. Buscar informações da receita a ser excluída
    $stmt_info = $pdo->prepare("SELECT grupo_recorrencia_id, data_recebimento FROM receitas WHERE id = :id");
    $stmt_info->execute([':id' => $id]);
    $receita = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$receita) {
        $pdo->rollBack();
        http_response_code(404 );
        echo json_encode(['sucesso' => false, 'erro' => 'Receita com ID ' . $id . ' não encontrada.']);
        exit;
    }

    $grupo_id = $receita['grupo_recorrencia_id'];
    $linhas_afetadas = 0;
    $mensagem = '';

    // 2. Decide a lógica de exclusão
    // Se o escopo for "esta_e_futuras" E a receita pertencer a um grupo...
    if ($escopo === 'esta_e_futuras' && $grupo_id) {
        // CASO 1: Excluir esta e as futuras parcelas do mesmo grupo
        $data_inicio_exclusao = $input['data_recebimento'] ?? $receita['data_recebimento'];

        $stmt = $pdo->prepare("
            DELETE FROM receitas 
            WHERE grupo_recorrencia_id = :grupo_id 
            AND data_recebimento >= :data_inicio
        ");
        $stmt->execute([
            ':grupo_id' => $grupo_id,
            ':data_inicio' => $data_inicio_exclusao
        ]);
        $linhas_afetadas = $stmt->rowCount();
        $mensagem = "{$linhas_afetadas} parcela(s) recorrente(s) foram excluídas com sucesso.";

    } else {
        // CASO 2: Excluir apenas a parcela específica (padrão para todos os outros casos)
        $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $linhas_afetadas = $stmt->rowCount();
        $mensagem = "A receita selecionada foi excluída com sucesso.";
    }

    // 3. Finaliza a transação e envia a resposta
    if ($linhas_afetadas > 0) {
        $pdo->commit();
        echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);
    } else {
        // Se nenhuma linha foi afetada, pode ser um erro ou a ação não encontrou alvos.
        // É melhor reverter para evitar inconsistências.
        $pdo->rollBack();
        http_response_code(404 );
        echo json_encode(['sucesso' => false, 'erro' => 'Nenhuma receita foi encontrada para exclusão com os critérios fornecidos. A operação foi revertida.']);
    }

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
