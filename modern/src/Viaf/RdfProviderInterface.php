<?php

namespace App\Viaf;

interface RdfProviderInterface
{
    public function getRdf(int|string $viaf): string;
}
