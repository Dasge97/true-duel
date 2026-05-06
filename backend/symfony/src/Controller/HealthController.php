<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class HealthController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(): JsonResponse
    {
        $dbOk = false;

        try {
            $this->connection->fetchOne('SELECT 1');
            $dbOk = true;
        } catch (\Throwable) {
            $dbOk = false;
        }

        return new JsonResponse([
            'service' => 'true-duel-api',
            'status' => $dbOk ? 'ok' : 'degraded',
            'db' => $dbOk ? 'up' : 'down',
            'timestamp' => gmdate('c'),
        ], $dbOk ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
