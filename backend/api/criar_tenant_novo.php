<?php
// Configurar headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuração do banco
try {
    require_once __DIR__ . '/../config/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro de configuração do banco de dados',
        'error' => $e->getMessage()
    ]);
    exit();
}

// Função para log de debug
function logDebug($message, $data = null) {
    error_log("ControleFlex Debug: " . $message . ($data ? " - " . json_encode($data) : ""));
}

// Função para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para gerar código único do tenant
function gerarCodigoTenant($nome) {
    $codigo = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nome), 0, 6));
    $codigo .= rand(100, 999);
    return $codigo;
}

// Função para criar dados iniciais do tenant
function criarDadosIniciais($pdo, $tenant_id) {
    try {
        // Verificar se tabela categorias existe e tem coluna tenant_id
        $stmt = $pdo->query("SHOW TABLES LIKE 'categorias'");
        if ($stmt->rowCount() > 0) {
            $colunas = $pdo->query("DESCRIBE categorias")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('tenant_id', $colunas)) {
                // Categorias padrão
                $categorias = [
                    'Alimentação',
                    'Transporte',
                    'Moradia',
                    'Saúde',
                    'Educação',
                    'Lazer',
                    'Vestuário',
                    'Outros'
                ];

                foreach ($categorias as $categoria) {
                    $stmt = $pdo->prepare("INSERT INTO categorias (nome, tenant_id) VALUES (?, ?)");
                    $stmt->execute([$categoria, $tenant_id]);
                }
            }
        }

        // Verificar se tabela bancos existe e tem coluna tenant_id
        $stmt = $pdo->query("SHOW TABLES LIKE 'bancos'");
        if ($stmt->rowCount() > 0) {
            $colunas = $pdo->query("DESCRIBE bancos")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('tenant_id', $colunas)) {
                // Bancos padrão
                $bancos = [
                    ['Banco do Brasil', '001'],
                    ['Bradesco', '237'],
                    ['Itaú', '341'],
                    ['Santander', '033'],
                    ['Caixa Econômica', '104'],
                    ['Nubank', '260'],
                    ['Inter', '077'],
                    ['C6 Bank', '336']
                ];

                foreach ($bancos as $banco) {
                    $stmt = $pdo->prepare("INSERT INTO bancos (nome, codigo_banco, tenant_id) VALUES (?, ?, ?)");
                    $stmt->execute([$banco[0], $banco[1], $tenant_id]);
                }
            }
        }

        return true;
    } catch (Exception $e) {
        logDebug("Erro ao criar dados iniciais", $e->getMessage());
        return false;
    }
}

