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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class SubmissionAttemptCounter
{
    private const WINDOW = 3600;

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function count(string $scope, string $ipAddress): int
    {
        $cacheItem = $this->getCacheItem($scope, $ipAddress);

        return $cacheItem->isHit() ? (int) $cacheItem->get() : 0;
    }

    public function add(string $scope, string $ipAddress, int $attempts): void
    {
        $cacheItem = $this->getCacheItem($scope, $ipAddress);

        $cacheItem->set(($cacheItem->isHit() ? (int) $cacheItem->get() : 0) + $attempts);
        $cacheItem->expiresAfter(self::WINDOW);
        $this->cache->save($cacheItem);
    }

    private function getCacheItem(string $scope, string $ipAddress): CacheItemInterface
    {
        return $this->cache->getItem('spam_protection.' . hash('sha256', $scope . '|' . $ipAddress));
    }
}
