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
use FlorentinGarnier\SpamProtection\IpReputation\IpReputationList;
use PHPUnit\Framework\TestCase;

final class IpReputationListTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/ip_reputation_list_' . bin2hex(random_bytes(4)) . '/list.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        @rmdir(\dirname($this->path));
    }

    public function testItIsEmptyAsLongAsNoListHasBeenSaved(): void
    {
        self::assertSame([], (new IpReputationList($this->path))->load());
    }

    public function testItLoadsTheRangeSetsItSaved(): void
    {
        $list = new IpReputationList($this->path);

        $list->save(['hosting' => IpRangeSet::fromCidrs(['198.51.100.0/24']), 'tor' => IpRangeSet::fromCidrs(['192.0.2.1'])]);

        $rangeSets = $list->load();
        self::assertSame(['hosting', 'tor'], array_keys($rangeSets));
        self::assertTrue($rangeSets['hosting']->contains('198.51.100.10'));
        self::assertTrue($rangeSets['tor']->contains('192.0.2.1'));
    }

    public function testItReplacesThePreviousList(): void
    {
        $list = new IpReputationList($this->path);
        $list->save(['tor' => IpRangeSet::fromCidrs(['192.0.2.1'])]);

        $list->save(['tor' => IpRangeSet::fromCidrs(['192.0.2.2'])]);

        self::assertFalse($list->load()['tor']->contains('192.0.2.1'));
        self::assertTrue($list->load()['tor']->contains('192.0.2.2'));
    }

    public function testItLeavesNoTemporaryFileBehind(): void
    {
        (new IpReputationList($this->path))->save(['tor' => IpRangeSet::fromCidrs(['192.0.2.1'])]);

        self::assertSame(['list.php'], array_values(array_diff(scandir(\dirname($this->path)), ['.', '..'])));
    }
}
