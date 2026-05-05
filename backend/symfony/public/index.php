<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Combat/Domain/MvpChampionRoster.php';
require_once __DIR__ . '/../src/Api/MvpApiKernel.php';

use App\Api\MvpApiKernel;

$kernel = new MvpApiKernel();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
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
