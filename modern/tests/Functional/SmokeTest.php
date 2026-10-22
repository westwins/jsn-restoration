<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('integration')]
final class SmokeTest extends WebTestCase
{
    #[DataProvider('pageProvider')]
    public function testPublicPageRespondsSuccessfully(string $path): void
    {
        $client = self::createClient();
        $client->request('GET', $path);

        self::assertResponseIsSuccessful();
    }

    public static function pageProvider(): iterable
    {
        yield 'home' => ['/'];
        yield 'Jesuits' => ['/jesuits/'];
        yield 'non-Jesuits' => ['/non-jesuits/'];
        yield 'places' => ['/places/'];
        yield 'places by country' => ['/places/by-country/'];
        yield 'occupations' => ['/occupations/'];
        yield 'subjects' => ['/subjects/'];
        yield 'grouped subjects' => ['/subjects/grouped/'];
        yield 'map' => ['/map/'];
        yield 'person autocomplete' => ['/persons.json?q=Kircher'];
        yield 'place autocomplete' => ['/places.json?q=Rome'];
        yield 'subjects API' => ['/subjects.json'];
        yield 'occupations API' => ['/occupations.json'];
        yield 'regions API' => ['/regions.json'];
        yield 'map markers API' => ['/map/markers?from=1490&to=1870'];
        yield 'person detail' => ['/p/14/'];
        yield 'person graph' => ['/p/14/graph'];
        yield 'person JSON' => ['/p/14.json'];
        yield 'VIAF beacon' => ['/jsn-viaf.beacon'];
        yield 'about' => ['/about/'];
        yield 'liveness' => ['/healthz'];
        yield 'readiness' => ['/readyz'];
    }
}
