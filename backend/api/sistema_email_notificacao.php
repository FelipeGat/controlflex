<?php
/**
 * Sistema de Notificação por Email - ControleFlex
 * Versão corrigida e funcional
 */

class EmailNotificacao {
    
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username = 'comercial.delta2024@gmail.com';
    private $smtp_password = 'ljns gfls oeym jctb';
    private $admin_email = 'felipehenriquegat@gmail.com';
    
    /**
     * Envia email usando PHPMailer ou mail() nativo
     */
    private function enviarEmail($destinatario, $assunto, $corpo, $isHTML = true) {
        // Verificar se PHPMailer está disponível
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->enviarComPHPMailer($destinatario, $assunto, $corpo, $isHTML);
        } else {
            // Fallback para mail() nativo do PHP
            return $this->enviarEmailNativo($destinatario, $assunto, $corpo);
        }
    }
    
    /**
     * Envia email usando PHPMailer
     */
    private function enviarComPHPMailer($destinatario, $assunto, $corpo, $isHTML) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            $mail->CharSet = 'UTF-8';
            
            // Configurações do email
            $mail->setFrom($this->smtp_username, 'ControleFlex');
            $mail->addAddress($destinatario);
            $mail->isHTML($isHTML);
            $mail->Subject = $assunto;
            $mail->Body = $corpo;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erro PHPMailer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fallback para envio de email usando mail() nativo
     */
    private function enviarEmailNativo($destinatario, $assunto, $corpo) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: ControleFlex <{$this->smtp_username}>" . "\r\n";
        
        return mail($destinatario, $assunto, $corpo, $headers);
    }
    
    /**
     * Função principal para enviar notificações
     */
    public function enviarNotificacaoEmail($tipo, $dadosCliente, $dadosExtras = []) {
        try {
            switch ($tipo) {
                case 'novo_cadastro':
                    return $this->notificarNovoCadastro($dadosCliente, $dadosExtras['plano'] ?? []);
                    
                case 'boas_vindas':
                    return $this->enviarBoasVindas($dadosCliente, $dadosExtras['codigo_tenant'] ?? '');
                    
                case 'lembrete_vencimento':
                    return $this->enviarLembreteVencimento($dadosCliente, $dadosExtras['dias'] ?? 0);
                    
                case 'pagamento_confirmado':
                    return $this->notificarPagamentoConfirmado($dadosCliente, $dadosExtras['valor'] ?? 0);
                    
                default:
                    error_log("Tipo de email não reconhecido: " . $tipo);
                    return false;
            }
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notifica administrador sobre novo cadastro
     */
    public function notificarNovoCadastro($dadosCliente, $planoEscolhido) {
        $assunto = "🎉 Novo Cliente Cadastrado - ControleFlex";
        $corpo = $this->templateNovoCadastro($dadosCliente, $planoEscolhido);
        
        return $this->enviarEmail($this->admin_email, $assunto, $corpo);
    }
    
    /**
     * Envia email de boas-vindas para o cliente
     */
    public function enviarBoasVindas($dadosCliente, $codigoTenant) {
        $assunto = "Bem-vindo ao ControleFlex! Sua conta foi criada com sucesso";
        $corpo = $this->templateBoasVindas($dadosCliente, $codigoTenant);
        
        return $this->enviarEmail($dadosCliente['email_contato'], $assunto, $corpo);
    }
    
    /**
     * Envia lembrete de vencimento do trial
     */
    public function enviarLembreteVencimento($dadosCliente, $diasRestantes) {
        $assunto = "⏰ Seu trial do ControleFlex vence em {$diasRestantes} dias";
        $corpo = $this->templateLembreteVencimento($dadosCliente, $diasRestantes);
        
        return $this->enviarEmail($dadosCliente['email_contato'], $assunto, $corpo);
    }
    
    /**
     * Notifica pagamento confirmado
     */
    public function notificarPagamentoConfirmado($dadosCliente, $valorPago) {
        // Email para o cliente
        $assuntoCliente = "✅ Pagamento confirmado - ControleFlex";
        $corpoCliente = $this->templatePagamentoCliente($dadosCliente, $valorPago);
        $this->enviarEmail($dadosCliente['email_contato'], $assuntoCliente, $corpoCliente);
        
        // Email para o administrador
        $assuntoAdmin = "💰 Pagamento Recebido - ControleFlex";
        $corpoAdmin = $this->templatePagamentoAdmin($dadosCliente, $valorPago);
        return $this->enviarEmail($this->admin_email, $assuntoAdmin, $corpoAdmin);
    }
    
    /**
     * Template: Notificação de novo cadastro para admin
     */
    private function templateNovoCadastro($dados, $plano) {
        $dataHora = date('d/m/Y H:i:s');
        $valorPlano = isset($plano['valor_mensal']) ? number_format($plano['valor_mensal'], 2, ',', '.') : '29,90';
        $nomePlano = isset($plano['nome_plano']) ? $plano['nome_plano'] : 'Básico';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #667eea; }
                .highlight { background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Novo Cliente Cadastrado!</h1>
                    <p>Um novo cliente se cadastrou no ControleFlex</p>
                </div>
                <div class='content'>
                    <div class='highlight'>
                        <strong>Data/Hora:</strong> {$dataHora}
                    </div>
                    
                    <div class='info-box'>
                        <h3>📋 Dados do Cliente</h3>
                        <p><strong>Nome:</strong> {$dados['nome_empresa']}</p>
                        <p><strong>Email:</strong> {$dados['email_contato']}</p>
                        <p><strong>Telefone:</strong> " . ($dados['telefone'] ?: 'Não informado') . "</p>
                        <p><strong>CNPJ/CPF:</strong> " . ($dados['cnpj_cpf'] ?: 'Não informado') . "</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>👤 Administrador</h3>
                        <p><strong>Nome:</strong> {$dados['admin_nome']}</p>
                        <p><strong>Email:</strong> {$dados['admin_email']}</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>💼 Plano Escolhido</h3>
                        <p><strong>Plano:</strong> {$nomePlano}</p>
                        <p><strong>Valor:</strong> R$ {$valorPlano}/mês</p>
                        <p><strong>Trial:</strong> 15 dias gratuitos</p>
                    </div>
                    
                    <div class='highlight'>
                        <p><strong>💡 Próximos passos:</strong></p>
                        <p>• O cliente tem 15 dias de trial gratuito</p>
                        <p>• Acompanhe o uso através do dashboard administrativo</p>
                        <p>• Lembretes de vencimento serão enviados automaticamente</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template: Boas-vindas para cliente
     */
    private function templateBoasVindas($dados, $codigoTenant) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .welcome-box { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; text-align: center; }
                .info-box { background: #e8f4fd; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .button { background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Bem-vindo ao ControleFlex!</h1>
                    <p>Sua conta foi criada com sucesso</p>
                </div>
                <div class='content'>
                    <div class='welcome-box'>
                        <h2>Olá, {$dados['admin_nome']}!</h2>
                        <p>Parabéns! Sua conta no ControleFlex foi criada com sucesso e você já pode começar a organizar suas finanças.</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>📋 Seus dados de acesso:</h3>
                        <p><strong>Código da empresa:</strong> {$codigoTenant}</p>
                        <p><strong>Email:</strong> {$dados['admin_email']}</p>
                        <p><strong>Link de acesso:</strong> <a href='http://localhost:3000/controleflex'>Acessar ControleFlex</a></p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>🎁 Seu trial gratuito:</h3>
                        <p>• <strong>15 dias</strong> de acesso completo</p>
                        <p>• Todas as funcionalidades liberadas</p>
                        <p>• Sem limitações de uso</p>
                        <p>• Suporte completo incluído</p>
                    </div>
                    
                    <div class='welcome-box'>
                        <h3>🚀 Primeiros passos:</h3>
                        <p>1. Faça login no sistema</p>
                        <p>2. Configure seus dados pessoais</p>
                        <p>3. Cadastre seus familiares</p>
                        <p>4. Comece a registrar suas finanças</p>
                        
                        <a href='http://localhost:3000/controleflex' class='button'>Começar agora</a>
                    </div>
                    
                    <p style='text-align: center; color: #666; font-size: 0.9em;'>
                        Precisa de ajuda? Responda este email que te ajudaremos!
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template: Lembrete de vencimento
     */
    private function templateLembreteVencimento($dados, $dias) {
        $urgencia = $dias <= 3 ? 'urgent' : 'normal';
        $emoji = $dias <= 3 ? '🚨' : '⏰';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .alert-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .button { background: #00b894; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$emoji} Trial vencendo em {$dias} dias</h1>
                    <p>Não perca o acesso ao ControleFlex</p>
                </div>
                <div class='content'>
                    <div class='alert-box'>
                        <h3>Olá, {$dados['admin_nome']}!</h3>
                        <p>Seu período de trial gratuito do ControleFlex vence em <strong>{$dias} dias</strong>.</p>
                        <p>Para continuar aproveitando todas as funcionalidades, efetue o pagamento da sua assinatura.</p>
                    </div>
                    
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='http://localhost:3000/controleflex/pagamento' class='button'>Efetuar Pagamento</a>
                    </div>
                    
                    <p><strong>O que você perde se não renovar:</strong></p>
                    <ul>
                        <li>Controle completo de despesas e receitas</li>
                        <li>Relatórios detalhados</li>
                        <li>Gestão de investimentos</li>
                        <li>Acesso aos seus dados históricos</li>
                    </ul>
                    
                    <p style='color: #666; font-size: 0.9em;'>
                        Dúvidas? Responda este email que te ajudaremos!
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template: Pagamento confirmado (cliente)
     */
    private function templatePagamentoCliente($dados, $valor) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #00b894, #00a085); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .success-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Pagamento Confirmado!</h1>
                    <p>Sua assinatura está ativa</p>
                </div>
                <div class='content'>
                    <div class='success-box'>
                        <h3>Parabéns, {$dados['admin_nome']}!</h3>
                        <p>Seu pagamento de <strong>R$ " . number_format($valor, 2, ',', '.') . "</strong> foi confirmado com sucesso.</p>
                        <p>Sua assinatura do ControleFlex está ativa por mais 30 dias.</p>
                    </div>
                    
                    <p><strong>Próxima cobrança:</strong> " . date('d/m/Y', strtotime('+30 days')) . "</p>
                    <p><strong>Valor:</strong> R$ " . number_format($valor, 2, ',', '.') . "</p>
                    
                    <p>Continue aproveitando todas as funcionalidades do ControleFlex para organizar suas finanças!</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template: Pagamento confirmado (admin)
     */
    private function templatePagamentoAdmin($dados, $valor) {
        $dataHora = date('d/m/Y H:i:s');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #00b894, #00a085); color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #00b894; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💰 Pagamento Recebido!</h1>
                    <p>Nova receita no ControleFlex</p>
                </div>
                <div class='content'>
                    <div class='info-box'>
                        <h3>💳 Detalhes do Pagamento</h3>
                        <p><strong>Cliente:</strong> {$dados['nome_empresa']}</p>
                        <p><strong>Email:</strong> {$dados['admin_email']}</p>
                        <p><strong>Valor:</strong> R$ " . number_format($valor, 2, ',', '.') . "</p>
                        <p><strong>Data/Hora:</strong> {$dataHora}</p>
                    </div>
                    
                    <div class='info-box'>
                        <h3>📊 Resumo</h3>
                        <p>Mais um cliente ativo no ControleFlex!</p>
                        <p>Assinatura renovada por 30 dias.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
}

/**
 * Função global para facilitar o uso
 */
function enviarNotificacaoEmail($tipo, $dadosCliente, $dadosExtras = []) {
    $emailSystem = new EmailNotificacao();
    return $emailSystem->enviarNotificacaoEmail($tipo, $dadosCliente, $dadosExtras);
}
?>

