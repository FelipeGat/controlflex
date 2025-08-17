<?php
// /api/receitas.php (Endpoint corrigido para novos nomes de colunas)

// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
$frontend_url = "http://localhost:3000"; 
header("Access-Control-Allow-Origin: " . $frontend_url );
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200 );
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// 2. INICIALIZAÇÃO E DEPENDÊNCIAS
// ===================================================
require_once __DIR__ . '/../config/db.php'; 

// 3. LÓGICA PRINCIPAL DA API
// ===================================================
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
            if (!$usuario_id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID do usuário é obrigatório.']);
                exit;
            }

            // Parâmetros de filtro, ordenação e limite
            $inicio = filter_input(INPUT_GET, 'inicio', FILTER_SANITIZE_STRING);
            $fim = filter_input(INPUT_GET, 'fim', FILTER_SANITIZE_STRING);
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10;
            $sortBy = filter_input(INPUT_GET, 'sortBy', FILTER_SANITIZE_STRING) ?: 'data_prevista_recebimento';
            $sortOrder = filter_input(INPUT_GET, 'sortOrder', FILTER_SANITIZE_STRING) ?: 'DESC';

            // Lista de colunas permitidas para ordenação
            $allowedSortColumns = ['quem_recebeu_nome', 'categoria_nome', 'valor', 'data_prevista_recebimento', 'data_recebimento'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'data_prevista_recebimento';
            }
            
            if (strtoupper($sortOrder) !== 'ASC' && strtoupper($sortOrder) !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Query corrigida com os novos nomes das colunas
            $sql = "SELECT 
                        r.id, r.valor, r.data_prevista_recebimento, r.data_recebimento, r.observacoes, 
                        r.recorrente, r.parcelas, r.grupo_recorrencia_id,
                        r.quem_recebeu as quem_recebeu_id, f.nome as quem_recebeu_nome,
                        r.categoria_id, cat.nome as categoria_nome,
                        r.forma_recebimento as forma_recebimento_id, b.nome as forma_recebimento_nome
                    FROM receitas r
                    LEFT JOIN familiares f ON r.quem_recebeu = f.id
                    LEFT JOIN categorias cat ON r.categoria_id = cat.id
                    LEFT JOIN bancos b ON r.forma_recebimento = b.id
                    WHERE r.usuario_id = :uid";
            
            $params = [':uid' => $usuario_id];

            // Filtro por data (usando data_prevista_recebimento)
            if ($inicio && $fim) {
                $sql .= " AND r.data_prevista_recebimento BETWEEN :inicio AND :fim";
                $params[':inicio'] = $inicio;
                $params[':fim'] = $fim;
            }

            // Adiciona a ordenação e o limite na query
            $sql .= " ORDER BY $sortBy $sortOrder, r.id DESC";
            $sql .= " LIMIT :limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => &$val) {
                if ($key !== ':limit') {
                    $stmt->bindValue($key, $val);
                }
            }
            
            $stmt->execute();
            $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($receitas);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            // Validação dos campos obrigatórios (corrigido para data_prevista_recebimento)
            if (empty($data['usuario_id']) || empty($data['quem_recebeu']) || empty($data['categoria_id']) || 
                !isset($data['valor']) || empty($data['data_prevista_recebimento'])) {
                http_response_code(400 );
                echo json_encode(['erro' => 'Campos obrigatórios não foram preenchidos.']);
                exit;
            }

            if ($id) {
                // UPDATE - corrigido para usar os novos nomes das colunas
                $sql = "UPDATE receitas SET 
                            quem_recebeu = :qr, 
                            categoria_id = :cid, 
                            forma_recebimento = :fr, 
                            valor = :v, 
                            data_prevista_recebimento = :dpr,
                            data_recebimento = :dr,
                            observacoes = :obs 
                        WHERE id = :id AND usuario_id = :uid";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':qr' => $data['quem_recebeu'], 
                    ':cid' => $data['categoria_id'], 
                    ':fr' => $data['forma_recebimento'],
                    ':v' => $data['valor'], 
                    ':dpr' => $data['data_prevista_recebimento'],
                    ':dr' => !empty($data['data_recebimento']) ? $data['data_recebimento'] : null,
                    ':obs' => $data['observacoes'], 
                    ':id' => $id, 
                    ':uid' => $data['usuario_id']
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Receita atualizada com sucesso!']);

            } else {
                // INSERT - corrigido para usar os novos nomes das colunas
                $pdo->beginTransaction();
                try {
                    $isRecorrente = !empty($data['recorrente']);
                    $parcelasRecebidas = isset($data['parcelas']) ? (int)$data['parcelas'] : 1;
                    
                    $frequencia = $data['frequencia'] ?? 'mensal';
                    $intervalo = '';

                    switch ($frequencia) {
                        case 'diaria':    $intervalo = 'day'; break;
                        case 'semanal':   $intervalo = 'week'; break;
                        case 'quinzenal': $intervalo = '2 week'; break;
                        case 'mensal':    $intervalo = 'month'; break;
                        case 'trimestral':$intervalo = '3 month'; break;
                        case 'semestral': $intervalo = '6 month'; break;
                        case 'anual':     $intervalo = 'year'; break;
                        default:          $intervalo = 'month'; break;
                    }

                    $totalParcelas = 1;
                    if ($isRecorrente) {
                        if ($parcelasRecebidas === 0) {
                            $totalParcelas = 60;
                        } else {
                            $totalParcelas = $parcelasRecebidas;
                        }
                    }
                    
                    $grupoRecorrenciaId = ($totalParcelas > 1) ? uniqid('rec_') : null;

                    // SQL corrigido com os novos nomes das colunas
                    $sql = "INSERT INTO receitas (
                                usuario_id, quem_recebeu, categoria_id, forma_recebimento, valor, 
                                data_prevista_recebimento, data_recebimento, recorrente, parcelas, 
                                frequencia, observacoes, grupo_recorrencia_id
                            ) VALUES (
                                :uid, :qr, :cid, :fr, :v, :dpr, :dr, :rec, :parc, :freq, :obs, :grid
                            )";
                    
                    $stmt = $pdo->prepare($sql);
                    $dataRecebimentoInicial = new DateTime($data['data_prevista_recebimento']);

                    for ($i = 0; $i < $totalParcelas; $i++) {
                        $dataParcela = clone $dataRecebimentoInicial;
                        if ($i > 0) {
                            $dataParcela->modify("+$i $intervalo");
                        }

                        $stmt->execute([
                            ':uid' => $data['usuario_id'],
                            ':qr' => $data['quem_recebeu'],
                            ':cid' => $data['categoria_id'],
                            ':fr' => $data['forma_recebimento'],
                            ':v' => $data['valor'],
                            ':dpr' => $dataParcela->format('Y-m-d'),
                            ':dr' => !empty($data['data_recebimento']) ? $data['data_recebimento'] : null,
                            ':rec' => $isRecorrente ? 1 : 0,
                            ':parc' => $totalParcelas,
                            ':freq' => $frequencia,
                            ':obs' => $data['observacoes'],
                            ':grid' => $grupoRecorrenciaId
                        ]);
                    }

                    $pdo->commit();
                    http_response_code(201 );
                    echo json_encode(['sucesso' => true, 'mensagem' => "$totalParcelas receita(s) salva(s) com sucesso!"]);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            break;

        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $escopo = filter_input(INPUT_GET, 'escopo', FILTER_SANITIZE_STRING) ?? 'apenas_esta';

            if (!$id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID da receita é obrigatório.']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                // Query corrigida para usar data_prevista_recebimento
                $stmtInfo = $pdo->prepare("SELECT grupo_recorrencia_id, data_prevista_recebimento FROM receitas WHERE id = :id");
                $stmtInfo->execute([':id' => $id]);
                $receita = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if (!$receita) {
                    throw new Exception('Receita não encontrada.', 404);
                }

                $rowCount = 0;
                if ($escopo === 'esta_e_futuras' && $receita['grupo_recorrencia_id']) {
                    // Query corrigida para usar data_prevista_recebimento
                    $stmtDelete = $pdo->prepare("DELETE FROM receitas WHERE grupo_recorrencia_id = :grid AND data_prevista_recebimento >= :dpr");
                    $stmtDelete->execute([
                        ':grid' => $receita['grupo_recorrencia_id'],
                        ':dpr' => $receita['data_prevista_recebimento']
                    ]);
                    $rowCount = $stmtDelete->rowCount();
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM receitas WHERE id = :id");
                    $stmtDelete->execute([':id' => $id]);
                    $rowCount = $stmtDelete->rowCount();
                }

                $pdo->commit();
                echo json_encode(['sucesso' => true, 'mensagem' => "$rowCount receita(s) excluída(s) com sucesso!"]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            http_response_code(405 );
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    error_log("Erro na API de receitas: " . $e->getMessage());
    $httpCode = is_int($e->getCode( )) && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>

