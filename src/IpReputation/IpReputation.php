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

final class IpReputation
{
    private const RISK_LEVELS_FROM_HIGHEST = [IpRiskLevel::Tor, IpRiskLevel::Hosting];

    /** @var array<string, IpRangeSet>|null */
    private ?array $rangeSets = null;

    public function __construct(
        private IpReputationList $list,
    ) {
    }

    /**
     * Fails open: as long as no list has been downloaded, every address is considered normal.
     */
    public function getRiskLevel(string $ipAddress): IpRiskLevel
    {
        foreach (self::RISK_LEVELS_FROM_HIGHEST as $riskLevel) {
            if ($this->getRangeSet($riskLevel)?->contains($ipAddress)) {
                return $riskLevel;
            }
        }

        return IpRiskLevel::Normal;
    }

    private function getRangeSet(IpRiskLevel $riskLevel): ?IpRangeSet
    {
        $this->rangeSets ??= $this->list->load();

        return $this->rangeSets[$riskLevel->value] ?? null;
    }
}
