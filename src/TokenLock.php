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

/**
 * Mutual exclusion between requests consuming the same token. PSR-6 offers no atomic "add if absent", so without
 * a lock two simultaneous requests could both find a token unused. The lock must be shared by every web server.
 */
interface TokenLock
{
    /**
     * Must not wait: returns false at once when another request holds the lock.
     */
    public function acquire(string $key): bool;

    public function release(string $key): void;
}
