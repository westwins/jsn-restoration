<?php

namespace App\Pdr\IdiProvider;

use App\Pdr\IdiProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RemoteProvider implements IdiProviderInterface
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getXml(string $pdrId): string
    {
        $url = 'https://pdrprod.bbaw.de/idi/pdrnc/'.$pdrId;

        return $this->httpClient->request('GET', $url, [
            'timeout' => 30,
        ])->getContent();
    }
}
