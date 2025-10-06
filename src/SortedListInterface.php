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
     * @return array<int, int|string>
     */
    public function toArray(): array;

    /**
     * @return 'int'|'string'|null
     */
    public function getType(): ?string;

    /**
     * @return Traversable<int, int|string>
     */
    public function getIterator(): Traversable;

    /**
     * @return array<int, int|string>
     */
    public function jsonSerialize(): array;
}
