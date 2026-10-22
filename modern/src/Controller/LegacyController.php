<?php

declare(strict_types=1);

namespace App\Controller;

use App\SearchService;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\SerializerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Environment;

/**
 * Temporary compatibility bridge while legacy controllers are converted to
 * constructor injection one by one. It keeps the migration reviewable without
 * exposing Symfony's full service container to application code.
 */
abstract class LegacyController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SearchService $searchService,
        private readonly PaginatorInterface $paginator,
        private readonly Environment $twig,
        private readonly SerializerInterface $serializer,
    ) {
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }

    protected function get(string $id): object
    {
        return match ($id) {
            'jsn.search' => $this->searchService,
            'knp_paginator' => $this->paginator,
            'twig' => $this->twig,
            'jms_serializer' => $this->serializer,
            default => throw new \LogicException(sprintf('Unsupported legacy service "%s".', $id)),
        };
    }
}
