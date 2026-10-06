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

use Psr\Cache\CacheItemPoolInterface;

final class SingleUseTokenRegistry
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Returns true the first time a token is consumed, false on any later attempt within its lifetime.
     */
    public function consume(string $token, int $lifetime): bool
    {
        $cacheItem = $this->cache->getItem('spam_protection.used_token.' . hash('sha256', $token));

        if ($cacheItem->isHit()) {
            return false;
        }

        $cacheItem->set(true);
        $cacheItem->expiresAfter($lifetime);
        $this->cache->save($cacheItem);

        return true;
    }
}
