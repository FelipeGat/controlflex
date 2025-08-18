<?php
/**
 * API para criação de novos tenants com notificações por email
 * Arquivo: backend/api/criar_tenant_novo.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir sistema de email
require_once 'sistema_email_notificacao.php';

// Incluir conexão com banco (ajuste o caminho conforme sua estrutura)
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com banco de dados']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'planos':
        listarPlanos($db);
        break;
    case 'criar':
        criarTenant($db);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação não especificada']);
}

function listarPlanos($db) {
    try {
        $query = "SELECT * FROM planos WHERE ativo = 1 ORDER BY valor_mensal ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Se não houver planos no banco, retornar planos padrão
        if (empty($planos)) {
            $planos = [
                [
                    'id' => 1,
                    'nome_plano' => 'Básico',
                    'valor_mensal' => 29.90,
                    'limite_usuarios' => 3,
                    'limite_transacoes' => 1000,
                    'recursos_inclusos' => ['Controle de despesas', 'Relatórios básicos', 'Suporte por email']
                ],
                [
                    'id' => 2,
                    'nome_plano' => 'Profissional',
                    'valor_mensal' => 59.90,
                    'limite_usuarios' => 10,
                    'limite_transacoes' => 5000,
                    'recursos_inclusos' => ['Todos do Básico', 'Relatórios avançados', 'Múltiplos usuários', 'Suporte prioritário']
                ],
                [
                    'id' => 3,
                    'nome_plano' => 'Empresarial',
                    'valor_mensal' => 99.90,
                    'limite_usuarios' => -1,
                    'limite_transacoes' => -1,
                    'recursos_inclusos' => ['Todos do Profissional', 'Usuários ilimitados', 'Transações ilimitadas', 'Suporte 24/7']
                ]
            ];
        }
        
        echo json_encode($planos);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao buscar planos: ' . $e->getMessage()]);
    }
}

function criarTenant($db) {
    try {
        // Ler dados JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Dados inválidos');
        }
        
        // Validar campos obrigatórios
        $camposObrigatorios = ['nome_empresa', 'email_contato', 'admin_nome', 'admin_email', 'admin_senha', 'plano_id'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($input[$campo])) {
                throw new Exception("Campo obrigatório: {$campo}");
            }
        }
        
        // Validar emails
        if (!filter_var($input['email_contato'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email de contato inválido');
        }
        
        if (!filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email do administrador inválido');
        }
        
        // Verificar se email já existe
        $query = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$input['admin_email']]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email já cadastrado no sistema');
        }
        
        // Buscar dados do plano
        $query = "SELECT * FROM planos WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$input['plano_id']]);
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plano) {
            // Se plano não existe no banco, usar dados padrão
            $planosDefault = [
                1 => ['nome_plano' => 'Básico', 'valor_mensal' => 29.90],
                2 => ['nome_plano' => 'Profissional', 'valor_mensal' => 59.90],
                3 => ['nome_plano' => 'Empresarial', 'valor_mensal' => 99.90]
            ];
            $plano = $planosDefault[$input['plano_id']] ?? $planosDefault[1];
        }
        
        // Iniciar transação
        $db->beginTransaction();
        
        // 1. Gerar código único do tenant
        $codigoTenant = gerarCodigoTenant($input['nome_empresa'], $db);
        
        // 2. Criar tenant
        $query = "INSERT INTO tenants (codigo_tenant, nome_empresa, email_contato, telefone, cnpj_cpf, status, criado_em) 
                  VALUES (?, ?, ?, ?, ?, 'ativo', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([
            $codigoTenant,
            $input['nome_empresa'],
            $input['email_contato'],
            $input['telefone'] ?? null,
            $input['cnpj_cpf'] ?? null
        ]);
        
        $tenantId = $db->lastInsertId();
        
        // 3. Criar assinatura (trial de 15 dias)
        $dataVencimento = date('Y-m-d', strtotime('+15 days'));
        $query = "INSERT INTO assinaturas (tenant_id, plano_id, status, data_inicio, data_vencimento, valor_mensal, criado_em) 
                  VALUES (?, ?, 'trial', CURDATE(), ?, ?, NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$tenantId, $input['plano_id'], $dataVencimento, $plano['valor_mensal']]);
        
        // 4. Criar usuário administrador
        $senhaHash = password_hash($input['admin_senha'], PASSWORD_DEFAULT);
        $query = "INSERT INTO usuarios (tenant_id, nome, email, senha, perfil, status, criado_em) 
                  VALUES (?, ?, ?, ?, 'admin', 'ativo', NOW())";
        $stmt = $db->prepare($query);
        $stmt->execute([$tenantId, $input['admin_nome'], $input['admin_email'], $senhaHash]);
        
        // 5. Criar dados iniciais (categorias padrão)
        criarCategoriasIniciais($db, $tenantId);
        
        // 6. Criar bancos padrão
        criarBancosIniciais($db, $tenantId);
        
        // Confirmar transação
        $db->commit();
        
        // 7. NOVO: Enviar emails de notificação
        try {
            // Preparar dados para email
            $dadosCliente = [
                'nome_empresa' => $input['nome_empresa'],
                'email_contato' => $input['email_contato'],
                'telefone' => $input['telefone'] ?? '',
                'cnpj_cpf' => $input['cnpj_cpf'] ?? '',
                'admin_nome' => $input['admin_nome'],
                'admin_email' => $input['admin_email']
            ];
            
            // Email para administrador (você)
            enviarNotificacaoEmail('novo_cadastro', $dadosCliente, ['plano' => $plano]);
            
            // Email de boas-vindas para cliente
            enviarNotificacaoEmail('boas_vindas', $dadosCliente, ['codigo_tenant' => $codigoTenant]);
            
        } catch (Exception $e) {
            // Log do erro de email, mas não falha a criação da conta
            error_log("Erro ao enviar emails: " . $e->getMessage());
        }
        
        // Retornar sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Conta criada com sucesso',
            'codigo_tenant' => $codigoTenant,
            'tenant_id' => $tenantId,
            'trial_dias' => 15,
            'data_vencimento' => $dataVencimento
        ]);
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function gerarCodigoTenant($nomeEmpresa, $db) {
    // Limpar nome e criar base do código
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nomeEmpresa));
    $base = substr($base, 0, 10); // Máximo 10 caracteres
    
    if (empty($base)) {
        $base = 'cliente';
    }
    
    // Verificar se código já existe e adicionar número se necessário
    $contador = 1;
    $codigo = $base;
    
    while (true) {
        $query = "SELECT COUNT(*) FROM tenants WHERE codigo_tenant = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$codigo]);
        
        if ($stmt->fetchColumn() == 0) {
            break;
        }
        
        $contador++;
        $codigo = $base . $contador;
    }
    
    return $codigo;
}

function criarCategoriasIniciais($db, $tenantId) {
    $categorias = [
        ['nome' => 'Alimentação', 'tipo' => 'receita_despesa'],
        ['nome' => 'Transporte', 'tipo' => 'receita_despesa'],
        ['nome' => 'Moradia', 'tipo' => 'receita_despesa'],
        ['nome' => 'Saúde', 'tipo' => 'receita_despesa'],
        ['nome' => 'Educação', 'tipo' => 'receita_despesa'],
        ['nome' => 'Lazer', 'tipo' => 'receita_despesa'],
        ['nome' => 'Vestuário', 'tipo' => 'receita_despesa'],
        ['nome' => 'Outros', 'tipo' => 'receita_despesa']
    ];
    
    $query = "INSERT INTO categorias (tenant_id, nome, tipo, ativo) VALUES (?, ?, ?, 1)";
    $stmt = $db->prepare($query);
    
    foreach ($categorias as $categoria) {
        $stmt->execute([$tenantId, $categoria['nome'], $categoria['tipo']]);
    }
}

function criarBancosIniciais($db, $tenantId) {
    $bancos = [
        ['nome' => 'Banco do Brasil', 'codigo_banco' => '001'],
        ['nome' => 'Bradesco', 'codigo_banco' => '237'],
        ['nome' => 'Itaú', 'codigo_banco' => '341'],
        ['nome' => 'Santander', 'codigo_banco' => '033'],
        ['nome' => 'Caixa Econômica', 'codigo_banco' => '104'],
        ['nome' => 'Nubank', 'codigo_banco' => '260'],
        ['nome' => 'Inter', 'codigo_banco' => '077'],
        ['nome' => 'C6 Bank', 'codigo_banco' => '336']
    ];
    
    $query = "INSERT INTO bancos (tenant_id, nome, codigo_banco, ativo) VALUES (?, ?, ?, 1)";
    $stmt = $db->prepare($query);
    
    foreach ($bancos as $banco) {
        $stmt->execute([$tenantId, $banco['nome'], $banco['codigo_banco']]);
    }
}
?>

