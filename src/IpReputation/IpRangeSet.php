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
 * Sorted, non-overlapping IPv4 ranges searched by dichotomy: lists of tens of thousands of CIDRs stay cheap to query.
 */
final class IpRangeSet implements \Countable
{
    /**
     * @param list<int> $starts
     * @param list<int> $ends
     */
    private function __construct(
        private array $starts,
        private array $ends,
    ) {
    }

    /**
     * @param iterable<string> $cidrs IPv4 addresses or CIDR blocks; blank lines, comments and invalid entries are ignored
     */
    public static function fromCidrs(iterable $cidrs): self
    {
        $ranges = [];

        foreach ($cidrs as $cidr) {
            $range = self::parseCidr(trim($cidr));

            if (null !== $range) {
                $ranges[] = $range;
            }
        }

        usort($ranges, static fn (array $first, array $second): int => $first[0] <=> $second[0]);

        return self::merge($ranges);
    }

    /**
     * @param array{starts: list<int>, ends: list<int>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['starts'], $data['ends']);
    }

    /**
     * @return array{starts: list<int>, ends: list<int>}
     */
    public function toArray(): array
    {
        return ['starts' => $this->starts, 'ends' => $this->ends];
    }

    public function count(): int
    {
        return \count($this->starts);
    }

    public function contains(string $ipAddress): bool
    {
        $address = self::toInteger($ipAddress);

        if (null === $address) {
            return false;
        }

        $low = 0;
        $high = \count($this->starts) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);

            if ($address < $this->starts[$middle]) {
                $high = $middle - 1;
            } elseif ($address > $this->ends[$middle]) {
                $low = $middle + 1;
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{int, int}|null
     */
    private static function parseCidr(string $cidr): ?array
    {
        [$ipAddress, $prefixLength] = array_pad(explode('/', $cidr, 2), 2, '32');
        $address = self::toInteger($ipAddress);

        if (null === $address || !ctype_digit($prefixLength) || (int) $prefixLength > 32) {
            return null;
        }

        $hostBits = 32 - (int) $prefixLength;
        $start = ($address >> $hostBits) << $hostBits;

        return [$start, $start + (1 << $hostBits) - 1];
    }

    private static function toInteger(string $ipAddress): ?int
    {
        if (false === filter_var($ipAddress, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            return null;
        }

        return ip2long($ipAddress);
    }

    /**
     * @param list<array{int, int}> $sortedRanges
     */
    private static function merge(array $sortedRanges): self
    {
        $starts = [];
        $ends = [];

        foreach ($sortedRanges as [$start, $end]) {
            $last = array_key_last($ends);

            if (null !== $last && $start <= $ends[$last] + 1) {
                $ends[$last] = max($ends[$last], $end);

                continue;
            }

            $starts[] = $start;
            $ends[] = $end;
        }

        return new self($starts, $ends);
    }
}
