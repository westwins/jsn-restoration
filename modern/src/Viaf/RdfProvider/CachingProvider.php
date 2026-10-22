<?php

namespace App\Viaf\RdfProvider;

use App\Viaf\RdfProviderInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CachingProvider implements RdfProviderInterface
{
    public function __construct(
        private readonly RdfProviderInterface $originalProvider,
        private readonly CacheInterface $cache,
    )
    {
    }

    public function getRdf(int|string $viaf): string
    {
        return $this->cache->get('viaf_'.hash('sha256', (string) $viaf), function (CacheItemInterface $item) use ($viaf): string {
            $item->expiresAfter(86400);

            return $this->originalProvider->getRdf($viaf);
        });
    }
}
