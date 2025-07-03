<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Conexão com banco
$conn = new mysqli('localhost', 'root', '', 'controleflex');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro conexão']);
    exit;
}

// 1. Receita x Despesas por mês (últimos 6 meses)
$sql1 = "
  SELECT DATE_FORMAT(data_recebimento, '%b/%Y') AS mes,
         SUM(valor) AS total_receita
  FROM receitas
  WHERE data_recebimento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY YEAR(data_recebimento), MONTH(data_recebimento)
  UNION ALL
  SELECT DATE_FORMAT(data_compra, '%b/%Y') AS mes,
         -SUM(valor) AS total_despesa
  FROM despesas
  WHERE data_compra >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY YEAR(data_compra), MONTH(data_compra)
  ORDER BY mes;
";
$res1 = $conn->query($sql1);

$months = []; $receitasMonth = []; $despesasMonth = [];
$temp = [];
while ($r = $res1->fetch_assoc()) {
    $m = $r['mes'];
    $v = (float)$r['total_receita'] + (float)$r['total_despesa'];
    if (!isset($temp[$m])) $temp[$m] = 0;
    $temp[$m] += $v;
}
foreach ($temp as $m => $v) {
    $months[] = $m;
    $receitasMonth[] = max(0, $v);
    $despesasMonth[] = max(0, -$v);
}

// 2. Despesas por categoria
$sql2 = "
  SELECT c.nome AS categoria, SUM(d.valor) AS total
  FROM despesas d
  JOIN categorias c ON d.categoria_id = c.id
  WHERE d.data_compra >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY c.nome;
";
$res2 = $conn->query($sql2);

$catLabels = []; $catValues = []; $catColors = [];
$cores = ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF']; $i=0;
while ($r = $res2->fetch_assoc()) {
    $catLabels[] = $r['categoria'];
    $catValues[] = (float)$r['total'];
    $catColors[] = $cores[$i++ % count($cores)];
}

// 3. Investidos versus Lucro
$sql3 = "
  SELECT DATE_FORMAT(data, '%b/%Y') AS mes,
         SUM(investido) AS inv, SUM(lucro) AS lucro
  FROM investimentos
  WHERE data >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY YEAR(data), MONTH(data)
  ORDER BY MIN(data);
";
$res3 = $conn->query($sql3);

$investidos = []; $lucros = [];
while ($r = $res3->fetch_assoc()) {
    $investidos[] = (float)$r['inv'];
    $lucros[] = (float)$r['lucro'];
}

// Monta JSON de resposta
echo json_encode([
    'months' => $months,
    'receitasMonth' => $receitasMonth,
    'despesasMonth' => $despesasMonth,
    'categoryLabels' => $catLabels,
    'categoryValues' => $catValues,
    'categoryColors' => $catColors,
    'investidos' => $investidos,
    'lucros' => $lucros
]);

$conn->close();
