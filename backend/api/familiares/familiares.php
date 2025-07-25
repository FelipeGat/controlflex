<?php
// backend/api/familiares.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/db.php';

function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

function getRendas($pdo, $fid) {
    $stmt = $pdo->prepare("SELECT id, nome, valor FROM rendas WHERE familiar_id = :fid");
    $stmt->execute(['fid' => $fid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFamiliaresBancos($pdo, $fid) {
    $stmt = $pdo->prepare("SELECT fb.id, fb.banco_id, b.nome AS banco_nome, b.icone,
            fb.limiteCartao, fb.limiteCheque, fb.alertaCartao, fb.alertaCheque
        FROM familiares_bancos fb
        JOIN bancos b ON b.id = fb.banco_id
        WHERE fb.familiar_id = :fid");
    $stmt->execute(['fid' => $fid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFamiliares($pdo, $usuario_id) {
    $sql = "SELECT f.id, f.nome, f.foto,
            IFNULL(SUM(r.valor),0) AS total_renda,
            IFNULL(SUM(fb.limiteCartao),0) AS total_limite_cartao,
            IFNULL(SUM(fb.limiteCheque),0) AS total_limite_cheque
            FROM familiares f
            LEFT JOIN rendas r ON r.familiar_id = f.id
            LEFT JOIN familiares_bancos fb ON fb.familiar_id = f.id
            WHERE f.usuario_id = :uid
            GROUP BY f.id
            ORDER BY f.nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uid' => $usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$method = $_SERVER['REQUEST_METHOD'];
$data = $method === 'GET' ? $_GET : (getJsonInput() ?? $_POST);
$uid = isset($data['usuario_id']) ? intval($data['usuario_id']) : null;

if (!$uid) {
    http_response_code(400);
    echo json_encode(['erro' => 'usuario_id obrigatório']);
    exit;
}

try {
    switch ($method) {
        case 'GET':
            if (!empty($data['familiar_id'])) {
                $fid = intval($data['familiar_id']);
                $stmt = $pdo->prepare("SELECT id, nome, foto FROM familiares WHERE id = :id AND usuario_id = :uid");
                $stmt->execute(['id' => $fid, 'uid' => $uid]);
                $familiar = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$familiar) {
                    http_response_code(404);
                    echo json_encode(['erro' => 'Familiar não encontrado']);
                    exit;
                }

                $familiar['rendas'] = getRendas($pdo, $fid);
                $familiar['bancos'] = getFamiliaresBancos($pdo, $fid);

                echo json_encode(['familiar' => $familiar]);
                exit;
            }

            echo json_encode(getFamiliares($pdo, $uid));
            exit;

        case 'POST':
            $nome = $_POST['nome'] ?? '';
            if (!$nome) {
                http_response_code(400);
                echo json_encode(['erro' => 'Nome obrigatório']);
                exit;
            }

            $foto = null;
            if (!empty($_FILES['foto']['tmp_name'])) {
                $dir = __DIR__ . '/../uploads/familiares/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $novoNome = uniqid() . ".$ext";
                move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $novoNome);
                $foto = "uploads/familiares/$novoNome";
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO familiares (nome, usuario_id, foto) VALUES (:nome, :uid, :foto)");
            $stmt->execute(['nome' => $nome, 'uid' => $uid, 'foto' => $foto]);
            $fid = $pdo->lastInsertId();

            if (!empty($_POST['rendas'])) {
                $rendas = json_decode($_POST['rendas'], true);
                $stmtRenda = $pdo->prepare("INSERT INTO rendas (familiar_id, nome, valor) VALUES (:fid, :nome, :valor)");
                foreach ($rendas as $r) {
                    $stmtRenda->execute(['fid' => $fid, 'nome' => $r['nome'], 'valor' => floatval($r['valor'])]);
                }
            }

            if (!empty($_POST['bancos'])) {
                $bancos = json_decode($_POST['bancos'], true);
                $stmtBanco = $pdo->prepare("INSERT INTO familiares_bancos (familiar_id, banco_id, limiteCartao, limiteCheque, alertaCartao, alertaCheque)
                    VALUES (:fid, :banco_id, :limiteCartao, :limiteCheque, :alertaCartao, :alertaCheque)");
                foreach ($bancos as $b) {
                    $stmtBanco->execute([
                        'fid' => $fid,
                        'banco_id' => $b['banco_id'],
                        'limiteCartao' => floatval($b['limiteCartao'] ?? 0),
                        'limiteCheque' => floatval($b['limiteCheque'] ?? 0),
                        'alertaCartao' => intval($b['alertaCartao'] ?? 0),
                        'alertaCheque' => intval($b['alertaCheque'] ?? 0),
                    ]);
                }
            }

            $pdo->commit();

            $stmt = $pdo->prepare("SELECT id, nome, foto FROM familiares WHERE id = :id");
            $stmt->execute(['id' => $fid]);
            $familiar = $stmt->fetch(PDO::FETCH_ASSOC);
            $familiar['rendas'] = getRendas($pdo, $fid);
            $familiar['bancos'] = getFamiliaresBancos($pdo, $fid);

            echo json_encode(['sucesso' => true, 'familiar' => $familiar]);
            exit;

        case 'PUT':
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do familiar obrigatório']);
                exit;
            }

            $fid = intval($data['id']);
            $stmt = $pdo->prepare("SELECT id FROM familiares WHERE id = :id AND usuario_id = :uid");
            $stmt->execute(['id' => $fid, 'uid' => $uid]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['erro' => 'Familiar não pertence ao usuário']);
                exit;
            }

            $pdo->beginTransaction();

            $pdo->prepare("UPDATE familiares SET nome = :nome WHERE id = :id")
                ->execute(['nome' => $data['nome'], 'id' => $fid]);

            $pdo->prepare("DELETE FROM rendas WHERE familiar_id = :fid")->execute(['fid' => $fid]);
            $rendas = json_decode($data['rendas'], true);
            $stmtR = $pdo->prepare("INSERT INTO rendas (familiar_id, nome, valor) VALUES (:fid, :nome, :valor)");
            foreach ($rendas as $r) {
                $stmtR->execute(['fid' => $fid, 'nome' => $r['nome'], 'valor' => floatval($r['valor'])]);
            }

            $pdo->prepare("DELETE FROM familiares_bancos WHERE familiar_id = :fid")->execute(['fid' => $fid]);
            $bancos = json_decode($data['bancos'], true);
            $stmtB = $pdo->prepare("INSERT INTO familiares_bancos (familiar_id, banco_id, limiteCartao, limiteCheque, alertaCartao, alertaCheque)
                VALUES (:fid, :banco_id, :limiteCartao, :limiteCheque, :alertaCartao, :alertaCheque)");
            foreach ($bancos as $b) {
                $stmtB->execute([
                    'fid' => $fid,
                    'banco_id' => $b['banco_id'],
                    'limiteCartao' => floatval($b['limiteCartao'] ?? 0),
                    'limiteCheque' => floatval($b['limiteCheque'] ?? 0),
                    'alertaCartao' => intval($b['alertaCartao'] ?? 0),
                    'alertaCheque' => intval($b['alertaCheque'] ?? 0),
                ]);
            }

            $pdo->commit();

            $stmt = $pdo->prepare("SELECT id, nome, foto FROM familiares WHERE id = :id");
            $stmt->execute(['id' => $fid]);
            $familiar = $stmt->fetch(PDO::FETCH_ASSOC);
            $familiar['rendas'] = getRendas($pdo, $fid);
            $familiar['bancos'] = getFamiliaresBancos($pdo, $fid);

            echo json_encode(['sucesso' => true, 'familiar' => $familiar]);
            exit;

        case 'DELETE':
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID do familiar obrigatório']);
                exit;
            }

            $fid = intval($data['id']);
            $stmt = $pdo->prepare("SELECT id FROM familiares WHERE id = :id AND usuario_id = :uid");
            $stmt->execute(['id' => $fid, 'uid' => $uid]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['erro' => 'Familiar não pertence ao usuário']);
                exit;
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM rendas WHERE familiar_id = :fid")->execute(['fid' => $fid]);
            $pdo->prepare("DELETE FROM familiares_bancos WHERE familiar_id = :fid")->execute(['fid' => $fid]);
            $pdo->prepare("DELETE FROM familiares WHERE id = :id")->execute(['id' => $fid]);
            $pdo->commit();

            echo json_encode(['sucesso' => true]);
            exit;

        case 'OPTIONS':
            http_response_code(200);
            exit;

        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
            exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
