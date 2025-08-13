<?php
// CORS e Headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $id = $input['id'] ?? null;
    $escopo = $input['escopo_exclusao'] ?? 'apenas_esta';
    $data_compra_inicio = $input['data_compra'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'ID da despesa não fornecido.']);
        exit;
    }

    $pdo->beginTransaction();

    if ($escopo === 'esta_e_futuras') {
        
        $stmt_info = $pdo->prepare("SELECT grupo_recorrencia_id FROM despesas WHERE id = :id");
        $stmt_info->execute([':id' => $id]);
        $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        $grupo_id = $info['grupo_recorrencia_id'] ?? null;

        if ($grupo_id && $data_compra_inicio) {
            
            $data_inicio_datetime = $data_compra_inicio . ' 00:00:00';

            $sql = "DELETE FROM despesas 
                    WHERE grupo_recorrencia_id = :grupo_id 
                      AND data_compra >= :data_inicio";

            $stmt_delete = $pdo->prepare($sql);
            $stmt_delete->execute([
                ':grupo_id' => $grupo_id,
                ':data_inicio' => $data_inicio_datetime
            ]);

            $linhas_afetadas = $stmt_delete->rowCount();
            $mensagem = "$linhas_afetadas parcela(s) recorrente(s) foram excluídas com sucesso.";
        } else {
            
            $stmt_delete = $pdo->prepare("DELETE FROM despesas WHERE id = :id");
            $stmt_delete->execute([':id' => $id]);
            $mensagem = "Apenas a despesa selecionada foi excluída (grupo não encontrado).";
        }
    } else {
        
        $stmt_delete = $pdo->prepare("DELETE FROM despesas WHERE id = :id");
        $stmt_delete->execute([':id' => $id]);
        $mensagem = "A despesa selecionada foi excluída com sucesso.";
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => $mensagem]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro no servidor: ' . $e->getMessage(),
        'linha' => $e->getLine()
    ]);
}
?>
