<?php
// 1. CARREGAR DEPENDÊNCIAS E VARIÁVEIS DE AMBIENTE
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 2. VERIFICAR VARIÁVEIS DE AMBIENTE
$requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($requiredVars as $var) {
    if (!isset($_ENV[$var])) {
        // Em vez de enviar uma resposta JSON, lançamos uma exceção.
        // O script principal que chamou este arquivo decidirá como lidar com o erro.
        throw new \Exception("Erro de configuração: A variável de ambiente '$var' não está definida.");
    }
}

// 3. CRIAR A CONEXÃO PDO
$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // A variável $pdo é criada e estará disponível para qualquer script que inclua este arquivo.
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Lança a exceção para que o script principal possa tratá-la.
    // Isso evita que a senha do banco seja exposta em um stack trace.
    throw new \PDOException("Erro de conexão com o banco de dados.", (int)$e->getCode());
}
