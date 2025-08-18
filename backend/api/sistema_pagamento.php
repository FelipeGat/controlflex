<?php
/**
 * Sistema de Pagamento Simplificado - ControleFlex
 * Versão básica para começar a funcionar
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configurações do banco - AJUSTE CONFORME SEU AMBIENTE
try {
    $host = 'localhost';
    $dbname = 'inves783_controleflex';
    $username = 'inves783_control';
    $password = '100%Control!!';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com banco de dados']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'status':
        verificarStatusAssinatura($pdo);
        break;
    case 'gerar_link':
        gerarLinkPagamento($pdo);
        break;
    case 'simular_pagamento':
        simularPagamento($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Ação não especificada']);
}

/**
 * Verifica status da assinatura de um tenant
 */
function verificarStatusAssinatura($pdo) {
    try {
        $tenantId = $_GET['tenant_id'] ?? null;
        
        if (!$tenantId) {
            throw new Exception('ID do tenant é obrigatório');
        }
        
        $query = "SELECT a.*, p.nome_plano, p.valor_mensal,
                  DATEDIFF(a.data_vencimento, CURDATE()) as dias_restantes
                  FROM assinaturas a
                  LEFT JOIN planos p ON a.plano_id = p.id
                  WHERE a.tenant_id = ?
                  ORDER BY a.id DESC
                  LIMIT 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tenantId]);
        $assinatura = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assinatura) {
            echo json_encode([
                'success' => false,
                'status' => 'sem_assinatura',
                'message' => 'Nenhuma assinatura encontrada'
            ]);
            return;
        }
        
        $hoje = new DateTime();
        $vencimento = new DateTime($assinatura['data_vencimento']);
        $diasRestantes = $assinatura['dias_restantes'];
        
        // Determinar status
        if ($assinatura['status'] === 'trial' && $vencimento >= $hoje) {
            $statusFinal = 'trial_ativo';
            $ativa = true;
        } elseif ($assinatura['status'] === 'pago' && $vencimento >= $hoje) {
            $statusFinal = 'ativa';
            $ativa = true;
        } elseif ($diasRestantes < 0) {
            $statusFinal = 'vencida';
            $ativa = false;
        } else {
            $statusFinal = 'pendente';
            $ativa = true;
        }
        
        echo json_encode([
            'success' => true,
            'ativa' => $ativa,
            'status' => $statusFinal,
            'dias_restantes' => $diasRestantes,
            'data_vencimento' => $assinatura['data_vencimento'],
            'plano' => $assinatura['nome_plano'] ?? 'Básico',
            'valor_mensal' => $assinatura['valor_mensal'] ?? 29.90,
            'tipo_assinatura' => $assinatura['status']
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Gera link de pagamento (versão simplificada)
 */
function gerarLinkPagamento($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $tenantId = $input['tenant_id'] ?? null;
        $planoId = $input['plano_id'] ?? 1;
        $meses = $input['meses'] ?? 1;
        
        if (!$tenantId) {
            throw new Exception('ID do tenant é obrigatório');
        }
        
        // Buscar dados do tenant
        $query = "SELECT * FROM tenants WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tenantId]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tenant) {
            throw new Exception('Tenant não encontrado');
        }
        
        // Buscar dados do plano
        $query = "SELECT * FROM planos WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$planoId]);
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plano) {
            // Planos padrão se não existir no banco
            $planosDefault = [
                1 => ['nome_plano' => 'Básico', 'valor_mensal' => 29.90],
                2 => ['nome_plano' => 'Profissional', 'valor_mensal' => 59.90],
                3 => ['nome_plano' => 'Empresarial', 'valor_mensal' => 99.90]
            ];
            $plano = $planosDefault[$planoId] ?? $planosDefault[1];
        }
        
        $valorTotal = $plano['valor_mensal'] * $meses;
        
        // Por enquanto, retorna dados simulados
        // Futuramente aqui você integrará com Mercado Pago, PagSeguro, etc.
        
        echo json_encode([
            'success' => true,
            'link_pagamento' => 'https://exemplo.com/pagamento/simulado',
            'valor_total' => $valorTotal,
            'plano' => $plano['nome_plano'],
            'meses' => $meses,
            'message' => 'Link de pagamento gerado (versão simulada)'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Simula pagamento aprovado (para testes)
 */
function simularPagamento($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $tenantId = $input['tenant_id'] ?? null;
        $planoId = $input['plano_id'] ?? 1;
        $meses = $input['meses'] ?? 1;
        
        if (!$tenantId) {
            throw new Exception('ID do tenant é obrigatório');
        }
        
        // Buscar assinatura atual
        $query = "SELECT * FROM assinaturas WHERE tenant_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tenantId]);
        $assinatura = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Buscar dados do plano
        $query = "SELECT * FROM planos WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$planoId]);
        $plano = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plano) {
            $planosDefault = [
                1 => ['nome_plano' => 'Básico', 'valor_mensal' => 29.90],
                2 => ['nome_plano' => 'Profissional', 'valor_mensal' => 59.90],
                3 => ['nome_plano' => 'Empresarial', 'valor_mensal' => 99.90]
            ];
            $plano = $planosDefault[$planoId] ?? $planosDefault[1];
        }
        
        $valorPago = $plano['valor_mensal'] * $meses;
        
        if ($assinatura) {
            // Calcular nova data de vencimento
            $dataAtual = new DateTime($assinatura['data_vencimento']);
            $hoje = new DateTime();
            
            // Se já venceu, conta a partir de hoje
            if ($dataAtual < $hoje) {
                $dataAtual = $hoje;
            }
            
            $dataAtual->add(new DateInterval("P{$meses}M"));
            $novaDataVencimento = $dataAtual->format('Y-m-d');
            
            // Atualizar assinatura existente
            $query = "UPDATE assinaturas SET 
                      plano_id = ?, 
                      data_vencimento = ?, 
                      status = 'pago',
                      valor_mensal = ?,
                      data_ultimo_pagamento = NOW()
                      WHERE id = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$planoId, $novaDataVencimento, $plano['valor_mensal'], $assinatura['id']]);
            
        } else {
            // Criar nova assinatura
            $dataInicio = date('Y-m-d');
            $dataVencimento = date('Y-m-d', strtotime("+{$meses} month"));
            
            $query = "INSERT INTO assinaturas (tenant_id, plano_id, data_inicio, data_vencimento, 
                      status, valor_mensal, data_ultimo_pagamento, criado_em) 
                      VALUES (?, ?, ?, ?, 'pago', ?, NOW(), NOW())";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$tenantId, $planoId, $dataInicio, $dataVencimento, $plano['valor_mensal']]);
        }
        
        // Ativar tenant
        $query = "UPDATE tenants SET status = 'ativo' WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$tenantId]);
        
        // Enviar email de confirmação (se sistema de email estiver funcionando)
        try {
            if (function_exists('enviarNotificacaoEmail')) {
                $query = "SELECT * FROM tenants WHERE id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$tenantId]);
                $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tenant) {
                    $dadosCliente = [
                        'nome_empresa' => $tenant['nome_empresa'],
                        'admin_nome' => $tenant['nome_empresa'], // Simplificado
                        'admin_email' => $tenant['email_contato']
                    ];
                    
                    enviarNotificacaoEmail('pagamento_confirmado', $dadosCliente, ['valor' => $valorPago]);
                }
            }
        } catch (Exception $e) {
            // Log erro mas não falha
            error_log("Erro ao enviar email de confirmação: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Pagamento simulado com sucesso',
            'valor_pago' => $valorPago,
            'nova_data_vencimento' => $novaDataVencimento ?? $dataVencimento,
            'status' => 'pago'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>

