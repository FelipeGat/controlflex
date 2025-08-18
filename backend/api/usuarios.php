<?php
// Garantir que a sessão seja iniciada antes de qualquer output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. CONFIGURAR CORS (permitir apenas origens específicas)
$allowed_origins = [
    'http://localhost',
    'http://localhost:3000',
    // 'https://seusite.com', // coloque aqui seus domínios confiáveis
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: *'); // fallback - cuidado em produção
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// 2. TRATAR REQUISIÇÕES OPTIONS (CORS Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. FUNÇÃO PARA RESPOSTA JSON
function enviarResposta($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// 4. FUNÇÃO PARA OBTER DADOS DE ENTRADA (JSON, FormData, urlencoded)
function obterDadosEntrada() {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (in_array($method, ['POST', 'PUT'])) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (stripos($contentType, 'application/json') !== false) {
            $inputJSON = file_get_contents('php://input');
            $data = json_decode($inputJSON, true);
            return $data ?? [];
        }
        
        if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            if ($method === 'POST') return $_POST;
            if ($method === 'PUT') {
                $put_data = file_get_contents('php://input');
                parse_str($put_data, $put_array);
                return $put_array;
            }
        }
        
        if (stripos($contentType, 'multipart/form-data') !== false) {
            // $_POST para dados e $_FILES para arquivos
            return $_POST;
        }
    }

    // GET e outros
    return $_GET;
}

// 5. TRATAMENTO DE ERROS
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
});

// 6. BLOCO PRINCIPAL DA API
try {
    // 6.1 INCLUIR DEPENDÊNCIAS
    $db_path = __DIR__ . '/../config/db.php';
    if (!file_exists($db_path)) {
        throw new Exception("Arquivo de configuração do banco não encontrado: $db_path");
    }
    require_once $db_path;
    if (!isset($pdo)) {
        throw new Exception("Conexão PDO não foi estabelecida");
    }
    
    // 6.2 DETECTAR ESTRUTURA DA TABELA
    $estrutura_tabela = detectarEstrutura($pdo);
    
    // 6.3 OBTER TENANT ATUAL (CONSISTENTE)
    $tenant_atual = obterTenantConsistente($pdo, $estrutura_tabela);
    
    // 6.4 ROTEAMENTO
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($id) {
                buscarUsuario($id, $pdo, $estrutura_tabela, $tenant_atual);
            } else {
                listarUsuarios($pdo, $estrutura_tabela, $tenant_atual);
            }
            break;
        case 'POST':
            criarUsuario($pdo, $estrutura_tabela, $tenant_atual);
            break;
        case 'PUT':
            if (!$id) {
                enviarResposta(['error' => 'ID é obrigatório para atualização'], 400);
            }
            atualizarUsuario($id, $pdo, $estrutura_tabela, $tenant_atual);
            break;
        case 'DELETE':
            if (!$id) {
                enviarResposta(['error' => 'ID é obrigatório para exclusão'], 400);
            }
            deletarUsuario($id, $pdo, $estrutura_tabela, $tenant_atual);
            break;
        default:
            enviarResposta(['error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    error_log("Erro na API de usuários: " . $e->getMessage());
    enviarResposta([
        'error' => 'Erro interno do servidor',
        'message' => 'Erro inesperado, contate o administrador.',
        'timestamp' => date('Y-m-d H:i:s')
    ], 500);
}


/**
 * Funções auxiliares...
 */
function detectarEstrutura($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
        if ($stmt->rowCount() == 0) {
            throw new Exception("Tabela usuarios não existe");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $estrutura = [
            'colunas_disponiveis' => [],
            'tem_tenant_id' => false,
            'tem_created_at' => false,
            'tem_updated_at' => false,
            'tem_data_criacao' => false,
            'tem_data_atualizacao' => false,
            'tem_foto' => false,
            'tem_telefone' => false,
            'tem_senha' => false,
        ];
        foreach ($colunas as $coluna) {
            $nome_coluna = $coluna['Field'];
            $estrutura['colunas_disponiveis'][] = $nome_coluna;
            
            switch ($nome_coluna) {
                case 'tenant_id': $estrutura['tem_tenant_id'] = true; break;
                case 'created_at': $estrutura['tem_created_at'] = true; break;
                case 'updated_at': $estrutura['tem_updated_at'] = true; break;
                case 'data_criacao': $estrutura['tem_data_criacao'] = true; break;
                case 'data_atualizacao': $estrutura['tem_data_atualizacao'] = true; break;
                case 'foto': $estrutura['tem_foto'] = true; break;
                case 'telefone': $estrutura['tem_telefone'] = true; break;
                case 'senha': $estrutura['tem_senha'] = true; break;
            }
        }
        return $estrutura;
        
    } catch (Exception $e) {
        throw $e;
    }
}

function obterTenantConsistente($pdo, $estrutura) {
    if (!$estrutura['tem_tenant_id']) {
        return 1;
    }
    
    if (isset($_SESSION['tenant_id']) && !empty($_SESSION['tenant_id'])) {
        return $_SESSION['tenant_id'];
    }
    
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT tenant_id FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            
            if ($result && !empty($result['tenant_id'])) {
                $_SESSION['tenant_id'] = $result['tenant_id'];
                return $result['tenant_id'];
            }
        } catch (Exception $e) {
            // ignorar erro aqui
        }
    }
    
    return 1; // Fallback
}

