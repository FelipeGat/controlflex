<?php
// /api/fornecedores.php

// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
// Defina a URL do seu frontend. Para desenvolvimento local, é http://localhost:3000
$frontend_url = "http://localhost:3000"; 
header("Access-Control-Allow-Origin: " . $frontend_url );
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// O navegador envia uma requisição OPTIONS (preflight) antes de POST ou DELETE
// para verificar as políticas de CORS. Respondemos com 200 OK para permitir.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200 );
    exit();
}

// Definir o tipo de conteúdo da resposta como JSON
header("Content-Type: application/json; charset=UTF-8");

// 2. INICIALIZAÇÃO E DEPENDÊNCIAS
// ===================================================
// Carrega a configuração do banco de dados.
// O 'require_once' garante que o script pare se o arquivo não for encontrado.
require_once __DIR__ . '/../config/db.php'; 

// 3. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Usa um switch para tratar os diferentes métodos HTTP (GET, POST, DELETE)
    switch ($method) {
        case 'GET':
            // Verifica se o ID do usuário foi fornecido na URL
            if (!isset($_GET['usuario_id'])) {
                http_response_code(400 ); // Bad Request
                echo json_encode(['erro' => 'O parâmetro usuario_id é obrigatório.']);
                exit;
            }
            
            // Valida o ID do usuário para garantir que é um número inteiro
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
            
            if ($usuario_id === false) {
                http_response_code(400 ); // Bad Request
                echo json_encode(['erro' => 'O parâmetro usuario_id deve ser um número inteiro.']);
                exit;
            }

            // Prepara e executa a query para buscar os fornecedores do usuário específico
            $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE usuario_id = :uid ORDER BY nome ASC");
            $stmt->execute(['uid' => $usuario_id]);
            $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Retorna a lista de fornecedores em formato JSON
            echo json_encode($fornecedores);
            break;

        case 'POST':
            // Lê o corpo da requisição, que deve ser um JSON
            $data = json_decode(file_get_contents("php://input"), true);

            // Verifica se o JSON é válido
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400 ); // Bad Request
                echo json_encode(['erro' => 'JSON inválido no corpo da requisição.']);
                exit;
            }

            // Extrai e sanitiza os dados do JSON recebido
            $id = $data['id'] ?? null; // ID existe apenas se for uma edição
            $usuario_id = filter_var($data['usuario_id'] ?? null, FILTER_VALIDATE_INT);
            $nome = filter_var($data['nome'] ?? '', FILTER_SANITIZE_STRING);
            $contato = filter_var($data['contato'] ?? '', FILTER_SANITIZE_STRING);
            $cnpj = filter_var($data['cnpj'] ?? '', FILTER_SANITIZE_STRING);
            $telefone = filter_var($data['telefone'] ?? '', FILTER_SANITIZE_STRING); // <-- CAMPO CORRIGIDO
            $observacoes = filter_var($data['observacoes'] ?? '', FILTER_SANITIZE_STRING);

            // Valida campos obrigatórios
            if (!$usuario_id || !$nome) {
                http_response_code(400 ); // Bad Request
                echo json_encode(['erro' => 'Campos obrigatórios (usuário e nome) não fornecidos.']);
                exit;
            }

            // Se um ID foi fornecido, trata-se de uma ATUALIZAÇÃO (UPDATE)
            if ($id) {
                // Medida de segurança: verifica se o fornecedor que está sendo editado pertence ao usuário logado
                $stmt = $pdo->prepare("SELECT id FROM fornecedores WHERE id = :id AND usuario_id = :uid");
                $stmt->execute(['id' => $id, 'uid' => $usuario_id]);
                if ($stmt->fetch() === false) {
                    http_response_code(403 ); // Forbidden
                    echo json_encode(['erro' => 'Acesso negado. O fornecedor não pertence a este usuário.']);
                    exit;
                }

                // Prepara e executa a query de atualização
                $sql = "UPDATE fornecedores SET nome = :nome, contato = :contato, cnpj = :cnpj, telefone = :telefone, observacoes = :obs WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nome' => $nome, 
                    'contato' => $contato, 
                    'cnpj' => $cnpj, 
                    'telefone' => $telefone, // <-- CAMPO CORRIGIDO
                    'obs' => $observacoes, 
                    'id' => $id
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Fornecedor atualizado.']);

            } else {
                // Se não há ID, trata-se de uma CRIAÇÃO (INSERT)
                $sql = "INSERT INTO fornecedores (usuario_id, nome, contato, cnpj, telefone, observacoes) 
                        VALUES (:uid, :nome, :contato, :cnpj, :telefone, :obs)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'uid' => $usuario_id, 
                    'nome' => $nome, 
                    'contato' => $contato, 
                    'cnpj' => $cnpj, 
                    'telefone' => $telefone, // <-- CAMPO CORRIGIDO
                    'obs' => $observacoes
                ]);
                http_response_code(201 ); // Created
                echo json_encode(['sucesso' => true, 'id' => $pdo->lastInsertId()]);
            }
            break;

        case 'DELETE':
            // Extrai e valida o ID do fornecedor da URL
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                http_response_code(400 ); // Bad Request
                echo json_encode(['erro' => 'ID do fornecedor não informado na URL.']);
                exit;
            }
            
            // Prepara e executa a exclusão
            // (Opcional: Adicionar verificação de usuario_id aqui também por segurança)
            $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Verifica se alguma linha foi de fato excluída
            if ($stmt->rowCount() > 0) {
                echo json_encode(['sucesso' => true, 'mensagem' => 'Fornecedor excluído.']);
            } else {
                http_response_code(404 ); // Not Found
                echo json_encode(['erro' => 'Fornecedor não encontrado.']);
            }
            break;

        default:
            // Se o método HTTP não for GET, POST ou DELETE, retorna um erro
            http_response_code(405 ); // Method Not Allowed
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) { // Captura qualquer tipo de erro (PDOException, etc.)
    // Loga o erro real no servidor para depuração
    error_log("Erro na API de fornecedores: " . $e->getMessage());
    
    // Envia uma resposta de erro genérica para o cliente
    http_response_code(500 ); // Internal Server Error
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
