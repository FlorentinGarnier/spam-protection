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

use FlorentinGarnier\SpamProtection\ProofOfWork;
use FlorentinGarnier\SpamProtection\SingleUseTokenRegistry;
use FlorentinGarnier\SpamProtection\Testing\SpamProtectionTestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class ProofOfWorkTest extends TestCase
{
    private const SECRET = 'test-secret';

    private ProofOfWork $proofOfWork;

    protected function setUp(): void
    {
        $this->proofOfWork = new ProofOfWork(self::SECRET, new SingleUseTokenRegistry(new ArrayAdapter()), 4);
    }

    public function testItAcceptsASolutionMeetingTheRequiredDifficulty(): void
    {
        $challenge = $this->proofOfWork->createChallenge();

        self::assertTrue($this->proofOfWork->consume($challenge, SpamProtectionTestHelper::solveProofOfWork($challenge, 8), 8));
    }

    public function testItRejectsASolutionBelowTheRequiredDifficulty(): void
    {
        $challenge = $this->proofOfWork->createChallenge();

        self::assertFalse($this->proofOfWork->consume($challenge, $this->findSolutionWithExactlyFourLeadingZeroBits($challenge), 8));
    }

    public function testItRejectsAReplayedSolution(): void
    {
        $challenge = $this->proofOfWork->createChallenge();
        $solution = SpamProtectionTestHelper::solveProofOfWork($challenge, 4);
        $this->proofOfWork->consume($challenge, $solution, 4);

        self::assertFalse($this->proofOfWork->consume($challenge, $solution, 4));
    }

    public function testItRejectsAnExpiredChallenge(): void
    {
        $challenge = SpamProtectionTestHelper::forgeProofOfWorkChallenge(self::SECRET, time() - 3601);

        self::assertFalse($this->proofOfWork->consume($challenge, SpamProtectionTestHelper::solveProofOfWork($challenge, 4), 4));
    }

    public function testItRejectsAChallengeSignedWithAnotherSecret(): void
    {
        $challenge = SpamProtectionTestHelper::forgeProofOfWorkChallenge('another-secret', time());

        self::assertFalse($this->proofOfWork->consume($challenge, SpamProtectionTestHelper::solveProofOfWork($challenge, 4), 4));
    }

    public function testItIncreasesTheDifficultyEveryFivePreviousAttemptsUpToACap(): void
    {
        self::assertSame(4, $this->proofOfWork->getDifficulty(0));
        self::assertSame(4, $this->proofOfWork->getDifficulty(4));
        self::assertSame(8, $this->proofOfWork->getDifficulty(5));
        self::assertSame(12, $this->proofOfWork->getDifficulty(10));
        self::assertSame(12, $this->proofOfWork->getDifficulty(100));
    }

    public function testItAddsExtraDifficultyWithinTheSameCap(): void
    {
        self::assertSame(8, $this->proofOfWork->getDifficulty(0, 4));
        self::assertSame(12, $this->proofOfWork->getDifficulty(5, 4));
        self::assertSame(12, $this->proofOfWork->getDifficulty(0, 8));
        self::assertSame(12, $this->proofOfWork->getDifficulty(100, 8));
    }

    private function findSolutionWithExactlyFourLeadingZeroBits(string $challenge): string
    {
        for ($solution = 0; ; ++$solution) {
            $value = $challenge . '|' . $solution;

            if (SpamProtectionTestHelper::hasLeadingZeroBits($value, 4) && !SpamProtectionTestHelper::hasLeadingZeroBits($value, 5)) {
                return (string) $solution;
            }
        }
    }
}
