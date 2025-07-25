<?php
// (Lembre-se de remover as linhas de depuração ini_set e error_reporting se ainda estiverem lá)

// Headers CORS e JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['usuario_id'])) {
    echo json_encode([]);
    exit;
}

$usuario_id = intval($_GET['usuario_id']);
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;

// Monta query base com correções
$sql = "SELECT 
            r.id, 
            r.quem_recebeu AS quem_recebeu_id,
            f.nome AS quem_recebeu_nome,
            r.forma_recebimento AS forma_recebimento_id,
            b.nome AS forma_recebimento_nome,
            r.categoria_id AS categoria_id, -- CORRIGIDO: de categoria_id para categoria_id
            c.nome AS categoria_nome,
            r.valor, 
            r.data_recebimento, 
            r.recorrente, 
            -- r.parcelas, -- REMOVIDO: coluna não existe na tabela
            r.observacoes
        FROM receitas r
        LEFT JOIN categorias c ON r.categoria_id = c.id -- CORRIGIDO: de categoria_id para categoria_id
        LEFT JOIN bancos b ON r.forma_recebimento = b.id
        LEFT JOIN familiares f ON r.quem_recebeu = f.id
        WHERE r.usuario_id = :usuario_id";

if ($inicio && $fim) {
    $sql .= " AND r.data_recebimento BETWEEN :inicio AND :fim";
}

$sql .= " ORDER BY r.data_recebimento DESC";

try {
    $stmt = $pdo->prepare($sql);

    $params = [':usuario_id' => $usuario_id];
    if ($inicio && $fim) {
        $params[':inicio'] = $inicio;
        $params[':fim'] = $fim;
    }

    $stmt->execute($params);
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajusta tipos numéricos
    foreach ($receitas as &$r) {
        $r['valor'] = (float)$r['valor'];
        $r['recorrente'] = (int)$r['recorrente'];
        // $r['parcelas'] = (int)$r['parcelas']; -- REMOVIDO
    }

    echo json_encode($receitas);

} catch (PDOException $e) {
    http_response_code(500 );
    echo json_encode(['erro' => 'Erro ao buscar receitas: ' . $e->getMessage()]);
}
