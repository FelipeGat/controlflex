<?php
/**
 * API de Lançamentos - Sistema ControleFlex
 * Versão corrigida com funcionalidades completas
 */

// Headers CORS mais seguros
$allowed_origins = [
    'http://localhost:3000',
    'https://investsolucoesdigitais.com.br'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Origin: http://localhost:3000" );
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuração do banco
require_once __DIR__ . '/../config/db.php';

/**
 * Classe para gerenciar lançamentos
 */
class LancamentosAPI {
    private $pdo;
    private $usuario_id;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Valida e define o usuário
     */
    private function setUsuario($usuario_id) {
        $usuario_id = filter_var($usuario_id, FILTER_VALIDATE_INT);
        if (!$usuario_id || $usuario_id <= 0) {
            throw new Exception('ID do usuário inválido', 400);
        }
        
        // Verificar se usuário existe
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND status = 'ativo'");
        $stmt->execute([$usuario_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Usuário não encontrado ou inativo', 404);
        }
        
        $this->usuario_id = $usuario_id;
    }
    
    /**
     * Lista lançamentos com filtros e paginação
     */
    public function listar($params) {
        $this->setUsuario($params['usuario_id'] ?? null);
        
        // Parâmetros de paginação
        $pagina = max(1, intval($params['pagina'] ?? 1));
        $por_pagina = min(100, max(10, intval($params['por_pagina'] ?? 20)));
        $offset = ($pagina - 1) * $por_pagina;
        
        // Construir filtros
        $filtros = $this->construirFiltros($params);
        $where_despesas = $filtros['where_despesas'];
        $where_receitas = $filtros['where_receitas'];
        $bind_params = $filtros['bind_params'];
        
        // Query principal com JOIN para nomes dos familiares
        $sql = "
            SELECT 
                d.id,
                'despesa' AS tipo,
                COALESCE(fo.nome, 'Fornecedor não informado') AS descricao,
                d.valor,
                d.data_compra AS data_prevista,
                d.data_pagamento AS data_real,
                d.quem_comprou AS familiar_id,
                f1.nome AS familiar,
                c1.nome AS categoria,
                d.observacoes
            FROM despesas d
            LEFT JOIN familiares f1 ON d.quem_comprou = f1.id
            LEFT JOIN categorias c1 ON d.categoria_id = c1.id
            LEFT JOIN fornecedores fo ON d.onde_comprou = fo.id
            WHERE d.usuario_id = :usuario_id1 $where_despesas

            UNION ALL

            SELECT 
                r.id,
                'receita' AS tipo,
                COALESCE(r.origem_receita, 'Origem não informada') AS descricao,
                r.valor,
                r.data_prevista_recebimento AS data_prevista,
                r.data_recebimento AS data_real,
                r.quem_recebeu AS familiar_id,
                f2.nome AS familiar,
                c2.nome AS categoria,
                r.observacoes
            FROM receitas r
            LEFT JOIN familiares f2 ON r.quem_recebeu = f2.id
            LEFT JOIN categorias c2 ON r.categoria_id = c2.id
            WHERE r.usuario_id = :usuario_id2 $where_receitas

            ORDER BY data_prevista DESC, tipo, id DESC
            LIMIT :limit OFFSET :offset
        ";
        
        // Preparar parâmetros
        $bind_params[':usuario_id1'] = $this->usuario_id;
        $bind_params[':usuario_id2'] = $this->usuario_id;
        $bind_params[':limit'] = $por_pagina;
        $bind_params[':offset'] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind dos parâmetros
        foreach ($bind_params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular status e processar dados
        $hoje = date('Y-m-d');
        foreach ($lancamentos as &$lancamento) {
            $lancamento['status'] = $this->calcularStatus($lancamento['data_prevista'], $lancamento['data_real'], $hoje);
            $lancamento['valor'] = floatval($lancamento['valor']);
        }
        
        // Contar total de registros
        $total = $this->contarTotal($params);
        
        return [
            'success' => true,
            'data' => $lancamentos,
            'pagination' => [
                'pagina' => $pagina,
                'por_pagina' => $por_pagina,
                'total' => $total,
                'total_paginas' => ceil($total / $por_pagina)
            ]
        ];
    }
    
    /**
     * Constrói filtros SQL baseado nos parâmetros
     */
    private function construirFiltros($params) {
        $where_despesas = '';
        $where_receitas = '';
        $bind_params = [];
        
        // Filtro por período personalizado (sempre usar dataInicio e dataFim)
        if (!empty($params['dataInicio']) && !empty($params['dataFim'])) {
            $where_despesas .= " AND d.data_compra BETWEEN :data_inicio1 AND :data_fim1";
            $where_receitas .= " AND r.data_prevista_recebimento BETWEEN :data_inicio2 AND :data_fim2";
            $bind_params[':data_inicio1'] = $params['dataInicio'];
            $bind_params[':data_fim1'] = $params['dataFim'];
            $bind_params[':data_inicio2'] = $params['dataInicio'];
            $bind_params[':data_fim2'] = $params['dataFim'];
        }
        
        // Filtro por tipo
        if (!empty($params['tipo'])) {
            if ($params['tipo'] === 'receita') {
                $where_despesas .= " AND 1 = 0"; // Excluir despesas
            } elseif ($params['tipo'] === 'despesa') {
                $where_receitas .= " AND 1 = 0"; // Excluir receitas
            }
        }
        
        // Filtro por status
        if (!empty($params['status'])) {
            $hoje = date('Y-m-d');
            switch ($params['status']) {
                case 'pago':
                    $where_despesas .= " AND d.data_pagamento IS NOT NULL";
                    $where_receitas .= " AND r.data_recebimento IS NOT NULL";
                    break;
                case 'pendente':
                    $where_despesas .= " AND d.data_pagamento IS NULL";
                    $where_receitas .= " AND r.data_recebimento IS NULL";
                    break;
                case 'atrasado':
                    $where_despesas .= " AND d.data_pagamento IS NULL AND d.data_compra < :hoje1";
                    $where_receitas .= " AND r.data_recebimento IS NULL AND r.data_prevista_recebimento < :hoje2";
                    $bind_params[':hoje1'] = $hoje;
                    $bind_params[':hoje2'] = $hoje;
                    break;
                case 'hoje':
                    $where_despesas .= " AND d.data_pagamento IS NULL AND d.data_compra = :hoje3";
                    $where_receitas .= " AND r.data_recebimento IS NULL AND r.data_prevista_recebimento = :hoje4";
                    $bind_params[':hoje3'] = $hoje;
                    $bind_params[':hoje4'] = $hoje;
                    break;
            }
        }
        
        // Filtro por busca
        if (!empty($params['busca'])) {
            $busca = '%' . $params['busca'] . '%';
            $where_despesas .= " AND (fo.nome LIKE :busca1 OR d.observacoes LIKE :busca1)";
            $where_receitas .= " AND (r.origem_receita LIKE :busca2 OR r.observacoes LIKE :busca2)";
            $bind_params[':busca1'] = $busca;
            $bind_params[':busca2'] = $busca;
        }
        
        return [
            'where_despesas' => $where_despesas,
            'where_receitas' => $where_receitas,
            'bind_params' => $bind_params
        ];
    }
    
    /**
     * Conta o total de registros para paginação
     */
    private function contarTotal($params) {
        $filtros = $this->construirFiltros($params);
        $where_despesas = $filtros['where_despesas'];
        $where_receitas = $filtros['where_receitas'];
        $bind_params = $filtros['bind_params'];
        
        $sql = "
            SELECT COUNT(*) as total FROM (
                SELECT d.id FROM despesas d 
                LEFT JOIN fornecedores fo ON d.onde_comprou = fo.id
                WHERE d.usuario_id = :usuario_id1 $where_despesas
                UNION ALL
                SELECT r.id FROM receitas r 
                WHERE r.usuario_id = :usuario_id2 $where_receitas
            ) as combined
        ";
        
        $bind_params[':usuario_id1'] = $this->usuario_id;
        $bind_params[':usuario_id2'] = $this->usuario_id;
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($bind_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return intval($stmt->fetchColumn());
    }
    
    /**
     * Calcula o status do lançamento
     */
    private function calcularStatus($data_prevista, $data_real, $hoje) {
        if (!empty($data_real)) {
            return 'pago';
        }
        
        if ($data_prevista < $hoje) {
            return 'atrasado';
        } elseif ($data_prevista === $hoje) {
            return 'hoje';
        } else {
            return 'pendente';
        }
    }
    
    /**
     * Quita um lançamento (marca como pago/recebido)
     */
    public function quitar($params) {
        $this->setUsuario($params['usuario_id'] ?? null);
        
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        $tipo = $params['tipo'] ?? '';
        
        if (!$id || !in_array($tipo, ['despesa', 'receita'])) {
            throw new Exception('Parâmetros inválidos', 400);
        }
        
        // Verificar se o lançamento pertence ao usuário
        if ($tipo === 'despesa') {
            $stmt = $this->pdo->prepare("SELECT id FROM despesas WHERE id = ? AND usuario_id = ? AND data_pagamento IS NULL");
            $update_sql = "UPDATE despesas SET data_pagamento = CURDATE() WHERE id = ? AND usuario_id = ?";
        } else {
            $stmt = $this->pdo->prepare("SELECT id FROM receitas WHERE id = ? AND usuario_id = ? AND data_recebimento IS NULL");
            $update_sql = "UPDATE receitas SET data_recebimento = CURDATE() WHERE id = ? AND usuario_id = ?";
        }
        
        $stmt->execute([$id, $this->usuario_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Lançamento não encontrado ou já quitado', 404);
        }
        
        // Atualizar o registro
        $stmt = $this->pdo->prepare($update_sql);
        $stmt->execute([$id, $this->usuario_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Erro ao quitar lançamento', 500);
        }
        
        return [
            'success' => true,
            'message' => ucfirst($tipo) . ' quitada com sucesso!'
        ];
    }
    
    /**
     * Obtém detalhes de um lançamento específico
     */
    public function obterDetalhes($params) {
        $this->setUsuario($params['usuario_id'] ?? null);
        
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        $tipo = $params['tipo'] ?? '';
        
        if (!$id || !in_array($tipo, ['despesa', 'receita'])) {
            throw new Exception('Parâmetros inválidos', 400);
        }
        
        if ($tipo === 'despesa') {
            $sql = "
                SELECT 
                    d.*,
                    f.nome as familiar_nome,
                    c.nome as categoria_nome,
                    fo.nome as fornecedor_nome
                FROM despesas d
                LEFT JOIN familiares f ON d.quem_comprou = f.id
                LEFT JOIN categorias c ON d.categoria_id = c.id
                LEFT JOIN fornecedores fo ON d.onde_comprou = fo.id
                WHERE d.id = ? AND d.usuario_id = ?
            ";
        } else {
            $sql = "
                SELECT 
                    r.*,
                    f.nome as familiar_nome,
                    c.nome as categoria_nome
                FROM receitas r
                LEFT JOIN familiares f ON r.quem_recebeu = f.id
                LEFT JOIN categorias c ON r.categoria_id = c.id
                WHERE r.id = ? AND r.usuario_id = ?
            ";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $this->usuario_id]);
        $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lancamento) {
            throw new Exception('Lançamento não encontrado', 404);
        }
        
        $lancamento['tipo'] = $tipo;
        $lancamento['valor'] = floatval($lancamento['valor']);
        
        return [
            'success' => true,
            'data' => $lancamento
        ];
    }
    
    /**
     * Exclui um lançamento
     */
    public function excluir($params) {
        $this->setUsuario($params['usuario_id'] ?? null);
        
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT);
        $tipo = $params['tipo'] ?? '';
        
        if (!$id || !in_array($tipo, ['despesa', 'receita'])) {
            throw new Exception('Parâmetros inválidos', 400);
        }
        
        // Verificar se o lançamento pertence ao usuário
        if ($tipo === 'despesa') {
            $stmt = $this->pdo->prepare("SELECT id FROM despesas WHERE id = ? AND usuario_id = ?");
            $delete_sql = "DELETE FROM despesas WHERE id = ? AND usuario_id = ?";
        } else {
            $stmt = $this->pdo->prepare("SELECT id FROM receitas WHERE id = ? AND usuario_id = ?");
            $delete_sql = "DELETE FROM receitas WHERE id = ? AND usuario_id = ?";
        }
        
        $stmt->execute([$id, $this->usuario_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Lançamento não encontrado', 404);
        }
        
        // Excluir o registro
        $stmt = $this->pdo->prepare($delete_sql);
        $stmt->execute([$id, $this->usuario_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Erro ao excluir lançamento', 500);
        }
        
        return [
            'success' => true,
            'message' => ucfirst($tipo) . ' excluída com sucesso!'
        ];
    }
    
    /**
     * Obtém resumo/estatísticas dos lançamentos
     */
    public function obterResumo($params) {
        $this->setUsuario($params['usuario_id'] ?? null);
        
        $filtros = $this->construirFiltros($params);
        $where_despesas = $filtros['where_despesas'];
        $where_receitas = $filtros['where_receitas'];
        $bind_params = $filtros['bind_params'];
        
        // Totais gerais
        $sql_totais = "
            SELECT 
                COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END), 0) as total_receitas,
                COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END), 0) as total_despesas,
                COUNT(CASE WHEN tipo = 'receita' THEN 1 END) as qtd_receitas,
                COUNT(CASE WHEN tipo = 'despesa' THEN 1 END) as qtd_despesas
            FROM (
                SELECT 'receita' as tipo, r.valor
                FROM receitas r
                WHERE r.usuario_id = :usuario_id1 $where_receitas
                UNION ALL
                SELECT 'despesa' as tipo, d.valor
                FROM despesas d
                WHERE d.usuario_id = :usuario_id2 $where_despesas
            ) as combined
        ";
        
        $bind_params[':usuario_id1'] = $this->usuario_id;
        $bind_params[':usuario_id2'] = $this->usuario_id;
        
        $stmt = $this->pdo->prepare($sql_totais);
        foreach ($bind_params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totais = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Converter valores para float
        foreach (['total_receitas', 'total_despesas'] as $campo) {
            $totais[$campo] = floatval($totais[$campo]);
        }
        
        $totais['saldo'] = $totais['total_receitas'] - $totais['total_despesas'];
        
        return [
            'success' => true,
            'data' => $totais
        ];
    }
}

// Processar requisição
try {
    $api = new LancamentosAPI($pdo);
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';
            
            switch ($action) {
                case 'list':
                    echo json_encode($api->listar($_GET));
                    break;
                    
                case 'detalhes':
                    echo json_encode($api->obterDetalhes($_GET));
                    break;
                    
                case 'resumo':
                    echo json_encode($api->obterResumo($_GET));
                    break;
                    
                default:
                    throw new Exception('Ação não reconhecida', 400);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? '';
            
            switch ($action) {
                case 'quitar':
                    echo json_encode($api->quitar($data));
                    break;
                    
                default:
                    throw new Exception('Ação não reconhecida', 400);
            }
            break;
            
        case 'DELETE':
            $action = $_GET['action'] ?? 'excluir';
            
            switch ($action) {
                case 'excluir':
                    echo json_encode($api->excluir($_GET));
                    break;
                    
                default:
                    throw new Exception('Ação não reconhecida', 400);
            }
            break;
            
        default:
            throw new Exception('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $code
    ]);
}
?>

