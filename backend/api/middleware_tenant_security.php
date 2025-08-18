<?php
/**
 * Middleware de Segurança Multi-Tenant - Versão Compatível
 * 
 * Versão adaptada para funcionar com o sistema de login existente
 * Mantém segurança mas é menos restritiva
 */

class MiddlewareTenantCompativel {
    private $pdo;
    private $debug = true; // Ativar logs para debug
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtém o tenant_id do usuário atual
     * Tenta múltiplas formas de identificar o usuário
     */
    public function obterTenantAtual() {
        try {
            // Método 1: Verificar se há tenant_id na sessão
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            if (isset($_SESSION['tenant_id'])) {
                $this->log("Tenant encontrado na sessão: " . $_SESSION['tenant_id']);
                return $_SESSION['tenant_id'];
            }
            
            // Método 2: Verificar se há user_id na sessão e buscar tenant
            if (isset($_SESSION['user_id'])) {
                $stmt = $this->pdo->prepare("
                    SELECT tenant_id FROM usuarios 
                    WHERE id = ? AND tenant_id IS NOT NULL
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $result = $stmt->fetch();
                
                if ($result && $result['tenant_id']) {
                    $_SESSION['tenant_id'] = $result['tenant_id']; // Salvar na sessão
                    $this->log("Tenant encontrado via user_id: " . $result['tenant_id']);
                    return $result['tenant_id'];
                }
            }
            
            // Método 3: Verificar se há email na sessão e buscar tenant
            if (isset($_SESSION['email'])) {
                $stmt = $this->pdo->prepare("
                    SELECT tenant_id FROM usuarios 
                    WHERE email = ? AND tenant_id IS NOT NULL
                ");
                $stmt->execute([$_SESSION['email']]);
                $result = $stmt->fetch();
                
                if ($result && $result['tenant_id']) {
                    $_SESSION['tenant_id'] = $result['tenant_id']; // Salvar na sessão
                    $this->log("Tenant encontrado via email: " . $result['tenant_id']);
                    return $result['tenant_id'];
                }
            }
            
            // Método 4: Se não encontrou tenant, assumir tenant padrão (1)
            // Isso permite que usuários antigos continuem funcionando
            $this->log("Nenhum tenant específico encontrado, usando tenant padrão (1)");
            return 1;
            
        } catch (Exception $e) {
            $this->log("Erro ao obter tenant: " . $e->getMessage());
            return 1; // Fallback para tenant padrão
        }
    }
    
    /**
     * Aplica filtro de tenant em consultas SQL
     */
    public function aplicarFiltroTenant($sql, $params = [], $tenant_id = null) {
        if ($tenant_id === null) {
            $tenant_id = $this->obterTenantAtual();
        }
        
        // Verificar se a consulta já tem WHERE
        if (stripos($sql, 'WHERE') !== false) {
            $sql .= " AND tenant_id = ?";
        } else {
            $sql .= " WHERE tenant_id = ?";
        }
        
        $params[] = $tenant_id;
        
        $this->log("SQL com filtro: " . $sql);
        $this->log("Parâmetros: " . json_encode($params));
        
        return [$sql, $params];
    }
    
    /**
     * Executa consulta com filtro de tenant
     */
    public function executarConsulta($sql, $params = []) {
        try {
            list($sql_filtrado, $params_filtrados) = $this->aplicarFiltroTenant($sql, $params);
            
            $stmt = $this->pdo->prepare($sql_filtrado);
            $stmt->execute($params_filtrados);
            
            return $stmt;
            
        } catch (Exception $e) {
            $this->log("Erro na consulta: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verifica se um registro pertence ao tenant atual
     */
    public function verificarPropriedade($tabela, $id, $tenant_id = null) {
        if ($tenant_id === null) {
            $tenant_id = $this->obterTenantAtual();
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM {$tabela} 
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$id, $tenant_id]);
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            $this->log("Erro ao verificar propriedade: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adiciona tenant_id em inserções
     */
    public function adicionarTenantInsert($dados, $tenant_id = null) {
        if ($tenant_id === null) {
            $tenant_id = $this->obterTenantAtual();
        }
        
        $dados['tenant_id'] = $tenant_id;
        return $dados;
    }
    
    /**
     * Log para debug
     */
    private function log($message) {
        if ($this->debug) {
            error_log("[TENANT MIDDLEWARE] " . $message);
        }
    }
    
    /**
     * Retorna informações de debug
     */
    public function getDebugInfo() {
        return [
            'session_data' => $_SESSION ?? [],
            'tenant_atual' => $this->obterTenantAtual(),
            'session_status' => session_status(),
            'session_id' => session_id()
        ];
    }
}

/**
 * Função helper para usar o middleware facilmente
 */
function obterMiddlewareTenant($pdo) {
    static $middleware = null;
    
    if ($middleware === null) {
        $middleware = new MiddlewareTenantCompativel($pdo);
    }
    
    return $middleware;
}

/**
 * Função para resposta JSON padronizada
 */
function responderJSON($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Função para tratar requisições OPTIONS (CORS)
 */
function tratarCORS() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        http_response_code(200);
        exit;
    }
}
?>

