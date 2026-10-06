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

final readonly class Submission
{
    /**
     * @param string       $scope    identifies the form, so that tokens and rate limits are not shared between forms
     * @param string       $honeypot value of the trap field, left empty by humans
     * @param list<string> $contents free texts of the form, rejected when made of random characters
     */
    public function __construct(
        public string $scope,
        public string $honeypot,
        public string $submissionToken,
        public string $proofOfWorkChallenge,
        public string $proofOfWorkSolution,
        public array $contents = [],
    ) {
    }
}
