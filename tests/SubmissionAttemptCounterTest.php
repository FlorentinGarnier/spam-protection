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

use FlorentinGarnier\SpamProtection\SubmissionAttemptCounter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SubmissionAttemptCounterTest extends TestCase
{
    public function testItStartsWithoutAttempts(): void
    {
        $counter = new SubmissionAttemptCounter(new ArrayAdapter());

        self::assertSame(0, $counter->count('contact', '203.0.113.10'));
    }

    public function testItAccumulatesAttemptsForAFormAndAnIpAddress(): void
    {
        $counter = new SubmissionAttemptCounter(new ArrayAdapter());

        $counter->add('contact', '203.0.113.10', 1);
        $counter->add('contact', '203.0.113.10', 3);

        self::assertSame(4, $counter->count('contact', '203.0.113.10'));
    }

    public function testItCountsAttemptsSeparatelyPerFormAndIpAddress(): void
    {
        $counter = new SubmissionAttemptCounter(new ArrayAdapter());

        $counter->add('contact', '203.0.113.10', 1);

        self::assertSame(0, $counter->count('registration', '203.0.113.10'));
        self::assertSame(0, $counter->count('contact', '203.0.113.11'));
    }
}
