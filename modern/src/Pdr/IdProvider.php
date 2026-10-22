<?php

namespace App\Pdr;

use App\Helper;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IdProvider
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getIds(): array
    {
        // @todo find project lastmod, if no refresh necessary, then return array();
        // else: get idrange call, parse out ids

        $url = 'https://pdrprod.bbaw.de/axis2/services/Utilities/getOccupiedIDRanges?Type=pdrPo&Instance=1&Min=1&Max=99999999&Project=42';

        $response = $this->httpClient->request('GET', $url, ['timeout' => 30])->getContent();

        return $this->getIdsFromXml($response);
    }

    private function getIdsFromXml(string $responseText): array
    {
        $xml = simplexml_load_string($responseText);

        if (false === $xml) {
            throw new \RuntimeException('Could not parse the PDR ID range response.');
        }

        $xml->registerXPathNamespace('allies', 'http://allies.pdr.bbaw.org');

        $ids = [];
        foreach ($xml->xpath('//allies:Range') as $range) {
            $info = $range->children('http://allies.pdr.bbaw.org');
            $ids = array_merge($ids, range((int)((string)$info->Min), (int)((string)$info->Max)));
        }

        return array_map(function($e) {
            return Helper::num2pdr($e);
        }, $ids);
    }
}
