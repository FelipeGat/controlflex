<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'controleflex';
$username = 'root';
$password = ''; // senha do seu phpMyAdmin

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Opcional para garantir o uso do utf8mb4
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro de conexão com o banco de dados: ' . $e->getMessage()]);
    exit;
}
