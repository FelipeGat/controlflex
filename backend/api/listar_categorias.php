<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli('localhost', 'root', '', 'controleflex');
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, nome FROM categorias ORDER BY nome ASC";
$result = $conn->query($sql);

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

echo json_encode($categorias);
$conn->close();
?>
