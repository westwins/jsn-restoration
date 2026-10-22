<?php

namespace App\Pdr;

interface IdiProviderInterface
{
    public function getXml(string $pdrId): string;
}
