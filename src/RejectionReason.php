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
 * Values are stable: they appear in logs and dashboards.
 */
enum RejectionReason: string
{
    case HoneypotFilled = 'honeypot_filled';
    case InvalidSubmissionToken = 'invalid_timestamp_token';
    case InvalidProofOfWork = 'invalid_proof_of_work';
    case RateLimitExceeded = 'rate_limit_exceeded';
    case UnreadableContent = 'unreadable_content';
}
