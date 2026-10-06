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

namespace FlorentinGarnier\SpamProtection\IpReputation;

enum IpRiskLevel: string
{
    case Normal = 'normal';
    case Hosting = 'hosting';
    case Tor = 'tor';

    /**
     * Bits added to the proof of work difficulty from the very first submission.
     */
    public function getExtraDifficulty(): int
    {
        return match ($this) {
            self::Normal => 0,
            self::Hosting => 4,
            self::Tor => 8,
        };
    }

    /**
     * How many attempts a single submission counts for, so that risky addresses reach the hourly limit sooner.
     */
    public function getAttemptWeight(): int
    {
        return match ($this) {
            self::Normal => 1,
            self::Hosting, self::Tor => 4,
        };
    }
}