// Processar requisição
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'test':
            // Endpoint de teste
            $tabelas = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tabelas[] = $row[0];
            }
            
            // Verificar estrutura da tabela tenants
            $estrutura_tenants = [];
            try {
                $result = $pdo->query("DESCRIBE tenants");
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $estrutura_tenants[] = $row['Field'];
                }
            } catch (Exception $e) {
                $estrutura_tenants = ['Erro ao verificar estrutura'];
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Conexão com banco funcionando',
                'data' => [
                    'ambiente' => $_ENV['APP_ENV'] ?? 'local',
                    'banco' => $_ENV['DB_NAME'] ?? 'controleflex',
                    'tabelas_existentes' => $tabelas,
                    'estrutura_tenants' => $estrutura_tenants
                ]
            ]);
            break;

        case 'planos':
            // Retornar planos disponíveis
            $planos = [
                [
                    'id' => 1,
                    'nome_plano' => 'Básico',
                    'valor_mensal' => 29.90,
                    'limite_usuarios' => 3,
                    'limite_transacoes' => 1000,
                    'recursos_inclusos' => [
                        'Controle de despesas e receitas',
                        'Relatórios básicos',
                        'Até 3 usuários',
                        'Suporte por email'
                    ]
                ],
                [
                    'id' => 2,
                    'nome_plano' => 'Profissional',
                    'valor_mensal' => 59.90,
                    'limite_usuarios' => 10,
                    'limite_transacoes' => 5000,
                    'recursos_inclusos' => [
                        'Todos os recursos do Básico',
                        'Relatórios avançados',
                        'Até 10 usuários',
                        'Controle de investimentos',
                        'Suporte prioritário'
                    ]
                ],
                [
                    'id' => 3,
                    'nome_plano' => 'Empresarial',
                    'valor_mensal' => 99.90,
                    'limite_usuarios' => -1,
                    'limite_transacoes' => -1,
                    'recursos_inclusos' => [
                        'Todos os recursos do Profissional',
                        'Usuários ilimitados',
                        'Transações ilimitadas',
                        'Dashboard executivo',
                        'Suporte 24/7'
                    ]
                ]
            ];

            echo json_encode([
                'status' => 'success',
                'data' => $planos
            ]);
            break;

        case 'criar':
            // Criar novo tenant
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }

            // Ler dados JSON
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Dados JSON inválidos');
            }

            logDebug("Dados recebidos para criação", $data);

            // Validações
            $errors = [];

            if (empty($data['nome_empresa']) || strlen(trim($data['nome_empresa'])) < 2) {
                $errors[] = 'Nome do cliente é obrigatório e deve ter pelo menos 2 caracteres';
            }

            if (empty($data['email_contato']) || !validarEmail($data['email_contato'])) {
                $errors[] = 'Email de contato é obrigatório e deve ser válido';
            }

            if (empty($data['admin_nome']) || strlen(trim($data['admin_nome'])) < 2) {
                $errors[] = 'Nome do administrador é obrigatório e deve ter pelo menos 2 caracteres';
            }

            if (empty($data['admin_email']) || !validarEmail($data['admin_email'])) {
                $errors[] = 'Email do administrador é obrigatório e deve ser válido';
            }

            if (empty($data['admin_senha']) || strlen($data['admin_senha']) < 6) {
                $errors[] = 'Senha deve ter pelo menos 6 caracteres';
            }

            if (empty($data['plano_id']) || !is_numeric($data['plano_id'])) {
                $errors[] = 'Plano deve ser selecionado';
            }

            if (!empty($errors)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Dados inválidos',
                    'errors' => $errors
                ]);
                exit();
            }

            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM tenants WHERE email_contato = ?");
            $stmt->execute([trim($data['email_contato'])]);
            if ($stmt->fetch()) {
                throw new Exception('Email de contato já cadastrado no sistema');
            }

            // Verificar se email do admin já existe na tabela usuarios
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([trim($data['admin_email'])]);
            if ($stmt->fetch()) {
                throw new Exception('Email do administrador já cadastrado no sistema');
            }

            // Iniciar transação
            $pdo->beginTransaction();

            try {
                // Gerar código único do tenant
                $codigo_tenant = gerarCodigoTenant($data['nome_empresa']);
                
                // Verificar se código já existe
                $stmt = $pdo->prepare("SELECT id FROM tenants WHERE codigo_tenant = ?");
                $stmt->execute([$codigo_tenant]);
                if ($stmt->fetch()) {
                    $codigo_tenant .= rand(10, 99); // Adicionar mais números se já existir
                }

                // ADAPTADO: Criar tenant usando estrutura existente
                $stmt = $pdo->prepare("
                    INSERT INTO tenants (
                        nome_empresa, 
                        cnpj_cpf,
                        email_contato, 
                        telefone, 
                        endereco,
                        status, 
                        plano_id,
                        codigo_tenant,
                        data_criacao,
                        data_atualizacao
                    ) VALUES (?, ?, ?, ?, '', 'trial', ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    trim($data['nome_empresa']),
                    trim($data['cnpj_cpf'] ?? ''),
                    trim($data['email_contato']),
                    trim($data['telefone'] ?? ''),
                    $data['plano_id'],
                    $codigo_tenant
                ]);

                $tenant_id = $pdo->lastInsertId();

                // Criar usuário administrador
                $senha_hash = password_hash($data['admin_senha'], PASSWORD_DEFAULT);
                
                // Verificar se tabela usuarios tem coluna tenant_id
                $colunas = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_COLUMN);
                $temTenantId = in_array('tenant_id', $colunas);
                $temCriadoEm = in_array('criado_em', $colunas);
                
                if ($temTenantId && $temCriadoEm) {
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (
                            nome, 
                            email, 
                            senha, 
                            perfil, 
                            status, 
                            tenant_id,
                            criado_em
                        ) VALUES (?, ?, ?, 'admin', 'ativo', ?, NOW())
                    ");
                    
                    $stmt->execute([
                        trim($data['admin_nome']),
                        trim($data['admin_email']),
                        $senha_hash,
                        $tenant_id
                    ]);
                } else if ($temTenantId) {
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (
                            nome, 
                            email, 
                            senha, 
                            perfil, 
                            status, 
                            tenant_id
                        ) VALUES (?, ?, ?, 'admin', 'ativo', ?)
                    ");
                    
                    $stmt->execute([
                        trim($data['admin_nome']),
                        trim($data['admin_email']),
                        $senha_hash,
                        $tenant_id
                    ]);
                } else {
                    // Fallback se não tem tenant_id
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (
                            nome, 
                            email, 
                            senha, 
                            perfil, 
                            status
                        ) VALUES (?, ?, ?, 'admin', 'ativo')
                    ");
                    
                    $stmt->execute([
                        trim($data['admin_nome']),
                        trim($data['admin_email']),
                        $senha_hash
                    ]);
                }

                // Criar dados iniciais (categorias e bancos) se as tabelas tiverem tenant_id
                criarDadosIniciais($pdo, $tenant_id);

                // Confirmar transação
                $pdo->commit();

                logDebug("Tenant criado com sucesso", [
                    'tenant_id' => $tenant_id,
                    'codigo_tenant' => $codigo_tenant
                ]);

                echo json_encode([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'Conta criada com sucesso!',
                    'data' => [
                        'tenant_id' => $tenant_id,
                        'codigo_tenant' => $codigo_tenant,
                        'trial_ate' => date('Y-m-d', strtotime('+15 days')),
                        'admin_email' => $data['admin_email'],
                        'nome_empresa' => $data['nome_empresa']
                    ]
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Ação não encontrada');
    }

} catch (Exception $e) {
    logDebug("Erro na API", $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>

