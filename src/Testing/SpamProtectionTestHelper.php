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

namespace FlorentinGarnier\SpamProtection\Testing;

final class SpamProtectionTestHelper
{
    public static function forgeSubmissionToken(string $secret, string $scope, int $timestamp): string
    {
        $nonce = bin2hex(random_bytes(16));

        return $nonce . '.' . $timestamp . '.' . hash_hmac('sha256', 'submission_token|' . $nonce . '|' . $timestamp . '|' . $scope, $secret);
    }

    public static function forgeProofOfWorkChallenge(string $secret, int $timestamp): string
    {
        $nonce = bin2hex(random_bytes(16));

        return $nonce . '.' . $timestamp . '.' . hash_hmac('sha256', 'proof_of_work|' . $nonce . '|' . $timestamp, $secret);
    }

    public static function solveProofOfWork(string $challenge, int $difficulty): string
    {
        for ($solution = 0; ; ++$solution) {
            if (self::hasLeadingZeroBits($challenge . '|' . $solution, $difficulty)) {
                return (string) $solution;
            }
        }
    }

    public static function hasLeadingZeroBits(string $value, int $bits): bool
    {
        $binary = '';

        foreach (str_split(substr(hash('sha256', $value, true), 0, 4)) as $byte) {
            $binary .= str_pad(decbin(ord($byte)), 8, '0', \STR_PAD_LEFT);
        }

        return str_starts_with($binary, str_repeat('0', $bits));
    }
}
