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
    /**
     * @param TokenLock|null $lock without it, two simultaneous requests may both consume the same token
     */
    public function __construct(
        private CacheItemPoolInterface $cache,
        private ?TokenLock $lock = null,
    ) {
    }

    /**
     * Returns true the first time a token is consumed, false on any later attempt within its lifetime,
     * and false as well while another request is consuming it.
     */
    public function consume(string $token, int $lifetime): bool
    {
        $key = 'spam_protection.used_token.' . hash('sha256', $token);

        if (null === $this->lock) {
            return $this->markAsUsed($key, $lifetime);
        }

        if (!$this->lock->acquire($key)) {
            return false;
        }

        try {
            return $this->markAsUsed($key, $lifetime);
        } finally {
            $this->lock->release($key);
        }
    }

    private function markAsUsed(string $key, int $lifetime): bool
    {
        $cacheItem = $this->cache->getItem($key);

        if ($cacheItem->isHit()) {
            return false;
        }

        $cacheItem->set(true);
        $cacheItem->expiresAfter($lifetime);
        $this->cache->save($cacheItem);

        return true;
    }
}
