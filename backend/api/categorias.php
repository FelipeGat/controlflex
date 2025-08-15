<?php
// /api/categorias.php (Arquivo Unificado e Completo)

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

// 2. INICIALIZAÇÃO E DEPENDÊNCIAS
// ===================================================
require_once __DIR__ . '/../config/db.php'; 

// 3. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        // --- CASO GET: Listar Categorias ---
        case 'GET':
            $tipo = filter_input(INPUT_GET, 'tipo', FILTER_SANITIZE_STRING);
            
            if ($tipo && ($tipo === 'RECEITA' || $tipo === 'DESPESA')) {
                // Se um tipo válido for especificado, filtra por ele.
                $stmt = $pdo->prepare("SELECT id, nome, tipo, icone FROM categorias WHERE tipo = :tipo ORDER BY nome ASC");
                $stmt->execute([':tipo' => $tipo]);
            } else {
                // Caso contrário (sem tipo ou tipo inválido), lista todas as categorias.
                $stmt = $pdo->query("SELECT id, nome, tipo, icone FROM categorias ORDER BY nome ASC");
            }
            
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($categorias);
            break;

        // --- CASO POST: Criar ou Atualizar Categoria ---
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (empty($data['nome']) || empty($data['tipo'])) {
                http_response_code(400 );
                echo json_encode(['erro' => 'Nome e Tipo são obrigatórios.']);
                exit;
            }

            if ($id) { // ATUALIZAÇÃO
                $sql = "UPDATE categorias SET nome = :nome, tipo = :tipo, icone = :icone WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':tipo' => $data['tipo'],
                    ':icone' => $data['icone'] ?? null,
                    ':id' => $id
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria atualizada com sucesso!']);
            } else { // CRIAÇÃO
                $sql = "INSERT INTO categorias (nome, tipo, icone) VALUES (:nome, :tipo, :icone)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $data['nome'],
                    ':tipo' => $data['tipo'],
                    ':icone' => $data['icone'] ?? null
                ]);
                http_response_code(201 );
                echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria criada com sucesso!']);
            }
            break;

        // --- CASO DELETE: Excluir Categoria ---
        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID da categoria é obrigatório.']);
                exit;
            }

            // Opcional: Verificar se a categoria está em uso antes de excluir
            // $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM despesas WHERE categoria_id = :id");
            // $stmt_check->execute([':id' => $id]);
            // if ($stmt_check->fetchColumn() > 0) {
            //     http_response_code(409 ); // Conflict
            //     echo json_encode(['erro' => 'Esta categoria está em uso e não pode ser excluída.']);
            //     exit;
            // }

            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria excluída com sucesso.']);
            } else {
                http_response_code(404 );
                echo json_encode(['erro' => 'Categoria não encontrada.']);
            }
            break;
        
        default:
            http_response_code(405 );
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    error_log("Erro na API de categorias: " . $e->getMessage());
    http_response_code(500 );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
