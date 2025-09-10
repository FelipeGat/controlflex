<?php
// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
$frontend_url = "http://localhost:3000";
header("Access-Control-Allow-Origin: " . $frontend_url);
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// 2. INICIALIZAÇÃO E DEPENDÊNCIAS
// ===================================================
require_once __DIR__ . '/../config/db.php';

// 3. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (!isset($_GET['usuario_id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'O parâmetro usuario_id é obrigatório.']);
                exit;
            }
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);

            // Adicionando um JOIN para buscar o nome do titular da tabela `familiares`
            $sql = "SELECT b.*, f.nome as nome_titular
                    FROM bancos b
                    LEFT JOIN familiares f ON b.titular_id = f.id
                    WHERE b.usuario_id = :uid
                    ORDER BY b.tipo_conta ASC, b.nome ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['uid' => $usuario_id]);
            $bancos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['data' => $bancos]);
            break;

        case 'POST':
             // Ajustar Saldo do Banco
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $bancoId = intval($data['banco_id'] ?? 0);
    $usuarioId = intval($data['usuario_id'] ?? 0);

    if ($_GET['action'] === 'ajustarSaldo') {
        $novoSaldo = floatval($data['saldo'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE bancos SET saldo = :saldo WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([
                ':saldo' => $novoSaldo,
                ':id' => $bancoId,
                ':usuario_id' => $usuarioId
            ]);
            echo json_encode(['success' => true, 'message' => 'Saldo ajustado com sucesso']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao ajustar saldo', 'error' => $e->getMessage()]);
        }
        exit;
    }

    if ($_GET['action'] === 'ajustarSaldoCartao') {
        $novoSaldoCartao = floatval($data['saldo_cartao'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE bancos SET saldo_cartao = :saldo_cartao WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([
                ':saldo_cartao' => $novoSaldoCartao,
                ':id' => $bancoId,
                ':usuario_id' => $usuarioId
            ]);
            echo json_encode(['success' => true, 'message' => 'Saldo do cartão ajustado com sucesso']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao ajustar saldo do cartão', 'error' => $e->getMessage()]);
        }
        exit;
    }
}
            $data = json_decode(file_get_contents("php://input"), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['erro' => 'JSON inválido.']);
                exit;
            }

            // Validação de campos obrigatórios
            if (empty($data['usuario_id']) || empty($data['nome']) || empty($data['titular_id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'Os campos "usuario_id", "nome" e "titular_id" são obrigatórios.']);
                exit;
            }
            
            $id = $data['id'] ?? null;
            $usuario_id = filter_var($data['usuario_id'], FILTER_VALIDATE_INT);
            $titular_id = filter_var($data['titular_id'], FILTER_VALIDATE_INT);


            // VERIFICAÇÃO ADICIONAL PARA EVITAR DUPLICAÇÃO DA CONTA "CARTEIRA"
            if ($data['nome'] === 'Carteira' && $id === null) {
                $stmt = $pdo->prepare("SELECT id FROM bancos WHERE usuario_id = :usuario_id AND tipo_conta = 'Dinheiro' AND nome = 'Carteira'");
                $stmt->execute([':usuario_id' => $usuario_id]);
                if ($stmt->fetch()) {
                    http_response_code(409); // Conflict
                    echo json_encode(['erro' => 'A conta "Carteira" já existe para este usuário.']);
                    exit;
                }
            }
            
            // Preparação e sanitização dos dados
            $nome = htmlspecialchars($data['nome']);
            $codigo_banco = $data['codigo_banco'] ? htmlspecialchars($data['codigo_banco']) : null;
            $agencia = $data['agencia'] ? htmlspecialchars($data['agencia']) : null;
            $conta = $data['conta'] ? htmlspecialchars($data['conta']) : null;
            $conta_poupanca = $data['conta_poupanca'] ? htmlspecialchars($data['conta_poupanca']) : null;
            
            // Tratamento de valores numéricos
            $saldo = isset($data['saldo']) ? (float) $data['saldo'] : 0.00;
            $limite_cartao = isset($data['limite_cartao']) ? (float) $data['limite_cartao'] : 0.00;
            $saldo_cartao = isset($data['saldo_cartao']) ? (float) $data['saldo_cartao'] : 0.00;
            $cheque_especial = isset($data['cheque_especial']) ? (float) $data['cheque_especial'] : 0.00;
            $saldo_cheque = isset($data['saldo_cheque']) ? (float) $data['saldo_cheque'] : 0.00;

            // ** NOVA LÓGICA SIMPLIFICADA **
            // O tipo de conta agora é sempre "Conta Corrente", exceto a Carteira.
            if ($data['nome'] === 'Carteira') {
                $tipo_conta = 'Dinheiro';
            } else {
                $tipo_conta = 'Conta Corrente';
            }
            // ** FIM DA NOVA LÓGICA **

            if ($id) { // ATUALIZAÇÃO (PUT)
                $sql = "UPDATE bancos SET
                    nome = :nome,
                    codigo_banco = :codigo_banco,
                    agencia = :agencia,
                    conta = :conta,
                    conta_poupanca = :conta_poupanca,
                    saldo = :saldo,
                    limite_cartao = :limite_cartao,
                    saldo_cartao = :saldo_cartao,
                    cheque_especial = :cheque_especial,
                    saldo_cheque = :saldo_cheque,
                    tipo_conta = :tipo_conta,
                    titular_id = :titular_id
                WHERE id = :id AND usuario_id = :usuario_id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':codigo_banco' => $codigo_banco,
                    ':agencia' => $agencia,
                    ':conta' => $conta,
                    ':conta_poupanca' => $conta_poupanca,
                    ':saldo' => $saldo,
                    ':limite_cartao' => $limite_cartao,
                    ':saldo_cartao' => $saldo_cartao,
                    ':cheque_especial' => $cheque_especial,
                    ':saldo_cheque' => $saldo_cheque,
                    ':tipo_conta' => $tipo_conta,
                    ':titular_id' => $titular_id,
                    ':id' => $id,
                    ':usuario_id' => $usuario_id
                ]);

                if ($stmt->rowCount() > 0) {
                    $stmt = $pdo->prepare("SELECT * FROM bancos WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $updated_bank = $stmt->fetch(PDO::FETCH_ASSOC);
                    http_response_code(200);
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Banco atualizado.', 'banco' => $updated_bank]);
                } else {
                    http_response_code(404);
                    echo json_encode(['erro' => 'Banco não encontrado ou nenhum dado foi alterado.']);
                }

            } else { // CRIAÇÃO (POST)
                $sql = "INSERT INTO bancos (usuario_id, titular_id, nome, codigo_banco, agencia, conta, conta_poupanca, saldo, limite_cartao, saldo_cartao, cheque_especial, saldo_cheque, tipo_conta)
                    VALUES (:usuario_id, :titular_id, :nome, :codigo_banco, :agencia, :conta, :conta_poupanca, :saldo, :limite_cartao, :saldo_cartao, :cheque_especial, :saldo_cheque, :tipo_conta)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':usuario_id' => $usuario_id,
                    ':titular_id' => $titular_id,
                    ':nome' => $nome,
                    ':codigo_banco' => $codigo_banco,
                    ':agencia' => $agencia,
                    ':conta' => $conta,
                    ':conta_poupanca' => $conta_poupanca,
                    ':saldo' => $saldo,
                    ':limite_cartao' => $limite_cartao,
                    ':saldo_cartao' => $saldo_cartao,
                    ':cheque_especial' => $cheque_especial,
                    ':saldo_cheque' => $saldo_cheque,
                    ':tipo_conta' => $tipo_conta
                ]);
                
                $lastId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT * FROM bancos WHERE id = :id");
                $stmt->execute([':id' => $lastId]);
                $new_bank = $stmt->fetch(PDO::FETCH_ASSOC);

                http_response_code(201);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Banco criado.', 'banco' => $new_bank]);
            }
            break;

        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);

            if (!$id || !$usuario_id) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do banco e do usuário são obrigatórios.']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM bancos WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->execute([':id' => $id, ':usuario_id' => $usuario_id]);

            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Banco excluído.']);
            } else {
                http_response_code(404);
                echo json_encode(['erro' => 'Banco não encontrado ou não pertence a este usuário.']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    error_log("Erro na API de bancos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>