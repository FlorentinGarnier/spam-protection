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

use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpRiskLevel;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Entry point of the library: issues the challenge embedded in a form, then judges the submission that comes back.
 */
final class SpamProtection
{
    private const REJECTION_PENALTY = 2;

    public function __construct(
        private SubmissionToken $submissionToken,
        private ProofOfWork $proofOfWork,
        private SubmissionAttemptCounter $attemptCounter,
        private IpReputation $ipReputation,
        private GibberishDetector $gibberishDetector,
        private int $maximumAttemptsPerHour = 20,
    ) {
    }

    /**
     * Wires the default collaborators, for applications without a dependency injection container.
     */
    public static function create(
        string $secret,
        CacheItemPoolInterface $cache,
        IpReputation $ipReputation,
        int $baseDifficulty = 10,
        int $maximumAttemptsPerHour = 20,
    ): self {
        $usedTokens = new SingleUseTokenRegistry($cache);

        return new self(
            new SubmissionToken($secret, $usedTokens),
            new ProofOfWork($secret, $usedTokens, $baseDifficulty),
            new SubmissionAttemptCounter($cache),
            $ipReputation,
            new GibberishDetector(),
            $maximumAttemptsPerHour,
        );
    }

    /**
     * Tokens are single-use: issue a fresh challenge every time the form is rendered, including after a failed submission.
     */
    public function issueChallenge(string $scope, string $ipAddress): Challenge
    {
        $riskLevel = $this->ipReputation->getRiskLevel($ipAddress);

        return new Challenge(
            $this->submissionToken->create($scope),
            $this->proofOfWork->createChallenge(),
            $this->getRequiredDifficulty($this->attemptCounter->count($scope, $ipAddress), $riskLevel),
        );
    }

    /**
     * Every verification counts as an attempt; a rejected one counts as several, so that bots face harder challenges.
     */
    public function verify(Submission $submission, string $ipAddress): Verdict
    {
        $riskLevel = $this->ipReputation->getRiskLevel($ipAddress);
        $rejectionReason = $this->findRejectionReason($submission, $this->attemptCounter->count($submission->scope, $ipAddress), $riskLevel);
        $attemptWeight = null === $rejectionReason ? 1 : 1 + self::REJECTION_PENALTY;

        $this->attemptCounter->add($submission->scope, $ipAddress, $riskLevel->getAttemptWeight() * $attemptWeight);

        return new Verdict($riskLevel, $rejectionReason);
    }

    private function findRejectionReason(Submission $submission, int $previousAttempts, IpRiskLevel $riskLevel): ?RejectionReason
    {
        return match (true) {
            '' !== $submission->honeypot => RejectionReason::HoneypotFilled,
            !$this->submissionToken->consume($submission->submissionToken, $submission->scope) => RejectionReason::InvalidSubmissionToken,
            !$this->proofOfWork->consume($submission->proofOfWorkChallenge, $submission->proofOfWorkSolution, $this->getRequiredDifficulty($previousAttempts, $riskLevel)) => RejectionReason::InvalidProofOfWork,
            $previousAttempts >= $this->maximumAttemptsPerHour => RejectionReason::RateLimitExceeded,
            $this->containsGibberish($submission->contents) => RejectionReason::UnreadableContent,
            default => null,
        };
    }

    /**
     * @param list<string> $contents
     */
    private function containsGibberish(array $contents): bool
    {
        foreach ($contents as $content) {
            if ($this->gibberishDetector->isGibberish($content)) {
                return true;
            }
        }

        return false;
    }

    private function getRequiredDifficulty(int $previousAttempts, IpRiskLevel $riskLevel): int
    {
        return $this->proofOfWork->getDifficulty($previousAttempts, $riskLevel->getExtraDifficulty());
    }
}
