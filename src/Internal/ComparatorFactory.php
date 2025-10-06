<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Internal;

use NazBoyko\SortedLinkedList\Options\SortedStringOptions;

/** @internal */
final class ComparatorFactory
{
    /**
     * @param 'int'|'string' $type
     * @param 'asc'|'desc' $order
     * @param SortedStringOptions|null $stringOptions
     * @param callable|null $custom fn(int|string,int|string): int
     * @return callable
     */
    public static function build(
        string $type,
        string $order,
        ?SortedStringOptions $stringOptions = null,
        ?callable $custom = null
    ): callable {
        $base = $custom ?? self::defaultComparator($type, $stringOptions);

        if ($order === 'desc') {
            return static function (int|string $a, int|string $b) use ($base): int {
                return -1 * $base($a, $b);
            };
        }

        return $base;
    }

    /**
     * @param 'int'|'string' $type
     * @param SortedStringOptions|null $opts
     * @return callable
     */
    private static function defaultComparator(string $type, ?SortedStringOptions $opts = null): callable
    {
        if ($type === 'int') {
            return static function (int|string $a, int|string $b): int {
                // @var int $a
                // @var int $b
                return $a <=> $b;
            };
        }

        $opts ??= new SortedStringOptions();

        if ($opts->naturalOrder) {
            if ($opts->caseInsensitive) {
                return static fn (int|string $a, int|string $b): int => strnatcasecmp((string) $a, (string) $b);
            }

            return static fn (int|string $a, int|string $b): int => strnatcmp((string) $a, (string) $b);
        }

        if ($opts->caseInsensitive) {
            return static fn (int|string $a, int|string $b): int => strcasecmp((string) $a, (string) $b);
        }

        return static fn (int|string $a, int|string $b): int => strcmp((string) $a, (string) $b);
    }
}
