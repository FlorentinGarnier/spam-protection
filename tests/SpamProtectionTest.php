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

use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpRiskLevel;
use FlorentinGarnier\SpamProtection\RejectionReason;
use FlorentinGarnier\SpamProtection\SpamProtection;
use FlorentinGarnier\SpamProtection\Submission;
use FlorentinGarnier\SpamProtection\Testing\SpamProtectionTestHelper;
use FlorentinGarnier\SpamProtection\Tests\IpReputation\IpReputationFixture;
use FlorentinGarnier\SpamProtection\Verdict;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SpamProtectionTest extends TestCase
{
    private const SECRET = 'test-secret';

    private const SCOPE = 'contact';

    private const NORMAL_IP = '203.0.113.10';

    private const HOSTING_IP = '198.51.100.10';

    private const TOR_IP = '192.0.2.1';

    private SpamProtection $spamProtection;

    protected function setUp(): void
    {
        $this->spamProtection = SpamProtection::create(
            self::SECRET,
            new ArrayAdapter(),
            new IpReputation(IpReputationFixture::write(hosting: ['198.51.100.0/24'], tor: [self::TOR_IP])),
            baseDifficulty: 4,
        );
    }

    public function testItAcceptsAHumanSubmission(): void
    {
        $verdict = $this->submit();

        self::assertTrue($verdict->isAccepted());
        self::assertNull($verdict->rejectionReason);
        self::assertSame(IpRiskLevel::Normal, $verdict->ipRiskLevel);
    }

    public function testItRejectsASubmissionThatFillsTheHoneypot(): void
    {
        self::assertSame(RejectionReason::HoneypotFilled, $this->submit(honeypot: 'https://spam.example')->rejectionReason);
    }

    public function testItRejectsASubmissionMadeTooQuickly(): void
    {
        $verdict = $this->submit(submissionToken: SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, self::SCOPE, time()));

        self::assertSame(RejectionReason::InvalidSubmissionToken, $verdict->rejectionReason);
    }

    public function testItRejectsASubmissionTokenIssuedForAnotherForm(): void
    {
        $verdict = $this->submit(submissionToken: SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, 'registration', time() - 4));

        self::assertSame(RejectionReason::InvalidSubmissionToken, $verdict->rejectionReason);
    }

    public function testItRejectsAReplayedSubmissionToken(): void
    {
        $submissionToken = SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, self::SCOPE, time() - 4);
        $this->submit(submissionToken: $submissionToken);

        self::assertSame(RejectionReason::InvalidSubmissionToken, $this->submit(submissionToken: $submissionToken)->rejectionReason);
    }

    public function testItRequiresProofOfWorkFromTheFirstSubmission(): void
    {
        self::assertSame(RejectionReason::InvalidProofOfWork, $this->submit(proofOfWorkSolution: '')->rejectionReason);
    }

    public function testItRejectsAReplayedProofOfWork(): void
    {
        $challenge = $this->spamProtection->issueChallenge(self::SCOPE, self::NORMAL_IP)->proofOfWorkChallenge;
        $solution = SpamProtectionTestHelper::solveProofOfWork($challenge, 4);
        $this->submit(proofOfWorkChallenge: $challenge, proofOfWorkSolution: $solution);

        $verdict = $this->submit(proofOfWorkChallenge: $challenge, proofOfWorkSolution: $solution);

        self::assertSame(RejectionReason::InvalidProofOfWork, $verdict->rejectionReason);
    }

    public function testItIncreasesTheProofOfWorkDifficultyAfterRejectedSubmissions(): void
    {
        $this->submit(honeypot: 'spam');
        $this->submit(honeypot: 'spam');

        self::assertSame(8, $this->spamProtection->issueChallenge(self::SCOPE, self::NORMAL_IP)->difficulty);
    }

    public function testItCountsAttemptsPerForm(): void
    {
        $this->submit(honeypot: 'spam');
        $this->submit(honeypot: 'spam');

        self::assertSame(4, $this->spamProtection->issueChallenge('registration', self::NORMAL_IP)->difficulty);
    }

    public function testItRejectsSubmissionsOnceTheHourlyLimitIsReached(): void
    {
        for ($attempt = 1; $attempt <= 7; ++$attempt) {
            $this->submit(honeypot: 'spam');
        }

        self::assertSame(RejectionReason::RateLimitExceeded, $this->submit()->rejectionReason);
    }

    public function testItRateLimitsAnIpv6NetworkAsAWhole(): void
    {
        for ($host = 1; $host <= 7; ++$host) {
            $this->submit(honeypot: 'spam', ipAddress: '2001:db8:1:2::' . $host);
        }

        self::assertSame(RejectionReason::RateLimitExceeded, $this->submit(ipAddress: '2001:db8:1:2::99')->rejectionReason);
    }

    public function testItStartsWithAHarderProofOfWorkForHostingAndVpnAddresses(): void
    {
        self::assertSame(8, $this->spamProtection->issueChallenge(self::SCOPE, self::HOSTING_IP)->difficulty);
    }

    public function testItStartsWithAnEvenHarderProofOfWorkForTorExitNodes(): void
    {
        self::assertSame(12, $this->spamProtection->issueChallenge(self::SCOPE, self::TOR_IP)->difficulty);
    }

    public function testItAllowsFewerSubmissionsPerHourFromHostingAndVpnAddresses(): void
    {
        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            self::assertTrue($this->submit(ipAddress: self::HOSTING_IP)->isAccepted());
        }

        $verdict = $this->submit(ipAddress: self::HOSTING_IP);

        self::assertSame(RejectionReason::RateLimitExceeded, $verdict->rejectionReason);
        self::assertSame(IpRiskLevel::Hosting, $verdict->ipRiskLevel);
    }

    public function testItRejectsAMessageMadeOfRandomCharacters(): void
    {
        self::assertSame(RejectionReason::UnreadableContent, $this->submit(contents: ['Bonjour', 'dTqLzVbKxWmPfRjN'])->rejectionReason);
    }

    public function testItAcceptsAReadableMessage(): void
    {
        self::assertTrue($this->submit(contents: ['Hello, I would like a quote for a shelving unit.'])->isAccepted());
    }

    public function testItIncreasesTheProofOfWorkDifficultyAfterAnUnreadableMessage(): void
    {
        $this->submit(contents: ['dTqLzVbKxWmPfRjN']);
        $this->submit(contents: ['kjsdhfkjsdhf']);

        self::assertSame(8, $this->spamProtection->issueChallenge(self::SCOPE, self::NORMAL_IP)->difficulty);
    }

    public function testItIssuesFreshTokensOnEveryChallenge(): void
    {
        $first = $this->spamProtection->issueChallenge(self::SCOPE, self::NORMAL_IP);
        $second = $this->spamProtection->issueChallenge(self::SCOPE, self::NORMAL_IP);

        self::assertNotSame($first->submissionToken, $second->submissionToken);
        self::assertNotSame($first->proofOfWorkChallenge, $second->proofOfWorkChallenge);
    }

    /**
     * Builds a valid human submission, then breaks the parts given as arguments.
     *
     * @param list<string> $contents
     */
    private function submit(
        string $honeypot = '',
        ?string $submissionToken = null,
        ?string $proofOfWorkChallenge = null,
        ?string $proofOfWorkSolution = null,
        array $contents = [],
        string $ipAddress = self::NORMAL_IP,
    ): Verdict {
        $challenge = $this->spamProtection->issueChallenge(self::SCOPE, $ipAddress);
        $proofOfWorkChallenge ??= $challenge->proofOfWorkChallenge;

        return $this->spamProtection->verify(new Submission(
            scope: self::SCOPE,
            honeypot: $honeypot,
            submissionToken: $submissionToken ?? SpamProtectionTestHelper::forgeSubmissionToken(self::SECRET, self::SCOPE, time() - 4),
            proofOfWorkChallenge: $proofOfWorkChallenge,
            proofOfWorkSolution: $proofOfWorkSolution ?? SpamProtectionTestHelper::solveProofOfWork($proofOfWorkChallenge, $challenge->difficulty),
            contents: $contents,
        ), $ipAddress);
    }
}