function montarSelect($estrutura) {
    $colunas_select = ['id', 'nome', 'email'];
    $colunas_opcionais = ['perfil', 'status', 'foto', 'telefone', 'tenant_id'];
    
    foreach ($colunas_opcionais as $coluna) {
        if (in_array($coluna, $estrutura['colunas_disponiveis'])) {
            $colunas_select[] = $coluna;
        }
    }
    
    if ($estrutura['tem_created_at']) {
        $colunas_select[] = 'created_at';
    } elseif ($estrutura['tem_data_criacao']) {
        $colunas_select[] = 'data_criacao';
    }
    
    return implode(', ', $colunas_select);
}

function listarUsuarios($pdo, $estrutura, $tenant_id) {
    try {
        $colunas_select = montarSelect($estrutura);
        
        if ($estrutura['tem_tenant_id']) {
            $sql = "SELECT {$colunas_select} FROM usuarios WHERE tenant_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tenant_id]);
        } else {
            $sql = "SELECT {$colunas_select} FROM usuarios";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        enviarResposta([
            'success' => true,
            'data' => $usuarios,
            'total' => count($usuarios),
            'tenant_id' => $tenant_id
        ]);
    } catch (Exception $e) {
        enviarResposta(['error' => 'Erro ao listar usuários', 'details' => $e->getMessage()], 500);
    }
}

function criarUsuario($pdo, $estrutura, $tenant_id) {
    try {
        $input = obterDadosEntrada();
        
        // Validações básicas
        if (empty($input['nome']) || empty($input['email'])) {
            enviarResposta(['error' => 'Nome e email são obrigatórios'], 400);
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            enviarResposta(['error' => 'Email inválido'], 400);
        }
        
        // Verifica email duplicado
        if ($estrutura['tem_tenant_id']) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$input['email'], $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$input['email']]);
        }
        if ($stmt->fetch()) {
            enviarResposta(['error' => 'Email já está em uso'], 400);
        }

        // Monta os dados com base na estrutura da tabela
        $dados_para_inserir = [
            'nome' => trim($input['nome']),
            'email' => trim($input['email']),
            'perfil' => $input['perfil'] ?? 'usuario',
            'status' => $input['status'] ?? 'ativo'
        ];
        
        if ($estrutura['tem_senha']) {
            if (empty($input['senha'])) {
                enviarResposta(['error' => 'Senha é obrigatória'], 400);
            }
            $dados_para_inserir['senha'] = password_hash($input['senha'], PASSWORD_DEFAULT);
        }
        if ($estrutura['tem_tenant_id']) {
            $dados_para_inserir['tenant_id'] = $tenant_id;
        }
        if ($estrutura['tem_foto']) {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoNome = salvarFoto();
                if ($fotoNome) {
                    $dados_para_inserir['foto'] = $fotoNome;
                }
            }
        }
        if ($estrutura['tem_telefone']) {
            $dados_para_inserir['telefone'] = $input['telefone'] ?? null;
        }

        $campos = implode(', ', array_keys($dados_para_inserir));
        $placeholders = ':' . implode(', :', array_keys($dados_para_inserir));
        $sql = "INSERT INTO usuarios ({$campos}) VALUES ({$placeholders})";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados_para_inserir);
        
        $novo_id = $pdo->lastInsertId();
        enviarResposta(['success' => true, 'message' => 'Usuário criado com sucesso', 'id' => $novo_id]);
        
    } catch (Exception $e) {
        enviarResposta(['error' => 'Erro ao criar o usuário', 'details' => $e->getMessage()], 500);
    }
}

