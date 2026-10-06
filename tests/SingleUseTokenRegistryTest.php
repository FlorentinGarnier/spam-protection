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

namespace FlorentinGarnier\SpamProtection\Tests;

use FlorentinGarnier\SpamProtection\SingleUseTokenRegistry;
use FlorentinGarnier\SpamProtection\TokenLock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SingleUseTokenRegistryTest extends TestCase
{
    public function testItConsumesATokenOnlyOnce(): void
    {
        $registry = new SingleUseTokenRegistry(new ArrayAdapter());

        self::assertTrue($registry->consume('token', 60));
        self::assertFalse($registry->consume('token', 60));
    }

    public function testItConsumesDistinctTokensIndependently(): void
    {
        $registry = new SingleUseTokenRegistry(new ArrayAdapter());

        self::assertTrue($registry->consume('first-token', 60));
        self::assertTrue($registry->consume('second-token', 60));
    }

    /**
     * PSR-6 has no atomic "add if absent": two simultaneous requests could both find the token unused.
     * The request that cannot take the lock is the duplicate one.
     */
    public function testItRejectsATokenThatAnotherRequestIsConsuming(): void
    {
        $cache = new ArrayAdapter();

        self::assertFalse((new SingleUseTokenRegistry($cache, $this->createLock(available: false)))->consume('token', 60));
        self::assertTrue((new SingleUseTokenRegistry($cache, $this->createLock(available: true)))->consume('token', 60));
    }

    public function testItReleasesTheLockOnceTheTokenIsConsumed(): void
    {
        $lock = $this->createLock(available: true);
        $registry = new SingleUseTokenRegistry(new ArrayAdapter(), $lock);

        $registry->consume('token', 60);
        $registry->consume('token', 60);

        self::assertCount(2, $lock->acquired);
        self::assertSame($lock->acquired, $lock->released);
    }

    public function testItLocksEachTokenSeparately(): void
    {
        $lock = $this->createLock(available: true);
        $registry = new SingleUseTokenRegistry(new ArrayAdapter(), $lock);

        $registry->consume('first-token', 60);
        $registry->consume('second-token', 60);

        self::assertNotSame($lock->acquired[0], $lock->acquired[1]);
    }

    private function createLock(bool $available): TokenLock
    {
        return new class($available) implements TokenLock {
            /** @var list<string> */
            public array $acquired = [];

            /** @var list<string> */
            public array $released = [];

            public function __construct(private bool $available)
            {
            }

            public function acquire(string $key): bool
            {
                if ($this->available) {
                    $this->acquired[] = $key;
                }

                return $this->available;
            }

            public function release(string $key): void
            {
                $this->released[] = $key;
            }
        };
    }
}
