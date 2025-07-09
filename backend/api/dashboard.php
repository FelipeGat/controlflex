<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$conn = new mysqli('localhost', 'root', '', 'controleflex');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro conexão']);
    exit;
}

$ano = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$res1 = $conn->query("SELECT SUM(valor) as total FROM receitas WHERE YEAR(data_recebimento) = $ano AND data_recebimento <= CURDATE()");
$realizadaReceita = $res1->fetch_assoc()['total'] ?? 0;

$res2 = $conn->query("SELECT SUM(valor) as total FROM despesas WHERE YEAR(data_compra) = $ano AND data_compra <= CURDATE()");
$realizadaDespesa = $res2->fetch_assoc()['total'] ?? 0;

$saldo = $realizadaReceita - $realizadaDespesa;

$res3 = $conn->query("SELECT SUM(valor) as total FROM receitas WHERE YEAR(data_recebimento) = $ano AND MONTH(data_recebimento) = MONTH(CURDATE()) AND data_recebimento > CURDATE()");
$aReceber = $res3->fetch_assoc()['total'] ?? 0;

$res4 = $conn->query("SELECT SUM(valor) as total FROM despesas WHERE YEAR(data_compra) = $ano AND MONTH(data_compra) = MONTH(CURDATE()) AND data_compra > CURDATE()");
$aPagar = $res4->fetch_assoc()['total'] ?? 0;

$diferencaMes = $aReceber - $aPagar;

$res5 = $conn->query("SELECT SUM(valor) as total FROM receitas WHERE YEAR(data_recebimento) = $ano AND data_recebimento > CURDATE()");
$receitaPrevista = $res5->fetch_assoc()['total'] ?? 0;

$res6 = $conn->query("SELECT SUM(valor) as total FROM despesas WHERE YEAR(data_compra) = $ano AND data_compra > CURDATE()");
$despesaPrevista = $res6->fetch_assoc()['total'] ?? 0;

$saldoPrevisto = $receitaPrevista - $despesaPrevista;

$sqlChart = "
  SELECT 
    DATE_FORMAT(mes_ref, '%b/%Y') AS mes,
    COALESCE(r.total_receita, 0) AS total_receita,
    COALESCE(d.total_despesa, 0) AS total_despesa
  FROM (
    SELECT DISTINCT DATE_FORMAT(data_recebimento, '%Y-%m-01') as mes_ref FROM receitas WHERE YEAR(data_recebimento) = $ano
    UNION
    SELECT DISTINCT DATE_FORMAT(data_compra, '%Y-%m-01') as mes_ref FROM despesas WHERE YEAR(data_compra) = $ano
  ) as base
  LEFT JOIN (
    SELECT DATE_FORMAT(data_recebimento, '%Y-%m-01') AS mes, SUM(valor) AS total_receita FROM receitas WHERE YEAR(data_recebimento) = $ano GROUP BY mes
  ) r ON base.mes_ref = r.mes
  LEFT JOIN (
    SELECT DATE_FORMAT(data_compra, '%Y-%m-01') AS mes, SUM(valor) AS total_despesa FROM despesas WHERE YEAR(data_compra) = $ano GROUP BY mes
  ) d ON base.mes_ref = d.mes
  ORDER BY base.mes_ref
";

$resChart = $conn->query($sqlChart);
$months = [];
$receitasMonth = [];
$despesasMonth = [];

while ($r = $resChart->fetch_assoc()) {
    $months[] = $r['mes'];
    $receitasMonth[] = (float)$r['total_receita'];
    $despesasMonth[] = (float)$r['total_despesa'];
}

$resumoAtual = [
    'receitas' => $realizadaReceita,
    'despesas' => $realizadaDespesa,
    'saldo' => $saldo,
    'aReceber' => $aReceber,
    'aPagar' => $aPagar,
    'atrasado' => $diferencaMes,
    'receitaPrevista' => $receitaPrevista,
    'despesaPrevista' => $despesaPrevista,
    'saldoPrevisto' => $saldoPrevisto
];

$resumoSeguinte = [
    'receitas' => $realizadaReceita,
    'despesas' => $realizadaDespesa,
    'saldo' => $saldo,
    'aReceber' => $aReceber,
    'aPagar' => $aPagar,
    'atrasado' => $diferencaMes,
    'receitaPrevista' => $receitaPrevista,
    'despesaPrevista' => $despesaPrevista,
    'saldoPrevisto' => $saldoPrevisto
];

echo json_encode([
    'mesAtual' => $resumoAtual,
    'mesSeguinte' => $resumoSeguinte,

    'realizadaReceita' => round($realizadaReceita, 2),
    'realizadaDespesa' => round($realizadaDespesa, 2),
    'saldo' => round($saldo, 2),

    'aReceber' => round($aReceber, 2),
    'aPagar' => round($aPagar, 2),
    'diferencaMes' => round($diferencaMes, 2),

    'receitaPrevista' => round($receitaPrevista, 2),
    'despesaPrevista' => round($despesaPrevista, 2),
    'saldoPrevisto' => round($saldoPrevisto, 2),

    'annualChart' => [
        'labels' => $months,
        'receitas' => $receitasMonth,
        'despesas' => $despesasMonth,
    ],
]);

$conn->close();
