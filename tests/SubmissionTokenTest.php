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
use FlorentinGarnier\SpamProtection\SubmissionToken;
use FlorentinGarnier\SpamProtection\Testing\SpamProtectionTestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SubmissionTokenTest extends TestCase
{
    private const SECRET = 'test-secret';

    private SubmissionToken $submissionToken;

    protected function setUp(): void
    {
        $this->submissionToken = new SubmissionToken(self::SECRET, new SingleUseTokenRegistry(new ArrayAdapter()));
    }

    public function testItAcceptsATokenSubmittedAfterTheMinimumDelay(): void
    {
        $token = SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, 'contact', time() - 4);

        self::assertTrue($this->submissionToken->consume($token, 'contact'));
    }

    public function testItRejectsATokenSubmittedTooQuickly(): void
    {
        self::assertFalse($this->submissionToken->consume($this->submissionToken->create('contact'), 'contact'));
    }

    public function testItRejectsAnExpiredToken(): void
    {
        $token = SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, 'contact', time() - 3601);

        self::assertFalse($this->submissionToken->consume($token, 'contact'));
    }

    public function testItRejectsAReplayedToken(): void
    {
        $token = SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, 'contact', time() - 4);
        $this->submissionToken->consume($token, 'contact');

        self::assertFalse($this->submissionToken->consume($token, 'contact'));
    }

    public function testItRejectsATokenIssuedForAnotherForm(): void
    {
        $token = SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, 'registration', time() - 4);

        self::assertFalse($this->submissionToken->consume($token, 'contact'));
    }

    public function testItRejectsATokenSignedWithAnotherSecret(): void
    {
        $token = SpamProtectionTestHelper::forgeSubmissionToken('another-secret', 'contact', time() - 4);

        self::assertFalse($this->submissionToken->consume($token, 'contact'));
    }

    public function testItRejectsAMalformedToken(): void
    {
        self::assertFalse($this->submissionToken->consume('not-a-token', 'contact'));
    }
}
