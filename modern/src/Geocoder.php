<?php

namespace App;

use App\DTO\Location;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Geocodes places via Google Geocoder
 */
class Geocoder
{
    private $replacementMap = [
        'Dobrzyń Land' => 'Dobrzyń nad Wisłą',
        'Saint Petersburg' => 'Saint Petersburg, Russia',
        'Buda' => 'Budapest, Hungary',
        'Naples' => 'Naples, Italy',
        'Vilnius Voivodeship' => 'Vilnius, Lithuania',
        'Serpa' => 'Serpa, Portugal',
        'Segno' => 'Segno, Italy',
        'Gascony' => 'Mont-de-Marsan, France',
        'Clermont' => 'Clermont-Ferrand, France',
        'Nola' => 'Nola, Italy',
        'Transylvania' => 'Cluj-Napoca, Rumania',
        'East Prussia' => 'Kaliningrad, Russia',
        'North America' => 'Kansas City, USA',
        'Yorkshire' => 'Yorkshire, UK',
        'Béarn' => 'Pau, France',
        'Ruthenia' => 'Kiev, Ukraine',
    ];

    private $manualLookup = [
        'Samogitia' => [55.75, 22.75, 'PL'],
        'Kashubia' => [54.25, 18.00, 'PL'],
        'Red Ruthenia' => [49.59, 24.41, 'PL'],
    ];

    private static $continentCodes = [
        'Europe' => 'EU',
        'Asia' => 'AS',
        'North America' => 'NA',
    ];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $mapsServerKey,
    )
    {
    }

    /**
     * @param $placeName
     * @return Location
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function geocode(string $placeName): Location
    {
        if (array_key_exists($placeName, $this->manualLookup)) {
            list($lat, $lng, $country) = $this->manualLookup[$placeName];
            return new Location($lat, $lng, $country);
        }

        if (array_key_exists($placeName, $this->replacementMap)) {
            $placeName = $this->replacementMap[$placeName];
        }

        $json = $this->cache->get('geocode_'.hash('sha256', $placeName), function (CacheItemInterface $item) use ($placeName): string {
            $item->expiresAfter(2592000);

            return $this->fetch($placeName);
        });

        $data = json_decode($json, true);

        if (!is_array($data) || ($data['status'] ?? null) !== 'OK') {
            $this->logger->warning('Geocoding failed for "{placeName}".', ['placeName' => $placeName]);

            return new Location(0, 0, null, null);
        }

        $loc = $data['results'][0]['geometry']['location'];

        $country = null;
        foreach ($data['results'][0]['address_components'] as $comp) {
            if (!in_array('country', $comp['types'])) {
                continue;
            }
            $country = $comp['short_name'];
        }

        $continent = null;
        if (!$country) {
            foreach ($data['results'][0]['address_components'] as $comp) {
                if (!in_array('continent', $comp['types'])) {
                    continue;
                }
                if (!array_key_exists($comp['short_name'], self::$continentCodes)) {
                    throw new \Exception('Continent '.$comp['short_name'] . ' unknown');
                }
                $continent = self::$continentCodes[$comp['short_name']];
            }
        }

        if (!$country && !$continent) {
            //$this->logger->warning('Geocoding did not yield a country or continent for "{placeName}"', array('placeName'=>$placeName));
        }

        return new Location($loc['lat'], $loc['lng'], $country, $continent);
    }

    protected function fetch(string $placeName): string
    {
        if ('' === $this->mapsServerKey) {
            throw new \RuntimeException('GOOGLE_MAPS_SERVER_KEY must be configured for geocoding.');
        }

        return $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'language' => 'en',
                'key' => $this->mapsServerKey,
                'address' => $placeName,
            ],
            'timeout' => 30,
        ])->getContent();
    }
}
