<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Api/ApiResponseFormatter.php';

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

use App\Api\ApiResponseFormatter;

function fail(string $message): void
{
    fwrite(STDERR, "[FAIL] $message\n");
    exit(1);
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fail($message);
    }
}

$formatter = new ApiResponseFormatter();

$normalized = $formatter->normalize([
    'status' => 409,
    'data' => [
        'error' => [
            'code' => 'STATE_VERSION_CONFLICT',
            'message' => 'Client state is outdated.',
        ],
        'authoritativeState' => [
            'serverStateVersion' => 3,
        ],
    ],
], 'trace-test-1234');

assertTrue(($normalized['status'] ?? 0) === 409, 'normalized response must preserve status');
assertTrue(($normalized['data']['error']['code'] ?? '') === 'STATE_VERSION_CONFLICT', 'normalized error code must be preserved');
assertTrue(($normalized['data']['error']['message'] ?? '') === 'Client state is outdated.', 'normalized error message must be preserved');
assertTrue(($normalized['data']['traceId'] ?? '') === 'trace-test-1234', 'normalized response must expose traceId');
assertTrue(
    (($normalized['data']['error']['details']['authoritativeState']['serverStateVersion'] ?? 0) === 3),
    'normalized response must move extra fields into error.details'
);

fwrite(STDOUT, "[PASS] error_contract_smoke\n");
