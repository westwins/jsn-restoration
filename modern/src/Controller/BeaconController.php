<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BeaconController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $siteBaseUrl,
    ) {
    }

    #[Route('/jsn-viaf.beacon', name: 'viaf_beacon', methods: ['GET'])]
    public function __invoke(): Response
    {
        $connection = $this->entityManager->getConnection();
        $rows = $connection->executeQuery(
            'SELECT viaf_id FROM person WHERE viaf_id IS NOT NULL ORDER BY viaf_id ASC',
        )->fetchFirstColumn();
        $lastModified = $connection->fetchOne('SELECT MAX(lastmod) FROM person');
        $timestamp = $lastModified
            ? new \DateTimeImmutable((string) $lastModified, new \DateTimeZone('UTC'))
            : new \DateTimeImmutable('@0');
        $baseUrl = rtrim($this->siteBaseUrl, '/');

        $lines = [
            '#FORMAT: BEACON',
            '#PREFIX: http://viaf.org/viaf/',
            '#TARGET: '.$baseUrl.'/viaf/{id}/',
            '#HOMEPAGE: '.$baseUrl.'/',
            '#CONTACT: Dagmar Mrozik <dagmar.mrozik@jesuitscience.net>',
            '#FEED: '.$baseUrl.'/jsn-viaf.beacon',
            '#TIMESTAMP: '.$timestamp->format(DATE_ATOM),
            '',
            ...array_map('strval', $rows),
            '',
        ];

        return new Response(implode("\n", $lines), Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="jsn-viaf.beacon"',
        ]);
    }
}
