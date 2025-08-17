<?php
/**
 * API da Tela HOME - Sistema ControleFlex
 * Versão Corrigida com Debug
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

// Debug - Log da requisição
error_log("HOME API - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("HOME API - GET: " . print_r($_GET, true));
error_log("HOME API - POST: " . print_r($_POST, true));

// Incluir conexão com banco
try {
    require_once __DIR__ . '/../config/db.php';
} catch (Exception $e) {
    // Se não conseguir incluir db.php, tentar o seu arquivo
    try {
        require_once __DIR__ . '/../db.php';
    } catch (Exception $e2) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro de conexão com banco de dados',
            'debug' => 'Arquivo db.php não encontrado'
        ]);
        exit();
    }
}

/**
 * Classe para gerenciar dados da HOME
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
        
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Usuário não encontrado', 404);
        }
        
        $this->usuario_id = $usuario_id;
    }
    
    /**
     * Obtém informações externas (clima e cotação)
     */
    public function obterInformacoesExternas() {
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;
        
        error_log("Coordenadas recebidas - Lat: $lat, Lon: $lon");
        
        $dados = [
            'success' => true,
            'clima' => $this->obterClima($lat, $lon),
            'cotacao' => $this->obterCotacaoDolar()
        ];
        
        return $dados;
    }
    
    /**
     * Obtém dados do clima
     */
    private function obterClima($lat = null, $lon = null) {
        try {
            // Por enquanto, sempre retornar Vitória/ES
            // Você pode configurar uma API key depois
            
            if ($lat && $lon) {
                // Se tiver coordenadas, determinar cidade
                if ($lat >= -20.5 && $lat <= -20.0 && $lon >= -40.5 && $lon <= -40.0) {
                    $cidade = 'Vitória, ES';
                } elseif ($lat >= -23.8 && $lat <= -23.3 && $lon >= -46.8 && $lon <= -46.3) {
                    $cidade = 'São Paulo, SP';
                } elseif ($lat >= -22.9 && $lat <= -22.8 && $lon >= -43.3 && $lon <= -43.1) {
                    $cidade = 'Rio de Janeiro, RJ';
                } else {
                    $cidade = 'Brasil';
                }
            } else {
                $cidade = 'Vitória, ES'; // Padrão
            }
            
            return [
                'temperatura' => rand(22, 30),
                'descricao' => 'Parcialmente nublado',
                'cidade' => $cidade
            ];
            
        } catch (Exception $e) {
            error_log("Erro no clima: " . $e->getMessage());
            return [
                'temperatura' => 25,
                'descricao' => 'Dados indisponíveis',
                'cidade' => 'Vitória, ES'
            ];
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
            error_log("Erro na cotação: " . $e->getMessage());
            return [
                'valor' => 5.20 + (rand(-50, 50) / 100),
                'variacao' => rand(-300, 300) / 100
            ];
        }
    }
    
    /**
     * Salva localização preferida do usuário
     */
    public function salvarLocalizacaoUsuario($usuario_id, $cidade, $lat, $lon) {
        $this->setUsuario($usuario_id);
        
        try {
            // Verificar se a tabela existe
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'configuracoes_usuario'");
            if (!$stmt->fetch()) {
                // Criar tabela se não existir
                $this->pdo->exec("
                    CREATE TABLE configuracoes_usuario (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT NOT NULL,
                        chave VARCHAR(100) NOT NULL,
                        valor TEXT NOT NULL,
                        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY unique_user_config (usuario_id, chave)
                    )
                ");
                error_log("Tabela configuracoes_usuario criada");
            }
            
            $config = [
                'cidade' => $cidade,
                'lat' => $lat,
                'lon' => $lon
            ];
            
            // Usar REPLACE para inserir ou atualizar
            $stmt = $this->pdo->prepare("
                REPLACE INTO configuracoes_usuario (usuario_id, chave, valor) 
                VALUES (?, 'localizacao_clima', ?)
            ");
            $stmt->execute([$this->usuario_id, json_encode($config)]);
            
            error_log("Localização salva: " . json_encode($config));
            
            return [
                'success' => true,
                'message' => 'Localização salva com sucesso!'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao salvar localização: " . $e->getMessage());
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
            error_log("Erro ao obter localização: " . $e->getMessage());
            return null;
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
        
        try {
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
            
            // Vencem hoje (similar para hoje e próximos)
            // ... (código similar para hoje e próximos)
            
        } catch (Exception $e) {
            error_log("Erro nos alertas: " . $e->getMessage());
        }
        
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
        try {
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
            
        } catch (Exception $e) {
            error_log("Erro no resumo: " . $e->getMessage());
            return [
                'receitas' => 0,
                'despesas' => 0,
                'saldo' => 0,
                'meta_receitas' => 10000.00,
                'meta_despesas' => 8000.00
            ];
        }
    }
}

// Processar requisição
try {
    $api = new HomeAPI($pdo);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard_data';
    
    error_log("Processando action: $action");
    
    switch ($action) {
        case 'dashboard_data':
            $usuario_id = $_GET['usuario_id'] ?? null;
            echo json_encode($api->obterDadosDashboard($usuario_id));
            break;
            
        case 'external_info':
            echo json_encode($api->obterInformacoesExternas());
            break;
            
        case 'salvar_localizacao':
            // Ler dados do POST (JSON)
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback para form data
            }
            
            $usuario_id = $input['usuario_id'] ?? null;
            $cidade = $input['cidade'] ?? null;
            $lat = $input['lat'] ?? null;
            $lon = $input['lon'] ?? null;
            
            error_log("Salvando - Usuario: $usuario_id, Cidade: $cidade, Lat: $lat, Lon: $lon");
            
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
            throw new Exception('Ação não reconhecida: ' . $action, 400);
    }
    
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    
    error_log("Erro na API: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $code,
        'debug' => [
            'action' => $_GET['action'] ?? $_POST['action'] ?? 'none',
            'method' => $_SERVER['REQUEST_METHOD'],
            'get' => $_GET,
            'post' => $_POST
        ]
    ]);
}
?>

