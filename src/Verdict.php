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

namespace FlorentinGarnier\SpamProtection;

use FlorentinGarnier\SpamProtection\IpReputation\IpRiskLevel;

final readonly class Verdict
{
    public function __construct(
        public IpRiskLevel $ipRiskLevel,
        public ?RejectionReason $rejectionReason = null,
    ) {
    }

    public function isAccepted(): bool
    {
        return null === $this->rejectionReason;
    }
}
