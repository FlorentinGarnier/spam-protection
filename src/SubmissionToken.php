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

final class SubmissionToken
{
    private const MINIMUM_AGE = 3;

    private const LIFETIME = 3600;

    public function __construct(
        private string $secret,
        private SingleUseTokenRegistry $usedTokens,
    ) {
    }

    public function create(string $scope): string
    {
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string) time();

        return $nonce . '.' . $timestamp . '.' . $this->sign($nonce, $timestamp, $scope);
    }

    public function consume(string $token, string $scope): bool
    {
        [$nonce, $timestamp, $signature] = array_pad(explode('.', $token, 3), 3, null);

        if (null === $nonce || null === $timestamp || null === $signature || !ctype_xdigit($nonce) || !ctype_digit($timestamp) || !hash_equals($this->sign($nonce, $timestamp, $scope), $signature)) {
            return false;
        }

        $age = time() - (int) $timestamp;

        return $age >= self::MINIMUM_AGE && $age <= self::LIFETIME && $this->usedTokens->consume($token, self::LIFETIME);
    }

    private function sign(string $nonce, string $timestamp, string $scope): string
    {
        return hash_hmac('sha256', 'submission_token|' . $nonce . '|' . $timestamp . '|' . $scope, $this->secret);
    }
}
