<?php
// /api/despesas.php (Lógica de Parcelas Corrigida)

// 1. CABEÇALHOS DE SEGURANÇA E CORS
// ===================================================
$frontend_url = "http://localhost:3000"; 
header("Access-Control-Allow-Origin: " . $frontend_url  );
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200  );
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
            // A lógica de GET (listar) permanece a mesma.
            $usuario_id = filter_input(INPUT_GET, 'usuario_id', FILTER_VALIDATE_INT);
            if (!$usuario_id) {
                http_response_code(400  );
                echo json_encode(['erro' => 'ID do usuário é obrigatório.']);
                exit;
            }

            $inicio = filter_input(INPUT_GET, 'inicio', FILTER_SANITIZE_STRING);
            $fim = filter_input(INPUT_GET, 'fim', FILTER_SANITIZE_STRING);

            $sql = "SELECT 
                        d.id, d.valor, d.data_compra, d.observacoes, d.recorrente, d.parcelas, d.grupo_recorrencia_id,
                        d.quem_comprou as quem_comprou_id, f.nome as quem_comprou_nome,
                        d.onde_comprou as onde_comprou_id, forn.nome as onde_comprou_nome,
                        d.categoria_id, cat.nome as categoria_nome
                    FROM despesas d
                    LEFT JOIN familiares f ON d.quem_comprou = f.id
                    LEFT JOIN fornecedores forn ON d.onde_comprou = forn.id
                    LEFT JOIN categorias cat ON d.categoria_id = cat.id
                    WHERE d.usuario_id = :uid";
            
            $params = ['uid' => $usuario_id];

            if ($inicio && $fim) {
                $sql .= " AND d.data_compra BETWEEN :inicio AND :fim";
                $params[':inicio'] = $inicio;
                $params[':fim'] = $fim;
            }

            $sql .= " ORDER BY d.data_compra DESC, d.id DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($despesas);
            break;

        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            $id = $data['id'] ?? null;

            if (empty($data['usuario_id']) || empty($data['quem_comprou']) || empty($data['onde_comprou']) || empty($data['categoria_id']) || !isset($data['valor']) || empty($data['data_compra'])) {
                http_response_code(400  );
                echo json_encode(['erro' => 'Campos obrigatórios não foram preenchidos.']);
                exit;
            }

            if ($id) { // ATUALIZAÇÃO (mantida simples)
                $sql = "UPDATE despesas SET quem_comprou = :qc, onde_comprou = :oc, categoria_id = :cid, forma_pagamento = :fp, valor = :v, data_compra = :dc, observacoes = :obs WHERE id = :id AND usuario_id = :uid";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':qc' => $data['quem_comprou'], ':oc' => $data['onde_comprou'], ':cid' => $data['categoria_id'],
                    ':fp' => $data['forma_pagamento'], ':v' => $data['valor'], ':dc' => $data['data_compra'],
                    ':obs' => $data['observacoes'], ':id' => $id, ':uid' => $data['usuario_id']
                ]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Despesa atualizada.']);

            } else { // CRIAÇÃO (com lógica de recorrência corrigida)
                
                $pdo->beginTransaction();

                try {
                    // --- INÍCIO DA CORREÇÃO ---
                    $isRecorrente = !empty($data['recorrente']);
                    // Se for recorrente, usa o número de parcelas enviado, senão, o total é 1.
                    $totalParcelas = $isRecorrente && isset($data['parcelas']) && (int)$data['parcelas'] > 0 ? (int)$data['parcelas'] : 1;
                    // --- FIM DA CORREÇÃO ---
                    
                    // Gera um ID de grupo apenas se houver mais de uma parcela.
                    $grupoRecorrenciaId = ($totalParcelas > 1) ? uniqid('rec_') : null;

                    $sql = "INSERT INTO despesas (usuario_id, quem_comprou, onde_comprou, categoria_id, forma_pagamento, valor, data_compra, recorrente, parcelas, observacoes, grupo_recorrencia_id) 
                            VALUES (:uid, :qc, :oc, :cid, :fp, :v, :dc, :rec, :parc, :obs, :grid)";
                    
                    $stmt = $pdo->prepare($sql);

                    $dataCompraInicial = new DateTime($data['data_compra']);

                    for ($i = 0; $i < $totalParcelas; $i++) {
                        $dataParcela = clone $dataCompraInicial;
                        if ($i > 0) {
                            // Adiciona um mês para cada parcela subsequente
                            $dataParcela->modify("+$i months");
                        }

                        $stmt->execute([
                            ':uid' => $data['usuario_id'],
                            ':qc' => $data['quem_comprou'],
                            ':oc' => $data['onde_comprou'],
                            ':cid' => $data['categoria_id'],
                            ':fp' => $data['forma_pagamento'],
                            ':v' => $data['valor'],
                            ':dc' => $dataParcela->format('Y-m-d'),
                            ':rec' => $isRecorrente ? 1 : 0,
                            // Armazena o número total de parcelas em cada registro
                            ':parc' => $totalParcelas, 
                            ':obs' => $data['observacoes'],
                            ':grid' => $grupoRecorrenciaId
                        ]);
                    }

                    $pdo->commit();

                    http_response_code(201  );
                    echo json_encode(['sucesso' => true, 'mensagem' => "$totalParcelas despesa(s) salva(s) com sucesso!"]);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e; // Lança a exceção para o bloco catch principal
                }
            }
            break;

        case 'DELETE':
            // --- INÍCIO DA LÓGICA DE EXCLUSÃO AVANÇADA ---
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $escopo = filter_input(INPUT_GET, 'escopo', FILTER_SANITIZE_STRING) ?? 'apenas_esta'; // Padrão é 'apenas_esta'

            if (!$id) {
                http_response_code(400 );
                echo json_encode(['erro' => 'ID da despesa é obrigatório.']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                if ($escopo === 'apenas_esta') {
                    // Exclui apenas a despesa com o ID específico
                    $stmt = $pdo->prepare("DELETE FROM despesas WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $rowCount = $stmt->rowCount();

                } elseif ($escopo === 'esta_e_futuras') {
                    // Primeiro, busca os detalhes da despesa para encontrar o grupo e a data
                    $stmt = $pdo->prepare("SELECT grupo_recorrencia_id, data_compra FROM despesas WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $despesa = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$despesa || !$despesa['grupo_recorrencia_id']) {
                        // Se não for recorrente, age como 'apenas_esta'
                        $stmtDelete = $pdo->prepare("DELETE FROM despesas WHERE id = :id");
                        $stmtDelete->execute([':id' => $id]);
                        $rowCount = $stmtDelete->rowCount();
                    } else {
                        // Exclui esta e todas as futuras do mesmo grupo
                        $stmtDelete = $pdo->prepare("DELETE FROM despesas WHERE grupo_recorrencia_id = :grid AND data_compra >= :dc");
                        $stmtDelete->execute([
                            ':grid' => $despesa['grupo_recorrencia_id'],
                            ':dc' => $despesa['data_compra']
                        ]);
                        $rowCount = $stmtDelete->rowCount();
                    }
                } else {
                    // Escopo inválido
                     $pdo->rollBack();
                    http_response_code(400 );
                    echo json_encode(['erro' => 'Escopo de exclusão inválido.']);
                    exit;
                }

                $pdo->commit();

                if ($rowCount > 0) {
                    echo json_encode(['sucesso' => true, 'mensagem' => "$rowCount despesa(s) excluída(s)."]);
                } else {
                    http_response_code(404 );
                    echo json_encode(['erro' => 'Nenhuma despesa encontrada para exclusão.']);
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e; // Lança para o catch principal
            }
            // --- FIM DA LÓGICA DE EXCLUSÃO ---
            break;

        default:
            http_response_code(405  );
            echo json_encode(['erro' => 'Método não permitido.']);
            break;
    }
} catch (\Throwable $e) {
    // Garante que erros não exponham detalhes sensíveis
    error_log("Erro na API de despesas: " . $e->getMessage());
    http_response_code(500  );
    echo json_encode(['erro' => 'Ocorreu um erro crítico no servidor.', 'detalhes' => $e->getMessage()]);
}
?>
