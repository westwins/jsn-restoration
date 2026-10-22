<?php

namespace App\Pdr\IdiProvider;

use App\Pdr\IdiProviderInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

class CachingProvider implements IdiProviderInterface
{
    public function __construct(
        private readonly IdiProviderInterface $originalProvider,
        private readonly CacheInterface $cache,
    )
    {
    }

    public function getXml(string $pdrId): string
    {
        return $this->cache->get('idi_'.hash('sha256', $pdrId), function (CacheItemInterface $item) use ($pdrId): string {
            $item->expiresAfter(86400);

            return $this->originalProvider->getXml($pdrId);
        });
    }
}
