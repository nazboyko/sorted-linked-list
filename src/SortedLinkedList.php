<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList;

use Generator;
use NazBoyko\SortedLinkedList\Exceptions\DuplicateNotAllowedException;
use NazBoyko\SortedLinkedList\Exceptions\EmptyListException;
use NazBoyko\SortedLinkedList\Exceptions\ValueTypeException;
use NazBoyko\SortedLinkedList\Internal\ComparatorFactory;
use NazBoyko\SortedLinkedList\Internal\Node;
use NazBoyko\SortedLinkedList\Options\SortedStringOptions;
use Traversable;

/**
 * @template T of int|string
 * @implements SortedListInterface
 */
final class SortedLinkedList implements SortedListInterface
{
    private const DUP_HEAD = 'head';
    private const DUP_TAIL = 'tail';

    private ?Node $head = null;
    private ?Node $tail = null;
    private int $size = 0;

    /** @var 'int'|'string'|null */
    private ?string $type = null;

    /** @var callable|null fn(int|string,int|string):int once type is known */
    private $cmp = null;

    /** @var 'asc'|'desc' */
    private string $order;

    private bool $allowDuplicates;

    /** @var 'head'|'tail' */
    private string $duplicatesPolicy;

    private ?SortedStringOptions $stringOptions;

    /**
     * @param callable|null $comparator fn(int|string,int|string):int
     * @param 'asc'|'desc' $order
     * @param bool $allowDuplicates
     * @param 'head'|'tail' $duplicatesPolicy
     * @param SortedStringOptions|null $stringOptions
     */
    public function __construct(
        ?callable $comparator = null,
        string $order = 'asc',
        bool $allowDuplicates = true,
        string $duplicatesPolicy = self::DUP_TAIL,
        ?SortedStringOptions $stringOptions = null
    ) {
        if ($order !== 'asc' && $order !== 'desc') {
            throw new \InvalidArgumentException("order must be 'asc' or 'desc'");
        }

        if ($duplicatesPolicy !== self::DUP_HEAD && $duplicatesPolicy !== self::DUP_TAIL) {
            throw new \InvalidArgumentException("duplicatesPolicy must be 'head' or 'tail'");
        }

        $this->order = $order;
        $this->allowDuplicates = $allowDuplicates;
        $this->duplicatesPolicy = $duplicatesPolicy;
        $this->stringOptions = $stringOptions;
        $this->cmp = $comparator; // wrapped when type becomes known
    }

    /**
     * Factory for ints.
     */
    public static function forInts(
        string $order = 'asc',
        bool $allowDuplicates = true
    ): self {
        $self = new self(null, $order, $allowDuplicates);
        $self->type = 'int';
        $self->cmp = ComparatorFactory::build('int', $order, null, $self->cmp);

        return $self;
    }

    /**
     * Factory for strings.
     */
    public static function forStrings(
        ?SortedStringOptions $options = null,
        string $order = 'asc',
        bool $allowDuplicates = true
    ): self {
        $self = new self(null, $order, $allowDuplicates, self::DUP_TAIL, $options ?? new SortedStringOptions());
        $self->type = 'string';
        $self->cmp = ComparatorFactory::build('string', $order, $self->stringOptions, $self->cmp);

        return $self;
    }

    /**
     * @param array<int, int|string> $values
     */
    public static function fromArray(
        array $values,
        ?callable $comparator = null,
        string $order = 'asc',
        bool $allowDuplicates = true,
        string $duplicatesPolicy = self::DUP_TAIL,
        ?SortedStringOptions $stringOptions = null
    ): self {
        $self = new self($comparator, $order, $allowDuplicates, $duplicatesPolicy, $stringOptions);
        $self->addAll($values);

        return $self;
    }

    public function insert(int|string $value): void
    {
        $this->ensureTypeEstablished($value);

        if (!$this->allowDuplicates && $this->contains($value)) {
            throw new DuplicateNotAllowedException('Duplicate value is not allowed');
        }

        $node = new Node($value);

        if ($this->head === null) {
            $this->head = $this->tail = $node;
            $this->size = 1;

            return;
        }

        /** @var callable $cmp */
        $cmp = $this->cmp;

        // Prepend?
        // - DUP_HEAD: prepend if value <= head
        // - DUP_TAIL: prepend only if value < head
        $prependThreshold = ($this->duplicatesPolicy === self::DUP_HEAD) ? 0 : -1;
        if ($cmp($value, $this->head->value) <= $prependThreshold) {
            $node->next = $this->head;
            $this->head = $node;
            $this->size++;
            return;
        }

        // Append?
        // - DUP_TAIL: append if value >= tail
        // - DUP_HEAD: append only if value > tail
        $appendThreshold = ($this->duplicatesPolicy === self::DUP_TAIL) ? 0 : 1;
        if ($cmp($value, $this->tail->value) >= $appendThreshold) {
            $this->tail->next = $node;
            $this->tail = $node;
            $this->size++;
            return;
        }

        $prev = $this->head;
        $curr = $this->head->next;

        if ($this->duplicatesPolicy === self::DUP_TAIL) {
            while ($curr !== null && $cmp($value, $curr->value) >= 0) {
                $prev = $curr;
                $curr = $curr->next;
            }
        } else {
            while ($curr !== null && $cmp($value, $curr->value) > 0) {
                $prev = $curr;
                $curr = $curr->next;
            }
        }

        $prev->next = $node;
        $node->next = $curr;
        $this->size++;
    }

