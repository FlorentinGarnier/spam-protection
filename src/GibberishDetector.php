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
 * Spots texts made of random characters (e.g. "dTqLzVbKxWmPfRjN"), as sent by bots that solve the proof of work.
 * Only words in the Latin script long enough to be meaningful are analysed, so references and acronyms pass.
 */
final class GibberishDetector
{
    private const MINIMUM_WORD_LENGTH = 6;

    private const MAXIMUM_CASE_CHANGES = 1;

    private const MAXIMUM_CONSONANT_RUN = 5;

    private const MINIMUM_GIBBERISH_RATIO = 0.5;

    public function isGibberish(string $text): bool
    {
        $words = $this->extractAnalysableWords($text);

        if ([] === $words) {
            return false;
        }

        $gibberishWords = array_filter($words, fn (string $word): bool => $this->isGibberishWord($word));

        return \count($gibberishWords) / \count($words) >= self::MINIMUM_GIBBERISH_RATIO;
    }

    /**
     * @return list<string>
     */
    private function extractAnalysableWords(string $text): array
    {
        preg_match_all('/\p{Latin}{' . self::MINIMUM_WORD_LENGTH . ',}/u', $text, $matches);

        return $matches[0];
    }

    private function isGibberishWord(string $word): bool
    {
        return $this->countCaseChanges($word) > self::MAXIMUM_CASE_CHANGES || $this->hasLongConsonantRun($word);
    }

    /**
     * Counts lowercase letters followed by an uppercase one: "iPhone" has one, "dTqLzV" has two.
     */
    private function countCaseChanges(string $word): int
    {
        return preg_match_all('/\p{Ll}\p{Lu}/u', $word);
    }

    private function hasLongConsonantRun(string $word): bool
    {
        return 1 === preg_match('/[^aeiouyàâäéèêëîïôöùûüÿæœ]{' . (self::MAXIMUM_CONSONANT_RUN + 1) . ',}/iu', $word);
    }
}
