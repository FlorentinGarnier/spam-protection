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

final class IpReputationFixture
{
    /**
     * Writes a compiled list through the real IpReputationList, so tests share the production file format.
     *
     * @param list<string> $hosting
     * @param list<string> $tor
     */
    public static function write(array $hosting, array $tor): IpReputationList
    {
        $list = new IpReputationList(sys_get_temp_dir() . '/ip_reputation_fixture_' . bin2hex(random_bytes(4)) . '.php');
        $list->save(['hosting' => IpRangeSet::fromCidrs($hosting), 'tor' => IpRangeSet::fromCidrs($tor)]);

        return $list;
    }
}
