<?php
/**
 * API da Tela HOME - Sistema ControleFlex
 * Versão com Geolocalização Automática
 */

// Headers CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../config/db.php';

/**
 * Classe para gerenciar dados da HOME com geolocalização
 */
class HomeAPI {
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
        
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND status = 'ativo'");
        $stmt->execute([$usuario_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Usuário não encontrado ou inativo', 404);
        }
        
        $this->usuario_id = $usuario_id;
    }
    
    /**
     * Obtém informações externas (clima e cotação)
     */
    public function obterInformacoesExternas() {
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;
        
        $dados = [
            'success' => true,
            'clima' => $this->obterClima($lat, $lon),
            'cotacao' => $this->obterCotacaoDolar()
        ];
        
        return $dados;
    }
    
    /**
     * Obtém dados do clima com geolocalização
     */
    private function obterClima($lat = null, $lon = null) {
        try {
            // Chave da API OpenWeatherMap (você precisa se cadastrar)
            $api_key = 'SUA_CHAVE_OPENWEATHER'; // Substitua pela sua chave
            
            // Se não tiver coordenadas, usar IP para detectar localização
            if (!$lat || !$lon) {
                $localizacao = $this->obterLocalizacaoPorIP();
                $lat = $localizacao['lat'];
                $lon = $localizacao['lon'];
            }
            
            // Se a chave não estiver configurada, retorna dados simulados
            if ($api_key === 'SUA_CHAVE_OPENWEATHER') {
                return [
                    'temperatura' => rand(20, 32),
                    'descricao' => 'Parcialmente nublado',
                    'cidade' => 'Vitória, ES'
                ];
            }
            
            $url = "http://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$api_key}&units=metric&lang=pt_br";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception('Erro ao obter dados do clima');
            }
            
            $data = json_decode($response, true);
            
            return [
                'temperatura' => round($data['main']['temp']),
                'descricao' => ucfirst($data['weather'][0]['description']),
                'cidade' => $data['name'] . ', ' . $data['sys']['country']
            ];
            
        } catch (Exception $e) {
            // Retorna dados simulados em caso de erro
            return [
                'temperatura' => rand(20, 32),
                'descricao' => 'Dados indisponíveis',
                'cidade' => 'Vitória, ES'
            ];
        }
    }
    
    /**
     * Obtém localização aproximada por IP
     */
    private function obterLocalizacaoPorIP() {
        try {
            // Usando API gratuita do ipapi.co
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            
            // Se for localhost, usar coordenadas de Vitória/ES
            if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
                return [
                    'lat' => -20.2976,
                    'lon' => -40.2958
                ];
            }
            
            $url = "https://ipapi.co/{$ip}/json/";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception('Erro ao obter localização por IP');
            }
            
            $data = json_decode($response, true);
            
            return [
                'lat' => $data['latitude'] ?? -20.2976,
                'lon' => $data['longitude'] ?? -40.2958
            ];
            
        } catch (Exception $e) {
            // Retorna coordenadas de Vitória/ES em caso de erro
            return [
                'lat' => -20.2976,
                'lon' => -40.2958
            ];
        }
    }
    
    /**
     * Salva localização preferida do usuário
     */
    public function salvarLocalizacaoUsuario($usuario_id, $cidade, $lat, $lon) {
        $this->setUsuario($usuario_id);
        
        try {
            // Verifica se já existe configuração para o usuário
            $stmt = $this->pdo->prepare("
                SELECT id FROM configuracoes_usuario 
                WHERE usuario_id = ? AND chave = 'localizacao_clima'
            ");
            $stmt->execute([$this->usuario_id]);
            
            $config = [
                'cidade' => $cidade,
                'lat' => $lat,
                'lon' => $lon
            ];
            
            if ($stmt->fetch()) {
                // Atualiza configuração existente
                $stmt = $this->pdo->prepare("
                    UPDATE configuracoes_usuario 
                    SET valor = ? 
                    WHERE usuario_id = ? AND chave = 'localizacao_clima'
                ");
                $stmt->execute([json_encode($config), $this->usuario_id]);
            } else {
                // Cria nova configuração
                $stmt = $this->pdo->prepare("
                    INSERT INTO configuracoes_usuario (usuario_id, chave, valor) 
                    VALUES (?, 'localizacao_clima', ?)
                ");
                $stmt->execute([$this->usuario_id, json_encode($config)]);
            }
            
            return [
                'success' => true,
                'message' => 'Localização salva com sucesso!'
            ];
            
        } catch (Exception $e) {
            throw new Exception('Erro ao salvar localização: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém localização preferida do usuário
     */
    public function obterLocalizacaoUsuario($usuario_id) {
        $this->setUsuario($usuario_id);
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT valor FROM configuracoes_usuario 
                WHERE usuario_id = ? AND chave = 'localizacao_clima'
            ");
            $stmt->execute([$this->usuario_id]);
            
            $resultado = $stmt->fetch();
            
            if ($resultado) {
                return json_decode($resultado['valor'], true);
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtém cotação do dólar
     */
    private function obterCotacaoDolar() {
        try {
            $url = 'https://economia.awesomeapi.com.br/last/USD-BRL';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception('Erro ao obter cotação');
            }
            
            $data = json_decode($response, true);
            $usd = $data['USDBRL'];
            
            return [
                'valor' => floatval($usd['bid']),
                'variacao' => floatval($usd['pctChange'])
            ];
            
        } catch (Exception $e) {
            return [
                'valor' => 5.20 + (rand(-50, 50) / 100),
                'variacao' => rand(-300, 300) / 100
            ];
        }
    }
    
    /**
     * Obtém dados do dashboard da home
     */
    public function obterDadosDashboard($usuario_id) {
        $this->setUsuario($usuario_id);
        
        $hoje = date('Y-m-d');
        $inicio_mes = date('Y-m-01');
        $fim_mes = date('Y-m-t');
        
        $alertas = $this->obterAlertasFinanceiros($hoje);
        $resumo = $this->obterResumoMensal($inicio_mes, $fim_mes);
        
        return [
            'success' => true,
            'data' => [
                'alertas' => $alertas,
                'resumo' => $resumo
            ]
        ];
    }
    
    /**
     * Obtém alertas financeiros
     */
    private function obterAlertasFinanceiros($hoje) {
        $alertas = [
            'atrasado' => ['total' => 0, 'valor_total' => 0, 'items' => []],
            'hoje' => ['total' => 0, 'valor_total' => 0, 'items' => []],
            'proximos' => ['total' => 0, 'valor_total' => 0, 'items' => []]
        ];
        
        $proximos_3_dias = date('Y-m-d', strtotime('+3 days'));
        
        // Despesas atrasadas
        $sql_despesas_atrasadas = "
            SELECT d.id, d.valor, d.data_compra as data_prevista, 
                   COALESCE(fo.nome, 'Fornecedor não informado') as descricao,
                   'despesa' as tipo
            FROM despesas d
            LEFT JOIN fornecedores fo ON d.onde_comprou = fo.id
            WHERE d.usuario_id = ? AND d.data_pagamento IS NULL AND d.data_compra < ?
            ORDER BY d.data_compra ASC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql_despesas_atrasadas);
        $stmt->execute([$this->usuario_id, $hoje]);
        $despesas_atrasadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Receitas atrasadas
        $sql_receitas_atrasadas = "
            SELECT r.id, r.valor, r.data_prevista_recebimento as data_prevista,
                   COALESCE(r.origem_receita, 'Origem não informada') as descricao,
                   'receita' as tipo
            FROM receitas r
            WHERE r.usuario_id = ? AND r.data_recebimento IS NULL AND r.data_prevista_recebimento < ?
            ORDER BY r.data_prevista_recebimento ASC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql_receitas_atrasadas);
        $stmt->execute([$this->usuario_id, $hoje]);
        $receitas_atrasadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combinar atrasados
        $atrasados = array_merge($despesas_atrasadas, $receitas_atrasadas);
        usort($atrasados, function($a, $b) {
            return strcmp($a['data_prevista'], $b['data_prevista']);
        });
        
        $alertas['atrasado']['items'] = $atrasados;
        $alertas['atrasado']['total'] = count($atrasados);
        $alertas['atrasado']['valor_total'] = array_sum(array_column($atrasados, 'valor'));
        
        // Vencem hoje
        $sql_despesas_hoje = "
            SELECT d.id, d.valor, d.data_compra as data_prevista,
                   COALESCE(fo.nome, 'Fornecedor não informado') as descricao,
                   'despesa' as tipo
            FROM despesas d
            LEFT JOIN fornecedores fo ON d.onde_comprou = fo.id
            WHERE d.usuario_id = ? AND d.data_pagamento IS NULL AND d.data_compra = ?
            ORDER BY d.valor DESC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql_despesas_hoje);
        $stmt->execute([$this->usuario_id, $hoje]);
        $despesas_hoje = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_receitas_hoje = "
            SELECT r.id, r.valor, r.data_prevista_recebimento as data_prevista,
                   COALESCE(r.origem_receita, 'Origem não informada') as descricao,
                   'receita' as tipo
            FROM receitas r
            WHERE r.usuario_id = ? AND r.data_recebimento IS NULL AND r.data_prevista_recebimento = ?
            ORDER BY r.valor DESC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql_receitas_hoje);
        $stmt->execute([$this->usuario_id, $hoje]);
        $receitas_hoje = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hoje_items = array_merge($despesas_hoje, $receitas_hoje);
        usort($hoje_items, function($a, $b) {
            return $b['valor'] <=> $a['valor'];
        });
        
        $alertas['hoje']['items'] = $hoje_items;
        $alertas['hoje']['total'] = count($hoje_items);
        $alertas['hoje']['valor_total'] = array_sum(array_column($hoje_items, 'valor'));
        
        // Próximos 3 dias
        $sql_despesas_proximos = "
            SELECT d.id, d.valor, d.data_compra as data_prevista,
                   COALESCE(fo.nome, 'Fornecedor não informado') as descricao,
                   'despesa' as tipo
            FROM despesas d
            LEFT JOIN fornecedores fo ON d.onde_comprou = fo.id
            WHERE d.usuario_id = ? AND d.data_pagamento IS NULL 
            AND d.data_compra > ? AND d.data_compra <= ?
            ORDER BY d.data_compra ASC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql_despesas_proximos);
        $stmt->execute([$this->usuario_id, $hoje, $proximos_3_dias]);
        $despesas_proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_receitas_proximos = "
            SELECT r.id, r.valor, r.data_prevista_recebimento as data_prevista,
                   COALESCE(r.origem_receita, 'Origem não informada') as descricao,
                   'receita' as tipo
            FROM receitas r
            WHERE r.usuario_id = ? AND r.data_recebimento IS NULL 
            AND r.data_prevista_recebimento > ? AND r.data_prevista_recebimento <= ?
            ORDER BY r.data_prevista_recebimento ASC
            LIMIT 10
        ";
        
        $stmt = $this->pdo->prepare($sql_receitas_proximos);
        $stmt->execute([$this->usuario_id, $hoje, $proximos_3_dias]);
        $receitas_proximos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $proximos_items = array_merge($despesas_proximos, $receitas_proximos);
        usort($proximos_items, function($a, $b) {
            return strcmp($a['data_prevista'], $b['data_prevista']);
        });
        
        $alertas['proximos']['items'] = $proximos_items;
        $alertas['proximos']['total'] = count($proximos_items);
        $alertas['proximos']['valor_total'] = array_sum(array_column($proximos_items, 'valor'));
        
        // Converter valores para float
        foreach ($alertas as &$alerta) {
            $alerta['valor_total'] = floatval($alerta['valor_total']);
            foreach ($alerta['items'] as &$item) {
                $item['valor'] = floatval($item['valor']);
            }
        }
        
        return $alertas;
    }
    
    /**
     * Obtém resumo mensal
     */
    private function obterResumoMensal($inicio_mes, $fim_mes) {
        // Receitas do mês
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total
            FROM receitas 
            WHERE usuario_id = ? AND data_prevista_recebimento BETWEEN ? AND ?
        ");
        $stmt->execute([$this->usuario_id, $inicio_mes, $fim_mes]);
        $receitas = floatval($stmt->fetchColumn());
        
        // Despesas do mês
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total
            FROM despesas 
            WHERE usuario_id = ? AND data_compra BETWEEN ? AND ?
        ");
        $stmt->execute([$this->usuario_id, $inicio_mes, $fim_mes]);
        $despesas = floatval($stmt->fetchColumn());
        
        return [
            'receitas' => $receitas,
            'despesas' => $despesas,
            'saldo' => $receitas - $despesas,
            'meta_receitas' => 10000.00,
            'meta_despesas' => 8000.00
        ];
    }
}

// Processar requisição
try {
    $api = new HomeAPI($pdo);
    $action = $_GET['action'] ?? 'dashboard_data';
    
    switch ($action) {
        case 'dashboard_data':
            $usuario_id = $_GET['usuario_id'] ?? null;
            echo json_encode($api->obterDadosDashboard($usuario_id));
            break;
            
        case 'external_info':
            echo json_encode($api->obterInformacoesExternas());
            break;
            
        case 'salvar_localizacao':
            $usuario_id = $_POST['usuario_id'] ?? null;
            $cidade = $_POST['cidade'] ?? null;
            $lat = $_POST['lat'] ?? null;
            $lon = $_POST['lon'] ?? null;
            echo json_encode($api->salvarLocalizacaoUsuario($usuario_id, $cidade, $lat, $lon));
            break;
            
        case 'obter_localizacao':
            $usuario_id = $_GET['usuario_id'] ?? null;
            $localizacao = $api->obterLocalizacaoUsuario($usuario_id);
            echo json_encode([
                'success' => true,
                'localizacao' => $localizacao
            ]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida', 400);
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

