<?php
// /api/bancos.php

// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
$frontend_url = "http://localhost:3000"; 
header("Access-Control-Allow-Origin: " . $frontend_url );
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200 );
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// 2. INICIALIZAÇÃO E DEPENDÊNCIAS (USANDO O SEU db.php)
// ===================================================
// Garante que estamos usando a mesma conexão PDO segura do resto do projeto.
require_once __DIR__ . '/../config/db.php'; 

// 3. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (!isset($_GET['usuario_id'])) {
                http_response_code(400 );
                echo json_encode(['erro' => 'O parâmetro usuario_id é obrigatório.']);
                exit;
            }
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);

            $stmt = $pdo->prepare("SELECT * FROM bancos WHERE usuario_id = :uid ORDER BY nome ASC");
            $stmt->execute(['uid' => $usuario_id]);
            $bancos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($bancos);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400 );
                echo json_encode(['erro' => 'JSON inválido.']);
                exit;
            }

            // Validação básica dos campos obrigatórios
            $required_fields = ['usuario_id', 'nome', 'codigo_banco', 'agencia'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    http_response_code(400 );
                    echo json_encode(['erro' => "O campo '$field' é obrigatório."]);
                    exit;
                }
            }

            $id = $data['id'] ?? null;

            // Se um ID foi fornecido, é uma ATUALIZAÇÃO
            if ($id) {
                $sql = "UPDATE bancos SET 
                            nome = :nome, 
                            codigo_banco = :codigo_banco, 
                            agencia = :agencia, 
                            conta = :conta, 
                            saldo = :saldo, 
                            limite_cartao = :limite_cartao, 
                            cheque_especial = :cheque_especial
                        WHERE id = :id AND usuario_id = :usuario_id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':codigo_banco' => $data['codigo_banco'],
                    ':agencia' => $data['agencia'],
                    ':conta' => $data['conta'] ?? null,
                    ':saldo' => $data['saldo'] ?? 0.00,
                    ':limite_cartao' => $data['limite_cartao'] ?? 0.00,
                    ':cheque_especial' => $data['cheque_especial'] ?? 0.00,
                    ':id' => $id,
                    ':usuario_id' => $data['usuario_id']
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Banco atualizado.']);

            } else { // Senão, é uma CRIAÇÃO
                $sql = "INSERT INTO bancos (usuario_id, nome, codigo_banco, agencia, conta, saldo, limite_cartao, cheque_especial) 
                        VALUES (:usuario_id, :nome, :codigo_banco, :agencia, :conta, :saldo, :limite_cartao, :cheque_especial)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':usuario_id' => $data['usuario_id'],
                    ':nome' => $data['nome'],
                    ':codigo_banco' => $data['codigo_banco'],
                    ':agencia' => $data['agencia'],
                    ':conta' => $data['conta'] ?? null,
                    ':saldo' => $data['saldo'] ?? 0.00,
                    ':limite_cartao' => $data['limite_cartao'] ?? 0.00,
                    ':cheque_especial' => $data['cheque_especial'] ?? 0.00
                ]);
                http_response_code(201 );
                echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;

        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT); // Segurança extra

            if (!$id || !$usuario_id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID do banco e do usuário são obrigatórios.']);
                exit;
            }
            
            // Adicionado 'usuario_id' na query DELETE para garantir que um usuário
            // não possa excluir o banco de outro.
            $stmt = $pdo->prepare("DELETE FROM bancos WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([':id' => $id, ':usuario_id' => $usuario_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Banco excluído.']);
            } else {
                http_response_code(404 );
                echo json_encode(['erro' => 'Banco não encontrado ou não pertence a este usuário.']);
            }
            break;

        default:
            http_response_code(405 );
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    error_log("Erro na API de bancos: " . $e->getMessage());
    http_response_code(500 );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
