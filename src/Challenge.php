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

/**
 * What a form embeds: the browser solves the proof of work, then sends both tokens back with the submission.
 */
final readonly class Challenge
{
    public function __construct(
        public string $submissionToken,
        public string $proofOfWorkChallenge,
        public int $difficulty,
    ) {
    }
}