function atualizarUsuario($id, $pdo, $estrutura, $tenant_id) {
    try {
        $input = obterDadosEntrada();

        // Verifica a existência do usuário e do tenant
        if ($estrutura['tem_tenant_id']) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        if (!$stmt->fetch()) {
            enviarResposta(['error' => 'Usuário não encontrado'], 404);
        }

        // Montar campos para atualizar
        $campos_possiveis = ['nome', 'email', 'perfil', 'status', 'telefone'];
        if ($estrutura['tem_senha'] && !empty($input['senha'])) {
            $campos_possiveis[] = 'senha';
        }
        if ($estrutura['tem_foto']) {
            // atualizar foto é via upload
        }
        
        $dados_para_atualizar = [];
        
        foreach ($campos_possiveis as $campo) {
            if (isset($input[$campo])) {
                if ($campo === 'email' && !filter_var($input[$campo], FILTER_VALIDATE_EMAIL)) {
                    enviarResposta(['error' => 'Email inválido'], 400);
                }
                if ($campo === 'senha') {
                    $dados_para_atualizar['senha'] = password_hash($input['senha'], PASSWORD_DEFAULT);
                } else {
                    $dados_para_atualizar[$campo] = trim($input[$campo]);
                }
            }
        }

        // Atualizar foto
        if ($estrutura['tem_foto'] && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fotoNome = salvarFoto();
            if ($fotoNome) {
                $dados_para_atualizar['foto'] = $fotoNome;
            }
        }
        
        if (empty($dados_para_atualizar)) {
            enviarResposta(['error' => 'Nenhum dado para atualizar'], 400);
        }
        
        // Timestamps
        if ($estrutura['tem_updated_at']) {
            $dados_para_atualizar['updated_at'] = date('Y-m-d H:i:s');
        } elseif ($estrutura['tem_data_atualizacao']) {
            $dados_para_atualizar['data_atualizacao'] = date('Y-m-d H:i:s');
        }

        $sets = [];
        foreach ($dados_para_atualizar as $chave => $valor) {
            $sets[] = "$chave = :$chave";
        }
        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $dados_para_atualizar['id'] = $id;
        $stmt->execute($dados_para_atualizar);
        
        enviarResposta(['success' => true, 'message' => 'Usuário atualizado com sucesso']);
        
    } catch (Exception $e) {
        enviarResposta(['error' => 'Erro ao atualizar o usuário', 'details' => $e->getMessage()], 500);
    }
}

function deletarUsuario($id, $pdo, $estrutura, $tenant_id) {
    try {
        if ($estrutura['tem_tenant_id']) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        if (!$stmt->fetch()) {
            enviarResposta(['error' => 'Usuário não encontrado'], 404);
        }
        
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        enviarResposta(['success' => true, 'message' => 'Usuário deletado com sucesso']);
        
    } catch (Exception $e) {
        enviarResposta(['error' => 'Erro ao deletar o usuário', 'details' => $e->getMessage()], 500);
    }
}

function buscarUsuario($id, $pdo, $estrutura, $tenant_id) {
    try {
        $colunas_select = montarSelect($estrutura);
        
        if ($estrutura['tem_tenant_id']) {
            $stmt = $pdo->prepare("SELECT {$colunas_select} FROM usuarios WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT {$colunas_select} FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            enviarResposta(['error' => 'Usuário não encontrado'], 404);
        }
        
        enviarResposta(['success' => true, 'data' => $usuario]);
        
    } catch (Exception $e) {
        enviarResposta(['error' => 'Erro ao buscar usuário', 'details' => $e->getMessage()], 500);
    }
}

function salvarFoto() {
    $pasta_destino = __DIR__ . '/../uploads/usuarios/';
    
    if (!file_exists($pasta_destino)) {
        mkdir($pasta_destino, 0755, true);
    }
    
    if (!isset($_FILES['foto'])) {
        return null;
    }
    
    $arquivo = $_FILES['foto'];
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $extensoes_permitidas)) {
        enviarResposta(['error' => 'Formato de imagem não permitido'], 400);
    }
    
    if ($arquivo['size'] > 2 * 1024 * 1024) { // 2MB max
        enviarResposta(['error' => 'Imagem muito grande, máximo 2MB'], 400);
    }
    
    $novo_nome = uniqid('usr_') . '.' . $extensao;
    $destino = $pasta_destino . $novo_nome;
    
    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return $novo_nome;
    }
    
    enviarResposta(['error' => 'Erro ao salvar a imagem'], 500);
}
