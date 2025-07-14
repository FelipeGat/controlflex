<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
require __DIR__ . '/../config/db.php';

// Função para ler PUT, DELETE com body raw
function getInputData() {
    $input = file_get_contents('php://input');
    parse_str($input, $data);
    return $data;
}

// Função para retornar familiares com somas de rendas e limites
function getFamiliares($pdo, $usuario_id) {
    $sql = "SELECT f.id, f.nome, f.foto,
            IFNULL(SUM(r.valor),0) AS total_renda,
            IFNULL(SUM(fb.limiteCartao),0) AS total_limite_cartao,
            IFNULL(SUM(fb.limiteCheque),0) AS total_limite_cheque
            FROM familiares f
            LEFT JOIN rendas r ON r.familiar_id = f.id
            LEFT JOIN familiares_bancos fb ON fb.familiar_id = f.id
            WHERE f.usuario_id = :usuario_id
            GROUP BY f.id
            ORDER BY f.nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario_id' => $usuario_id]);
    $familiares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $familiares;
}

// Função para buscar rendas de um familiar
function getRendas($pdo, $familiar_id) {
    $stmt = $pdo->prepare("SELECT id, nome, valor FROM rendas WHERE familiar_id = :fid");
    $stmt->execute(['fid' => $familiar_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar bancos associados a um familiar com limites e alertas
function getFamiliaresBancos($pdo, $familiar_id) {
    $sql = "SELECT fb.id, fb.banco_id, b.nome AS banco_nome, b.icone,
            fb.limiteCartao, fb.limiteCheque, fb.alertaCartao, fb.alertaCheque
            FROM familiares_bancos fb
            JOIN bancos b ON b.id = fb.banco_id
            WHERE fb.familiar_id = :fid";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['fid' => $familiar_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtem o usuario_id do GET ou POST (ou header, session, conforme seu sistema)
$usuario_id = null;
if ($method === 'GET') {
    $usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;
} else {
    $input = $method === 'PUT' || $method === 'DELETE' ? getInputData() : $_POST;
    $usuario_id = isset($input['usuario_id']) ? intval($input['usuario_id']) : null;
}

if (!$usuario_id) {
    http_response_code(400);
    echo json_encode(['erro' => 'usuario_id obrigatório']);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            // Se veio familiar_id, traz detalhes e dados relacionados
            if (isset($_GET['familiar_id'])) {
                $familiar_id = intval($_GET['familiar_id']);

                // Busca familiar básico
                $stmt = $pdo->prepare("SELECT id, nome, foto FROM familiares WHERE id = :id AND usuario_id = :uid");
                $stmt->execute(['id' => $familiar_id, 'uid' => $usuario_id]);
                $familiar = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$familiar) {
                    http_response_code(404);
                    echo json_encode(['erro' => 'Familiar não encontrado']);
                    exit;
                }

                // Busca rendas
                $familiar['rendas'] = getRendas($pdo, $familiar_id);

                // Busca bancos associados
                $familiar['bancos'] = getFamiliaresBancos($pdo, $familiar_id);

                echo json_encode(['familiar' => $familiar]);
                exit;
            }

            // Se não, retorna lista simples com somas
            $familiares = getFamiliares($pdo, $usuario_id);
            echo json_encode($familiares);
            break;

        case 'POST':
            // Criar novo familiar com foto opcional
            // Recebe: nome, usuario_id, foto (arquivo), rendas (array JSON), bancos (array JSON)

            $nome = $_POST['nome'] ?? '';
            if (!$nome) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome é obrigatório']);
                exit;
            }

            // Salvar foto se houver upload
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/familiares/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $novo_nome = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $novo_nome);
                $foto_path = $upload_dir . $novo_nome;
            }

            $pdo->beginTransaction();

            // Inserir familiar
            $stmt = $pdo->prepare("INSERT INTO familiares (nome, usuario_id, foto) VALUES (:nome, :uid, :foto)");
            $stmt->execute([
                'nome' => $nome,
                'uid' => $usuario_id,
                'foto' => $foto_path
            ]);
            $familiar_id = $pdo->lastInsertId();

            // Inserir rendas (recebidas via JSON em campo 'rendas')
            if (isset($_POST['rendas'])) {
                $rendas = json_decode($_POST['rendas'], true);
                if (is_array($rendas)) {
                    $stmt_renda = $pdo->prepare("INSERT INTO rendas (familiar_id, nome, valor) VALUES (:fid, :nome, :valor)");
                    foreach ($rendas as $renda) {
                        if (isset($renda['nome'], $renda['valor'])) {
                            $stmt_renda->execute([
                                'fid' => $familiar_id,
                                'nome' => $renda['nome'],
                                'valor' => floatval($renda['valor'])
                            ]);
                        }
                    }
                }
            }

            // Inserir bancos associados (campo 'bancos' JSON)
            if (isset($_POST['bancos'])) {
                $bancos = json_decode($_POST['bancos'], true);
                if (is_array($bancos)) {
                    $stmt_banco = $pdo->prepare("INSERT INTO familiares_bancos (familiar_id, banco_id, limiteCartao, limiteCheque, alertaCartao, alertaCheque)
                        VALUES (:fid, :bid, :limCartao, :limCheque, :alertaCartao, :alertaCheque)");
                    foreach ($bancos as $banco) {
                        if (isset($banco['banco_id'])) {
                            $stmt_banco->execute([
                                'fid' => $familiar_id,
                                'bid' => intval($banco['banco_id']),
                                'limCartao' => floatval($banco['limiteCartao'] ?? 0),
                                'limCheque' => floatval($banco['limiteCheque'] ?? 0),
                                'alertaCartao' => intval($banco['alertaCartao'] ?? 0),
                                'alertaCheque' => intval($banco['alertaCheque'] ?? 0)
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();

            // Retornar o familiar completo (como GET /?familiar_id=...)
            $stmt = $pdo->prepare("SELECT id, nome, foto FROM familiares WHERE id = :id");
            $stmt->execute(['id' => $familiar_id]);
            $familiar = $stmt->fetch(PDO::FETCH_ASSOC);
            $familiar['rendas'] = getRendas($pdo, $familiar_id);
            $familiar['bancos'] = getFamiliaresBancos($pdo, $familiar_id);

            echo json_encode(['sucesso' => true, 'familiar' => $familiar]);
            break;

        case 'PUT':
            // Atualizar familiar, rendas e bancos
            $data = getInputData();

            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do familiar é obrigatório para atualização']);
                exit;
            }

            $familiar_id = intval($data['id']);

            // Validar que o familiar pertence ao usuário
            $stmt = $pdo->prepare("SELECT id FROM familiares WHERE id = :id AND usuario_id = :uid");
            $stmt->execute(['id' => $familiar_id, 'uid' => $usuario_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['erro' => 'Familiar não encontrado ou acesso negado']);
                exit;
            }

            $nome = $data['nome'] ?? null;
            if (!$nome) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome é obrigatório']);
                exit;
            }

            $pdo->beginTransaction();

            // Atualiza nome
            $stmt = $pdo->prepare("UPDATE familiares SET nome = :nome WHERE id = :id");
            $stmt->execute(['nome' => $nome, 'id' => $familiar_id]);

            // Atualizar rendas (recebidas via JSON string)
            if (isset($data['rendas'])) {
                $rendas = json_decode($data['rendas'], true);
                if (is_array($rendas)) {
                    // Delete todas rendas antigas
                    $pdo->prepare("DELETE FROM rendas WHERE familiar_id = :fid")->execute(['fid' => $familiar_id]);

                    // Inserir novas rendas
                    $stmt_renda = $pdo->prepare("INSERT INTO rendas (familiar_id, nome, valor) VALUES (:fid, :nome, :valor)");
                    foreach ($rendas as $renda) {
                        if (isset($renda['nome'], $renda['valor'])) {
                            $stmt_renda->execute([
                                'fid' => $familiar_id,
                                'nome' => $renda['nome'],
                                'valor' => floatval($renda['valor'])
                            ]);
                        }
                    }
                }
            }

            // Atualizar bancos familiares
            if (isset($data['bancos'])) {
                $bancos = json_decode($data['bancos'], true);
                if (is_array($bancos)) {
                    // Delete associações antigas
                    $pdo->prepare("DELETE FROM familiares_bancos WHERE familiar_id = :fid")->execute(['fid' => $familiar_id]);

                    // Inserir novas associações
                    $stmt_banco = $pdo->prepare("INSERT INTO familiares_bancos (familiar_id, banco_id, limiteCartao, limiteCheque, alertaCartao, alertaCheque)
                        VALUES (:fid, :bid, :limCartao, :limCheque, :alertaCartao, :alertaCheque)");
                    foreach ($bancos as $banco) {
                        if (isset($banco['banco_id'])) {
                            $stmt_banco->execute([
                                'fid' => $familiar_id,
                                'bid' => intval($banco['banco_id']),
                                'limCartao' => floatval($banco['limiteCartao'] ?? 0),
                                'limCheque' => floatval($banco['limiteCheque'] ?? 0),
                                'alertaCartao' => intval($banco['alertaCartao'] ?? 0),
                                'alertaCheque' => intval($banco['alertaCheque'] ?? 0)
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();

            // Retorna dados atualizados
            $stmt = $pdo->prepare("SELECT id, nome, foto FROM familiares WHERE id = :id");
            $stmt->execute(['id' => $familiar_id]);
            $familiar = $stmt->fetch(PDO::FETCH_ASSOC);
            $familiar['rendas'] = getRendas($pdo, $familiar_id);
            $familiar['bancos'] = getFamiliaresBancos($pdo, $familiar_id);

            echo json_encode(['sucesso' => true, 'familiar' => $familiar]);
            break;

        case 'DELETE':
            // Excluir familiar e dados relacionados
            $data = getInputData();
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do familiar é obrigatório para exclusão']);
                exit;
            }
            $familiar_id = intval($data['id']);

            // Verifica usuário
            $stmt = $pdo->prepare("SELECT id FROM familiares WHERE id = :id AND usuario_id = :uid");
            $stmt->execute(['id' => $familiar_id, 'uid' => $usuario_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['erro' => 'Familiar não encontrado ou acesso negado']);
                exit;
            }

            $pdo->beginTransaction();

            // Deletar rendas, familiares_bancos (ON DELETE CASCADE pode ajudar, mas deletamos explicitamente)
            $pdo->prepare("DELETE FROM rendas WHERE familiar_id = :fid")->execute(['fid' => $familiar_id]);
            $pdo->prepare("DELETE FROM familiares_bancos WHERE familiar_id = :fid")->execute(['fid' => $familiar_id]);
            $pdo->prepare("DELETE FROM familiares WHERE id = :id")->execute(['id' => $familiar_id]);

            $pdo->commit();

            echo json_encode(['sucesso' => true]);
            break;

        case 'OPTIONS':
            // Preflight CORS
            http_response_code(200);
            break;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
