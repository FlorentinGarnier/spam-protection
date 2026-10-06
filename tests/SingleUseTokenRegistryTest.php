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
}