    public function addAll(iterable $values): void
    {
        foreach ($values as $v) {
            $this->insert($v);
        }
    }

    public function remove(int|string $value): bool
    {
        if ($this->head === null) {
            return false;
        }
        if (!$this->sameType($value)) {
            return false;
        }

        if ($this->head->value === $value) {
            $this->head = $this->head->next;

            if ($this->head === null) {
                $this->tail = null;
            }
            $this->size--;

            return true;
        }

        $prev = $this->head;
        $curr = $this->head->next;

        while ($curr !== null) {
            if ($curr->value === $value) {
                $prev->next = $curr->next;

                if ($curr === $this->tail) {
                    $this->tail = $prev;
                }
                $this->size--;

                return true;
            }
            $prev = $curr;
            $curr = $curr->next;
        }

        return false;
    }

    public function removeAll(int|string $value): int
    {
        if (!$this->sameType($value)) {
            return 0;
        }

        $count = 0;

        while ($this->head !== null && $this->head->value === $value) {
            $this->head = $this->head->next;
            $this->size--;
            $count++;
        }

        if ($this->head === null) {
            $this->tail = null;

            return $count;
        }

        $prev = $this->head;
        $curr = $this->head->next;

        while ($curr !== null) {
            if ($curr->value === $value) {
                $prev->next = $curr->next;
                $this->size--;
                $count++;
                if ($curr === $this->tail) {
                    $this->tail = $prev;
                }
                $curr = $prev->next;
                continue;
            }
            $prev = $curr;
            $curr = $curr->next;
        }

        return $count;
    }

    public function contains(int|string $value): bool
    {
        if ($this->head === null) {
            return false;
        }

        if (!$this->sameType($value)) {
            return false;
        }

        for ($n = $this->head; $n !== null; $n = $n->next) {
            if ($n->value === $value) {
                return true;
            }
        }

        return false;
    }

    public function first(): int|string
    {
        if ($this->head === null) {
            throw new EmptyListException('List is empty');
        }

        return $this->head->value;
    }

    public function last(): int|string
    {
        if ($this->tail === null) {
            throw new EmptyListException('List is empty');
        }

        return $this->tail->value;
    }

    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    public function clear(): void
    {
        $this->head = $this->tail = null;
        $this->size = 0;
    }

    /**
     * @return array<int, int|string>
     */
    public function toArray(): array
    {
        $out = [];
        for ($n = $this->head; $n !== null; $n = $n->next) {
            $out[] = $n->value;
        }

        return $out;
    }

    /**
     * @return 'int'|'string'|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function count(): int
    {
        return $this->size;
    }

    /**
     * @return Traversable<int, int|string>
     */
    public function getIterator(): Traversable
    {
        return $this->yieldValues();
    }

    /**
     * @return array<int, int|string>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function yieldValues(): Generator
    {
        for ($n = $this->head; $n !== null; $n = $n->next) {
            yield $n->value;
        }
    }

    private function ensureTypeEstablished(int|string $value): void
    {
        $valueType = gettype($value);
        if ($valueType !== 'integer' && $valueType !== 'string') {
            throw new ValueTypeException('Only int or string values are supported');
        }

        $normalized = $valueType === 'integer' ? 'int' : 'string';

        if ($this->type === null) {
            $this->type = $normalized;
            $this->cmp = ComparatorFactory::build($this->type, $this->order, $this->stringOptions, $this->cmp);

            return;
        }

        if ($this->type !== $normalized) {
            throw new ValueTypeException(sprintf(
                'Mismatched type: list holds %s but %s given',
                $this->type,
                $normalized
            ));
        }
    }

    private function sameType(int|string $value): bool
    {
        return $this->type === null
            || ($this->type === 'int' && is_int($value))
            || ($this->type === 'string' && is_string($value));
    }
}
