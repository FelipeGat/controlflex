<?php
// /api/investimentos.php

// 1. CABEÇALHOS E DEPENDÊNCIAS
// ===================================================
header("Access-Control-Allow-Origin: http://localhost:3000" );
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200 );
    exit();
}

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../config/db.php';

// 2. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
            if (!$usuario_id) {
                throw new Exception('ID do usuário é obrigatório.', 400);
            }

            // Futuramente, podemos adicionar filtros por tipo, etc.
            $sql = "SELECT 
                        i.*, 
                        b.nome as banco_nome 
                    FROM investimentos i
                    LEFT JOIN bancos b ON i.banco_id = b.id
                    WHERE i.usuario_id = :uid 
                    ORDER BY i.data_aporte DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':uid' => $usuario_id]);
            $investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($investimentos);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            // Validação básica
            $requiredFields = ['usuario_id', 'nome_ativo', 'tipo_investimento', 'banco_id', 'data_aporte', 'valor_aportado'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("O campo '$field' é obrigatório.", 400);
                }
            }

            if ($id) { // ATUALIZAÇÃO
                $sql = "UPDATE investimentos SET nome_ativo = :na, tipo_investimento = :ti, banco_id = :bi, data_aporte = :da, valor_aportado = :va, quantidade_cotas = :qc, observacoes = :obs WHERE id = :id AND usuario_id = :uid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':na' => $data['nome_ativo'],
                    ':ti' => $data['tipo_investimento'],
                    ':bi' => $data['banco_id'],
                    ':da' => $data['data_aporte'],
                    ':va' => $data['valor_aportado'],
                    ':qc' => $data['quantidade_cotas'] ?? null,
                    ':obs' => $data['observacoes'] ?? '',
                    ':id' => $id,
                    ':uid' => $data['usuario_id']
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento atualizado com sucesso!']);

            } else { // CRIAÇÃO
                $sql = "INSERT INTO investimentos (usuario_id, nome_ativo, tipo_investimento, banco_id, data_aporte, valor_aportado, quantidade_cotas, observacoes) 
                        VALUES (:uid, :na, :ti, :bi, :da, :va, :qc, :obs)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':uid' => $data['usuario_id'],
                    ':na' => $data['nome_ativo'],
                    ':ti' => $data['tipo_investimento'],
                    ':bi' => $data['banco_id'],
                    ':da' => $data['data_aporte'],
                    ':va' => $data['valor_aportado'],
                    ':qc' => $data['quantidade_cotas'] ?? null,
                    ':obs' => $data['observacoes'] ?? ''
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento adicionado com sucesso!']);
            }
            break;

        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('ID do investimento é obrigatório.', 400);
            }
            
            $stmt = $pdo->prepare("DELETE FROM investimentos WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Investimento excluído com sucesso.']);
            } else {
                throw new Exception('Investimento não encontrado.', 404);
            }
            break;

        default:
            http_response_code(405 );
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    $httpCode = is_int($e->getCode( )) && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
