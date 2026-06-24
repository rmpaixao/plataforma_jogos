<?php
/**
 * CONFIGURAÇÃO DO BANCO DE DADOS MySQL
 * 
 * Lê as credenciais do arquivo .env na raiz do projeto.
 * Se o .env não existir, usa valores padrão (localhost/root/sem senha).
 * 
 * NÃO precisa de Composer nem phpdotenv - parser .env manual em PHP puro.
 * 
 * Uso:
 *   require_once __DIR__ . '/../config/database.php';
 *   $pdo = getDBConnection();
 */

// Carrega variáveis do .env manualmente (sem dependências)
function loadEnv(string $caminho): array
{
    $vars = [];

    if (!file_exists($caminho)) {
        return $vars;
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // Pula comentários e linhas vazias
        if ($linha === '' || str_starts_with($linha, '#')) {
            continue;
        }

        // Divide no primeiro "="
        $pos = strpos($linha, '=');
        if ($pos === false) {
            continue;
        }

        $chave = trim(substr($linha, 0, $pos));
        $valor = trim(substr($linha, $pos + 1));

        // Remove aspas se houver
        if ((str_starts_with($valor, '"') && str_ends_with($valor, '"'))
            || (str_starts_with($valor, "'") && str_ends_with($valor, "'"))) {
            $valor = substr($valor, 1, -1);
        }

        $vars[$chave] = $valor;
    }

    return $vars;
}

// Carrega variáveis de ambiente do .env
$envVars = loadEnv(__DIR__ . '/../.env');

// Define as constantes com fallback para valores padrão
define('DB_HOST', $envVars['DB_HOST'] ?? 'localhost');
define('DB_PORT', $envVars['DB_PORT'] ?? '3306');
define('DB_NAME', $envVars['DB_NAME'] ?? 'plataforma_jogos');
define('DB_USER', $envVars['DB_USER'] ?? 'root');
define('DB_PASS', $envVars['DB_PASS'] ?? '');

/**
 * Retorna uma conexão PDO com MySQL (singleton)
 */
function getDBConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'erro'  => 'Erro de conexão com o banco de dados.',
                'debug' => $e->getMessage()
            ]));
        }
    }

    return $pdo;
}