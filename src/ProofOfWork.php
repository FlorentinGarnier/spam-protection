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

final class ProofOfWork
{
    private const CHALLENGE_LIFETIME = 3600;

    private const ATTEMPTS_PER_DIFFICULTY_STEP = 5;

    private const DIFFICULTY_STEP = 4;

    private const MAXIMUM_EXTRA_DIFFICULTY = 8;

    public function __construct(
        private string $secret,
        private SingleUseTokenRegistry $usedChallenges,
        private int $baseDifficulty = 10,
    ) {
    }

    public function createChallenge(): string
    {
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) time();

        return $nonce . '.' . $timestamp . '.' . $this->sign($nonce, $timestamp);
    }

    public function consume(string $challenge, string $solution, int $difficulty): bool
    {
        [$nonce, $timestamp, $signature] = array_pad(explode('.', $challenge, 3), 3, null);

        if (null === $nonce || null === $timestamp || null === $signature || !ctype_xdigit($nonce) || !ctype_digit($timestamp) || !hash_equals($this->sign($nonce, $timestamp), $signature)) {
            return false;
        }

        $elapsedTime = time() - (int) $timestamp;

        if ($elapsedTime < 0 || $elapsedTime > self::CHALLENGE_LIFETIME || !ctype_digit($solution)) {
            return false;
        }

        return $this->hasRequiredDifficulty($challenge . '|' . $solution, $difficulty) && $this->usedChallenges->consume($challenge, self::CHALLENGE_LIFETIME);
    }

    /**
     * The extra difficulty (e.g. for a risky IP address) shares the cap of the attempt-based increase,
     * so the challenge never becomes impossible to solve in a browser.
     */
    public function getDifficulty(int $previousAttempts, int $extraDifficulty = 0): int
    {
        $attemptDifficulty = intdiv($previousAttempts, self::ATTEMPTS_PER_DIFFICULTY_STEP) * self::DIFFICULTY_STEP;

        return $this->baseDifficulty + min($attemptDifficulty + $extraDifficulty, self::MAXIMUM_EXTRA_DIFFICULTY);
    }

    private function sign(string $nonce, string $timestamp): string
    {
        return hash_hmac('sha256', 'proof_of_work|' . $nonce . '|' . $timestamp, $this->secret);
    }

    private function hasRequiredDifficulty(string $value, int $difficulty): bool
    {
        $hash = hash('sha256', $value, true);
        $completeBytes = intdiv($difficulty, 8);
        $remainingBits = $difficulty % 8;

        if (strspn($hash, "\0", 0, $completeBytes) !== $completeBytes) {
            return false;
        }

        return 0 === $remainingBits || (ord($hash[$completeBytes]) >> (8 - $remainingBits)) === 0;
    }
}
