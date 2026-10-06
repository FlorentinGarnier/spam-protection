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

use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpReputationList;
use FlorentinGarnier\SpamProtection\IpReputation\IpRiskLevel;
use PHPUnit\Framework\TestCase;

final class IpReputationTest extends TestCase
{
    public function testItTreatsEveryAddressAsNormalWhenNoListHasBeenDownloadedYet(): void
    {
        $reputation = new IpReputation(new IpReputationList(sys_get_temp_dir() . '/missing_ip_reputation_list.php'));

        self::assertSame(IpRiskLevel::Normal, $reputation->getRiskLevel('198.51.100.10'));
    }

    public function testItTreatsAnAddressOutsideTheListsAsNormal(): void
    {
        $list = IpReputationFixture::write(hosting: ['198.51.100.0/24'], tor: ['192.0.2.1']);

        self::assertSame(IpRiskLevel::Normal, (new IpReputation($list))->getRiskLevel('203.0.113.10'));
    }

    public function testTorTakesPrecedenceOverHosting(): void
    {
        $list = IpReputationFixture::write(hosting: ['192.0.2.0/24'], tor: ['192.0.2.1']);

        self::assertSame(IpRiskLevel::Tor, (new IpReputation($list))->getRiskLevel('192.0.2.1'));
    }

    public function testItPenalisesRiskierAddresses(): void
    {
        self::assertSame([0, 1], [IpRiskLevel::Normal->getExtraDifficulty(), IpRiskLevel::Normal->getAttemptWeight()]);
        self::assertSame([4, 4], [IpRiskLevel::Hosting->getExtraDifficulty(), IpRiskLevel::Hosting->getAttemptWeight()]);
        self::assertSame([8, 4], [IpRiskLevel::Tor->getExtraDifficulty(), IpRiskLevel::Tor->getAttemptWeight()]);
    }
}
