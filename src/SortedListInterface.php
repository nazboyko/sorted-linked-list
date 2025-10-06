<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @template T of int|string
 * @extends IteratorAggregate<int, T>
 */
interface SortedListInterface extends Countable, IteratorAggregate, JsonSerializable
{
    /**
     * Insert a value while preserving sorted order.
     * @param int|string $value
     */
    public function insert(int|string $value): void;

    /**
     * Insert many values while preserving sorted order for each.
     * @param iterable<int|string> $values
     */
    public function addAll(iterable $values): void;

    /**
     * Remove first occurrence of a value.
     * @param int|string $value
     * @return bool true if removed
     */
    public function remove(int|string $value): bool;

    /**
     * Remove all occurrences of a value.
     * @param int|string $value
     * @return int number of removed items
     */
    public function removeAll(int|string $value): int;

    /**
     * @param int|string $value
     */
    public function contains(int|string $value): bool;

    /**
     * @return int|string
     */
    public function first(): int|string;

    /**
     * @return int|string
     */
    public function last(): int|string;

    public function isEmpty(): bool;

    public function clear(): void;

    /**
     * @return array<int, T>
     */
    public function toArray(): array;

    /**
     * @return 'int'|'string'|null
     */
    public function getType(): ?string;

    /**
     * Find the index of the first occurrence of a value.
     * @param int|string $value
     * @return int|null null if not found
     */
    public function indexOf(int|string $value): ?int;

    /**
     * Get the value at a specific index.
     * @param int $index
     * @return T
     * @throws \OutOfBoundsException if index is out of bounds
     */
    public function get(int $index): int|string;

    /**
     * Extract a slice of the list.
     * @param int $start
     * @param int|null $length
     */
    public function slice(int $start, ?int $length = null): self;

    /**
     * Merge with another sorted list.
     * @param self $other
     */
    public function merge(self $other): self;

    /**
     * Filter elements using a callback.
     * @param callable(int|string): bool $callback
     */
    public function filter(callable $callback): self;

    /**
     * Transform elements using a callback while maintaining sort order.
     * @param callable(int|string): (int|string) $callback
     */
    public function map(callable $callback): self;

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable;

    /**
     * @return array<int, T>
     */
    public function jsonSerialize(): array;
}
