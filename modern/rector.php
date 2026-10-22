<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src/Controller',
        __DIR__.'/src/Entity',
    ])
    ->withAttributesSets(symfony: true, doctrine: true, jms: true);
