<?php
// /api/despesas.php (Endpoint corrigido para novos nomes de colunas)

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
            $sortBy = filter_input(INPUT_GET, 'sortBy', FILTER_SANITIZE_STRING) ?: 'data_compra';
            $sortOrder = filter_input(INPUT_GET, 'sortOrder', FILTER_SANITIZE_STRING) ?: 'DESC';

            // Lista de colunas permitidas para ordenação
            $allowedSortColumns = ['quem_comprou_nome', 'onde_comprou_nome', 'categoria_nome', 'valor', 'data_compra', 'data_pagamento'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'data_compra';
            }
            
            if (strtoupper($sortOrder) !== 'ASC' && strtoupper($sortOrder) !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Query corrigida com os nomes das colunas
            $sql = "SELECT 
                        d.id, d.valor, d.data_compra, d.data_pagamento, d.observacoes, 
                        d.recorrente, d.parcelas, d.grupo_recorrencia_id, d.forma_pagamento,
                        d.quem_comprou as quem_comprou_id, f.nome as quem_comprou_nome,
                        d.onde_comprou as onde_comprou_id, forn.nome as onde_comprou_nome,
                        d.categoria_id, cat.nome as categoria_nome
                    FROM despesas d
                    LEFT JOIN familiares f ON d.quem_comprou = f.id
                    LEFT JOIN fornecedores forn ON d.onde_comprou = forn.id
                    LEFT JOIN categorias cat ON d.categoria_id = cat.id
                    WHERE d.usuario_id = :uid";
            
            $params = [':uid' => $usuario_id];

            // Filtro por data (usando data_compra)
            if ($inicio && $fim) {
                $sql .= " AND d.data_compra BETWEEN :inicio AND :fim";
                $params[':inicio'] = $inicio;
                $params[':fim'] = $fim;
            }

            // Adiciona a ordenação e o limite na query
            $sql .= " ORDER BY $sortBy $sortOrder, d.id DESC";
            $sql .= " LIMIT :limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => &$val) {
                if ($key !== ':limit') {
                    $stmt->bindValue($key, $val);
                }
            }
            
            $stmt->execute();
            $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($despesas);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            // Validação dos campos obrigatórios
            if (empty($data['usuario_id']) || empty($data['quem_comprou']) || empty($data['onde_comprou']) || 
                empty($data['categoria_id']) || !isset($data['valor']) || empty($data['data_compra'])) {
                http_response_code(400 );
                echo json_encode(['erro' => 'Campos obrigatórios não foram preenchidos.']);
                exit;
            }

            if ($id) {
                // UPDATE - corrigido para incluir data_pagamento
                $sql = "UPDATE despesas SET 
                            quem_comprou = :qc, 
                            onde_comprou = :oc, 
                            categoria_id = :cid, 
                            forma_pagamento = :fp, 
                            valor = :v, 
                            data_compra = :dc,
                            data_pagamento = :dp,
                            observacoes = :obs 
                        WHERE id = :id AND usuario_id = :uid";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':qc' => $data['quem_comprou'], 
                    ':oc' => $data['onde_comprou'], 
                    ':cid' => $data['categoria_id'],
                    ':fp' => $data['forma_pagamento'], 
                    ':v' => $data['valor'], 
                    ':dc' => $data['data_compra'],
                    ':dp' => !empty($data['data_pagamento']) ? $data['data_pagamento'] : null,
                    ':obs' => $data['observacoes'], 
                    ':id' => $id, 
                    ':uid' => $data['usuario_id']
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Despesa atualizada com sucesso!']);

            } else {
                // INSERT - corrigido para incluir data_pagamento
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

                    // SQL corrigido com data_pagamento
                    $sql = "INSERT INTO despesas (
                                usuario_id, quem_comprou, onde_comprou, categoria_id, forma_pagamento, valor, 
                                data_compra, data_pagamento, recorrente, parcelas, frequencia, observacoes, grupo_recorrencia_id
                            ) VALUES (
                                :uid, :qc, :oc, :cid, :fp, :v, :dc, :dp, :rec, :parc, :freq, :obs, :grid
                            )";
                    
                    $stmt = $pdo->prepare($sql);
                    $dataCompraInicial = new DateTime($data['data_compra']);

                    for ($i = 0; $i < $totalParcelas; $i++) {
                        $dataParcela = clone $dataCompraInicial;
                        if ($i > 0) {
                            $dataParcela->modify("+$i $intervalo");
                        }

                        $stmt->execute([
                            ':uid' => $data['usuario_id'],
                            ':qc' => $data['quem_comprou'],
                            ':oc' => $data['onde_comprou'],
                            ':cid' => $data['categoria_id'],
                            ':fp' => $data['forma_pagamento'],
                            ':v' => $data['valor'],
                            ':dc' => $dataParcela->format('Y-m-d'),
                            ':dp' => !empty($data['data_pagamento']) ? $data['data_pagamento'] : null,
                            ':rec' => $isRecorrente ? 1 : 0,
                            ':parc' => $totalParcelas,
                            ':freq' => $frequencia,
                            ':obs' => $data['observacoes'],
                            ':grid' => $grupoRecorrenciaId
                        ]);
                    }

                    $pdo->commit();
                    http_response_code(201 );
                    echo json_encode(['sucesso' => true, 'mensagem' => "$totalParcelas despesa(s) salva(s) com sucesso!"]);

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
                echo json_encode(['erro' => 'ID da despesa é obrigatório.']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                // Query usando data_compra
                $stmtInfo = $pdo->prepare("SELECT grupo_recorrencia_id, data_compra FROM despesas WHERE id = :id");
                $stmtInfo->execute([':id' => $id]);
                $despesa = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if (!$despesa) {
                    throw new Exception('Despesa não encontrada.', 404);
                }

                $rowCount = 0;
                if ($escopo === 'esta_e_futuras' && $despesa['grupo_recorrencia_id']) {
                    // Query corrigida para usar data_compra
                    $stmtDelete = $pdo->prepare("DELETE FROM despesas WHERE grupo_recorrencia_id = :grid AND data_compra >= :dc");
                    $stmtDelete->execute([
                        ':grid' => $despesa['grupo_recorrencia_id'],
                        ':dc' => $despesa['data_compra']
                    ]);
                    $rowCount = $stmtDelete->rowCount();
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM despesas WHERE id = :id");
                    $stmtDelete->execute([':id' => $id]);
                    $rowCount = $stmtDelete->rowCount();
                }

                $pdo->commit();
                echo json_encode(['sucesso' => true, 'mensagem' => "$rowCount despesa(s) excluída(s) com sucesso!"]);

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
    error_log("Erro na API de despesas: " . $e->getMessage());
    $httpCode = is_int($e->getCode( )) && $e->getCode() >= 400 ? $e->getCode() : 500;
    http_response_code($httpCode );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>

