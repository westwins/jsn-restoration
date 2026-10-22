<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/healthz', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/readyz', name: 'readiness', methods: ['GET'])]
    public function readiness(Connection $connection): JsonResponse
    {
        $connection->executeQuery('SELECT 1')->fetchOne();

        return new JsonResponse(['status' => 'ready']);
    }
}
