<?php
/**
 * Webhook Simplificado - ControleFlex
 * Versão básica para receber notificações de pagamento
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Função para log
function logWebhook($message) {
    $logFile = __DIR__ . '/logs/webhook_simples.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    // Criar diretório de logs se não existir
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    // Log da requisição recebida
    $rawInput = file_get_contents('php://input');
    
    logWebhook("=== WEBHOOK RECEBIDO ===");
    logWebhook("Method: " . $_SERVER['REQUEST_METHOD']);
    logWebhook("Raw Input: " . $rawInput);
    
    // Verificar se é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        logWebhook("Erro: Método não permitido");
        exit;
    }
    
    // Decodificar dados
    $dados = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'JSON inválido']);
        logWebhook("Erro: JSON inválido - " . json_last_error_msg());
        exit;
    }
    
    logWebhook("Dados recebidos: " . json_encode($dados));
    
    // Processar webhook (versão simplificada)
    if (isset($dados['type']) && $dados['type'] === 'payment') {
        
        if (isset($dados['data']['id'])) {
            $paymentId = $dados['data']['id'];
            logWebhook("Processando pagamento ID: " . $paymentId);
            
            // Aqui você pode adicionar lógica para:
            // 1. Buscar o pagamento na API do gateway
            // 2. Atualizar status no banco de dados
            // 3. Enviar emails de confirmação
            
            // Por enquanto, apenas loga
            logWebhook("Pagamento processado com sucesso");
            
            http_response_code(200);
            echo json_encode(['message' => 'Webhook processado com sucesso']);
            
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID do pagamento não encontrado']);
            logWebhook("Erro: ID do pagamento não encontrado");
        }
        
    } else {
        // Tipo de notificação não suportado
        http_response_code(200);
        echo json_encode(['message' => 'Tipo de notificação ignorado']);
        logWebhook("Tipo de notificação ignorado: " . ($dados['type'] ?? 'não especificado'));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor']);
    logWebhook("Exceção capturada: " . $e->getMessage());
}

logWebhook("=== FIM DO WEBHOOK ===\n");
?>

