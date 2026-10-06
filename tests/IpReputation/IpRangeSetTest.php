<?php

/*
 * This file is part of the florentingarnier/spam-protection package.
 *
 * (c) Florentin Garnier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FlorentinGarnier\SpamProtection\Tests\IpReputation;

use FlorentinGarnier\SpamProtection\IpReputation\IpRangeSet;
use PHPUnit\Framework\TestCase;

final class IpRangeSetTest extends TestCase
{
    public function testItContainsTheAddressesOfACidrRange(): void
    {
        $ranges = IpRangeSet::fromCidrs(['198.51.100.0/24']);

        self::assertTrue($ranges->contains('198.51.100.0'));
        self::assertTrue($ranges->contains('198.51.100.255'));
        self::assertFalse($ranges->contains('198.51.99.255'));
        self::assertFalse($ranges->contains('198.51.101.0'));
    }

    public function testItContainsASingleAddress(): void
    {
        $ranges = IpRangeSet::fromCidrs(['192.0.2.1']);

        self::assertTrue($ranges->contains('192.0.2.1'));
        self::assertFalse($ranges->contains('192.0.2.2'));
    }

    public function testItFindsAnAddressAmongManyUnsortedRanges(): void
    {
        $ranges = IpRangeSet::fromCidrs(['203.0.113.0/24', '10.0.0.0/8', '192.0.2.0/24']);

        self::assertTrue($ranges->contains('10.20.30.40'));
        self::assertTrue($ranges->contains('192.0.2.50'));
        self::assertTrue($ranges->contains('203.0.113.7'));
        self::assertFalse($ranges->contains('172.16.0.1'));
    }

    public function testItMergesOverlappingAndAdjacentRanges(): void
    {
        $ranges = IpRangeSet::fromCidrs(['10.0.0.0/24', '10.0.0.128/25', '10.0.1.0/24']);

        self::assertSame(1, $ranges->count());
        self::assertTrue($ranges->contains('10.0.1.255'));
    }

    public function testItIgnoresBlankLinesCommentsAndInvalidEntries(): void
    {
        $ranges = IpRangeSet::fromCidrs(['', '  ', '# comment', 'not-an-ip', '10.0.0.0/33', '2001:db8::/32', ' 192.0.2.1 ']);

        self::assertSame(1, $ranges->count());
        self::assertTrue($ranges->contains('192.0.2.1'));
    }

    public function testItNeverContainsAnIpv6OrInvalidAddress(): void
    {
        $ranges = IpRangeSet::fromCidrs(['0.0.0.0/0']);

        self::assertFalse($ranges->contains('2001:db8::1'));
        self::assertFalse($ranges->contains('unknown'));
    }

    public function testItSurvivesAnArrayRoundTrip(): void
    {
        $ranges = IpRangeSet::fromArray(IpRangeSet::fromCidrs(['198.51.100.0/24'])->toArray());

        self::assertTrue($ranges->contains('198.51.100.42'));
    }

    public function testAnEmptySetContainsNothing(): void
    {
        self::assertFalse(IpRangeSet::fromCidrs([])->contains('192.0.2.1'));
    }
}
