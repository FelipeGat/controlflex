<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$conn = new mysqli('localhost', 'control', '100%Control!!', 'inves783_controleflex');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro conexão']);
    exit;
}

// Parâmetro opcional para filtrar por banco
$bancoId = isset($_GET['banco_id']) ? intval($_GET['banco_id']) : null;
$whereBanco = $bancoId ? "WHERE banco_id = $bancoId" : "";

// Consulta para gráfico mensal
$sql = "
  SELECT DATE_FORMAT(data, '%Y-%m') AS mes,
         SUM(investido) AS total_investido,
         SUM(lucro) AS total_lucro
  FROM investimentos
  $whereBanco
  GROUP BY mes
  ORDER BY mes ASC
";

$res = $conn->query($sql);
$labels = [];
$investidos = [];
$lucros = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['mes'];
        $investidos[] = (float)$row['total_investido'];
        $lucros[] = (float)$row['total_lucro'];
    }
}

// Consulta para totais gerais
$resTotais = $conn->query("
  SELECT 
    SUM(investido) AS total_investido,
    SUM(lucro) AS total_lucro,
    SUM(investido + lucro) AS total_acumulado
  FROM investimentos
  $whereBanco
");
$totaisRow = $resTotais->fetch_assoc();
$totalInvestido = (float)($totaisRow['total_investido'] ?? 0);
$totalLucro = (float)($totaisRow['total_lucro'] ?? 0);
$totalAcumulado = (float)($totaisRow['total_acumulado'] ?? 0);

// URL base dos ícones
$baseImgUrl = 'http://localhost/ControleFlex/assets/img/';

// Consulta para listar bancos ativos em investimento
$resBancos = $conn->query("SELECT id, nome, icone FROM bancos WHERE investimento = 1");
$bancosInvestimento = [];

if ($resBancos) {
    while ($row = $resBancos->fetch_assoc()) {
        $iconeUrl = !empty($row['icone']) ? $baseImgUrl . $row['icone'] : null;
        $bancosInvestimento[] = [
            'id' => (int)$row['id'],
            'nome' => $row['nome'],
            'icone' => $iconeUrl,
        ];
    }
}

echo json_encode([
    'labels' => $labels,
    'investidos' => $investidos,
    'lucros' => $lucros,
    'totais' => [
        'investido' => $totalInvestido,
        'lucro' => $totalLucro,
        'acumulado' => $totalAcumulado,
        'bancosDetalhados' => $bancosInvestimento
    ],
]);

$conn->close();
