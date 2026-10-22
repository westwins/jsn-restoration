<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Helper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HelperTest extends TestCase
{
    #[DataProvider('slugProvider')]
    public function testSlugify(string $input, string $expected): void
    {
        self::assertSame($expected, Helper::slugify($input));
    }

    public static function slugProvider(): iterable
    {
        yield 'spaces and punctuation' => ['Natural Philosophy: Europe', 'natural-philosophy-europe'];
        yield 'accented characters' => ['René Descartes', 'rene-descartes'];
    }

    public function testGeographyFormattingHandlesMissingValues(): void
    {
        self::assertSame('', Helper::formatCountry(null));
        self::assertSame('', Helper::formatContinent(null));
        self::assertSame('Europe', Helper::formatContinent('EU'));
        self::assertSame('Germany', Helper::formatCountry('DE'));
    }

    public function testPdrIdentifierRoundTrip(): void
    {
        $identifier = Helper::num2pdr(42);

        self::assertSame('pdrPo.001.042.000000042', $identifier);
        self::assertSame(42, Helper::pdr2num($identifier));
    }
}
