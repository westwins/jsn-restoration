<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\DTO\Bounds;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BoundsTest extends TestCase
{
    public function testParsesUrlBounds(): void
    {
        $bounds = Bounds::fromUrlValue('-10.5,20.25,30.75,40.125');

        self::assertSame('-10.5', $bounds->getSouthWest()->getLatitude());
        self::assertSame('20.25', $bounds->getSouthWest()->getLongitude());
        self::assertSame('30.75', $bounds->getNorthEast()->getLatitude());
        self::assertSame('40.125', $bounds->getNorthEast()->getLongitude());
    }

    public function testRejectsMalformedBounds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Bounds::fromUrlValue('not-a-boundary');
    }
}
