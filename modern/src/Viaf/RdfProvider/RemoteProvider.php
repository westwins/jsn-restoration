<?php

namespace App\Viaf\RdfProvider;

use App\Viaf\RdfProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RemoteProvider implements RdfProviderInterface
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getRdf(int|string $viaf): string
    {
        $url = 'https://viaf.org/viaf/'.$viaf.'/rdf.xml';

        return $this->httpClient->request('GET', $url, [
            'timeout' => 30,
        ])->getContent();
    }
}
