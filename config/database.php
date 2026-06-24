<?php
/**
 * Configuração do Banco de Dados MySQL
 * 
 * Plataforma de Jogos Multiplayer
 * Lê as credenciais do arquivo .env na raiz do projeto
 * 
 * Uso:
 *   require_once __DIR__ . '/config/database.php';
 *   $pdo = getDBConnection();
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Carrega variáveis do .env (se existir)
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER']);
}

/**
 * Retorna uma conexão PDO com o MySQL
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $name = $_ENV['DB_NAME'] ?? 'plataforma_jogos';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $name
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    return $pdo;
}