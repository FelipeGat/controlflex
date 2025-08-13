<?php
// Headers CORS e JSON
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=UTF-8');

// Requisições OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/db.php';

if (!isset($_GET['usuario_id'])) {
    echo json_encode([]);
    exit;
}

$usuario_id = intval($_GET['usuario_id']);
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;
$mes = $_GET['mes'] ?? null;
$ano = $_GET['ano'] ?? null;

// Se não tiver filtros de data, define mês e ano atuais como padrão
if (!$inicio && !$fim && !$mes && !$ano) {
    $mes = date('m');
    $ano = date('Y');
}

// Monta query base
// ========= A CORREÇÃO ESTÁ AQUI =========
// Adicionado `r.grupo_recorrencia_id` à lista de colunas selecionadas.
$sql = "SELECT 
            r.id, 
            r.quem_recebeu AS quem_recebeu_id,
            f.nome AS quem_recebeu_nome,
            r.forma_recebimento AS forma_recebimento_id,
            b.nome AS forma_recebimento_nome,
            r.categoria_id,
            c.nome AS categoria_nome,
            r.valor, 
            r.data_recebimento, 
            r.recorrente, 
            r.parcelas,
            r.observacoes,
            r.grupo_recorrencia_id 
        FROM receitas r
        LEFT JOIN categorias c ON r.categoria_id = c.id
        LEFT JOIN bancos b ON r.forma_recebimento = b.id
        LEFT JOIN familiares f ON r.quem_recebeu = f.id
        WHERE r.usuario_id = :usuario_id";
// ========= FIM DA CORREÇÃO =========

// Filtro por intervalo de datas (se enviado)
if ($inicio && $fim) {
    $sql .= " AND r.data_recebimento BETWEEN :inicio AND :fim";
}
// Caso contrário, filtra por mês e ano (padrão: mês atual)
elseif ($mes && $ano) {
    $sql .= " AND MONTH(r.data_recebimento) = :mes AND YEAR(r.data_recebimento) = :ano";
}

$sql .= " ORDER BY r.data_recebimento DESC, r.id DESC";

try {
    $stmt = $pdo->prepare($sql);

    $params = [':usuario_id' => $usuario_id];

    if ($inicio && $fim) {
        $params[':inicio'] = $inicio;
        $params[':fim'] = $fim;
    } elseif ($mes && $ano) {
        $params[':mes'] = intval($mes);
        $params[':ano'] = intval($ano);
    }

    $stmt->execute($params);
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajusta tipos numéricos e garante que campos existam
    foreach ($receitas as &$r) {
        $r['valor'] = (float)$r['valor'];
        $r['recorrente'] = (int)$r['recorrente'];
        $r['grupo_recorrencia_id'] = $r['grupo_recorrencia_id'] ?? null;
    }

    echo json_encode($receitas);

} catch (PDOException $e) {
    http_response_code(500 );
    echo json_encode(['erro' => 'Erro ao buscar receitas: ' . $e->getMessage()]);
}
?>
