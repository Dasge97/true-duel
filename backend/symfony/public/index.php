<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

use App\Api\MvpApiKernel;

$kernel = new MvpApiKernel();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'GET' && $path === '/health') {
    $dbOk = false;
    try {
        $dsn = getenv('DATABASE_URL') ?: 'pgsql:host=db;port=5432;dbname=true_duel';
        $dbUser = getenv('DATABASE_USER') ?: 'true_duel';
        $dbPassword = getenv('DATABASE_PASSWORD') ?: 'true_duel';
        $pdo = new PDO($dsn, $dbUser, $dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->query('SELECT 1');
        $dbOk = true;
    } catch (Throwable) {
        $dbOk = false;
    }

    http_response_code($dbOk ? 200 : 503);
    header('Content-Type: application/json');
    echo json_encode([
        'service' => 'true-duel-api',
        'status' => $dbOk ? 'ok' : 'degraded',
        'db' => $dbOk ? 'up' : 'down',
        'timestamp' => gmdate('c'),
    ]);
    exit;
}

$rawBody = file_get_contents('php://input');
$body = [];
if (is_string($rawBody) && trim($rawBody) !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

$headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
$result = $kernel->handle($method, $path, $headers, $body);

http_response_code((int) ($result['status'] ?? 500));
header('Content-Type: application/json');
echo json_encode($result['data'] ?? ['error' => ['code' => 'INTERNAL', 'message' => 'Invalid response']]);
