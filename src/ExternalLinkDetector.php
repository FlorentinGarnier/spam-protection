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

final class ExternalLinkDetector
{
    /**
     * A link starts with a scheme or with "www."; the e-mail address "john@www.example.com" is not one.
     */
    private const LINK_HOST_PATTERN = '~(?:https?://|(?<![\w@./-])www\.)([^\s/?#<>"\'()]+)~iu';

    /**
     * @param list<string> $ownHosts hosts of the site: links to them, or to their subdomains, are not external
     */
    public function containsExternalLink(string $text, array $ownHosts = []): bool
    {
        $ownHosts = array_map($this->normalizeHost(...), $ownHosts);
        preg_match_all(self::LINK_HOST_PATTERN, $text, $matches);

        foreach ($matches[1] as $host) {
            if (!$this->isOwnHost($this->normalizeHost($host), $ownHosts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $ownHosts
     */
    private function isOwnHost(string $host, array $ownHosts): bool
    {
        foreach ($ownHosts as $ownHost) {
            if ('' !== $ownHost && ($host === $ownHost || str_ends_with($host, '.' . $ownHost))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Keeps what identifies the site: "https://user@WWW.Example.com:8080." links to "example.com".
     */
    private function normalizeHost(string $host): string
    {
        $host = mb_strtolower($host);
        $host = substr($host, (int) strrpos('@' . $host, '@'));
        $host = (string) preg_replace('~:\d*$~', '', rtrim($host, '.,;:!'));

        return (string) preg_replace('~^www\.~', '', rtrim($host, '.'));
    }
}
