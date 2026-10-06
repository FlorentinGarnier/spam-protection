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

namespace FlorentinGarnier\SpamProtection\IpReputation;

/**
 * The compiled IP lists, stored as a PHP file so that opcache keeps them in memory between requests.
 */
final class IpReputationList
{
    public function __construct(
        private string $path,
    ) {
    }

    /**
     * @return array<string, IpRangeSet> indexed by IpRiskLevel value, empty as long as no list has been saved
     */
    public function load(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        return array_map(IpRangeSet::fromArray(...), require $this->path);
    }

    /**
     * Atomically replaces the compiled lists, so a reader never loads a partially written file.
     *
     * @param array<string, IpRangeSet> $rangeSets indexed by IpRiskLevel value
     */
    public function save(array $rangeSets): void
    {
        $directory = \dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create the directory "%s".', $directory));
        }

        $temporaryPath = tempnam($directory, basename($this->path));
        $content = '<?php return ' . var_export(array_map(static fn (IpRangeSet $rangeSet): array => $rangeSet->toArray(), $rangeSets), true) . ";\n";

        if (false === $temporaryPath || false === file_put_contents($temporaryPath, $content)) {
            throw new \RuntimeException(sprintf('Unable to write the IP lists to "%s".', $this->path));
        }

        // tempnam() creates the file readable by its owner only, while the web server may run as another user.
        chmod($temporaryPath, 0666 & ~umask());
        rename($temporaryPath, $this->path);
    }
}
