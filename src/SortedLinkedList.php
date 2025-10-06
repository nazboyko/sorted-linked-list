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
 * @implements SortedListInterface<T>
 */
final class SortedLinkedList implements SortedListInterface
{
    public const ORDER_ASC = 'asc';
    public const ORDER_DESC = 'desc';
    public const TYPE_INT = 'int';
    public const TYPE_STRING = 'string';
    
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

    /**
     * Create from already sorted array (optimized).
     * @param array<int, int|string> $sortedValues
     */
    public static function fromSortedArray(
        array $sortedValues,
        ?callable $comparator = null,
        string $order = 'asc',
        bool $allowDuplicates = true,
        string $duplicatesPolicy = self::DUP_TAIL,
        ?SortedStringOptions $stringOptions = null
    ): self {
        $self = new self($comparator, $order, $allowDuplicates, $duplicatesPolicy, $stringOptions);
        
        foreach ($sortedValues as $value) {
            if ($self->type === null) {
                $self->ensureTypeEstablished($value);
            }
            
            if (!$self->allowDuplicates && $self->contains($value)) {
                throw new DuplicateNotAllowedException('Duplicate value is not allowed');
            }
            
            $node = new Node($value);
            if ($self->head === null) {
                $self->head = $self->tail = $node;
            } else {
                $self->tail->next = $node;
                $self->tail = $node;
            }
            $self->size++;
        }

        return $self;
    }

    /**
     * Create a numeric range.
     * @param int $start
     * @param int $end
     * @param int $step
     * @return SortedLinkedList
     */
    public static function fromRange(int $start, int $end, int $step = 1): self
    {
        if ($step === 0) {
            throw new \InvalidArgumentException('Step cannot be zero');
        }

        $self = self::forInts();
        
        if ($step > 0) {
            for ($i = $start; $i <= $end; $i += $step) {
                $self->insert($i);
            }
        } else {
            for ($i = $start; $i >= $end; $i += $step) {
                $self->insert($i);
            }
        }

        return $self;
    }

    /**
     * @param int|string $value
     */
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

    /**
     * @param iterable<int|string> $values
     */
    public function addAll(iterable $values): void
    {
        foreach ($values as $v) {
            $this->insert($v);
        }
    }

    /**
     * @param int|string $value
     * @return bool
     */
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

    /**
     * @param int|string $value
     * @return int
     */
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

    /**
     * @param int|string $value
     * @return bool
     */
    public function contains(int|string $value): bool
    {
        if ($this->head === null) {
            return false;
        }

        if (!$this->sameType($value)) {
            return false;
        }

        /** @var callable $cmp */
        $cmp = $this->cmp;
        
        for ($n = $this->head; $n !== null; $n = $n->next) {
            $comparison = $cmp($value, $n->value);
            
            if ($comparison === 0) {
                return true;
            }
            
            if ($comparison < 0) {
                return false;
            }
        }

        return false;
    }

    /**
     * @return int|string
     */
    public function first(): int|string
    {
        if ($this->head === null) {
            throw new EmptyListException('List is empty');
        }

        return $this->head->value;
    }

    /**
     * @return int|string
     */
    public function last(): int|string
    {
        if ($this->tail === null) {
            throw new EmptyListException('List is empty');
        }

        return $this->tail->value;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->head = $this->tail = null;
        $this->size = 0;
    }

    /**
     * @return array<int, T>
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

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return $this->yieldValues();
    }

    /**
     * @return array<int, T>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param int|string $value
     * @return int|null
     */
    public function indexOf(int|string $value): ?int
    {
        if ($this->head === null || !$this->sameType($value)) {
            return null;
        }

        /** @var callable $cmp */
        $cmp = $this->cmp;
        $index = 0;
        
        for ($n = $this->head; $n !== null; $n = $n->next) {
            if ($cmp($value, $n->value) === 0) {
                return $index;
            }
            $index++;
        }

        return null;
    }

    /**
     * @param int $index
     * @return int|string
     */
    public function get(int $index): int|string
    {
        if ($index < 0 || $index >= $this->size) {
            throw new \OutOfBoundsException("Index {$index} is out of bounds for list of size {$this->size}");
        }

        $current = $this->head;
        for ($i = 0; $i < $index; $i++) {
            $current = $current->next;
        }

        return $current->value;
    }

    /**
    * @phpstan-return self<T>
    */
    public function slice(int $start, ?int $length = null): self
    {
        if ($start < 0) {
            $start = max(0, $this->size + $start);
        }

        if ($start >= $this->size) {
            return $this->createEmpty();
        }

        $length = $length ?? ($this->size - $start);
        if ($length <= 0) {
            return $this->createEmpty();
        }

        $result = $this->createEmpty();
        $current = $this->head;

        // Skip to start position
        for ($i = 0; $i < $start && $current !== null; $i++) {
            $current = $current->next;
        }

        // Collect slice elements
        for ($i = 0; $i < $length && $current !== null; $i++) {
            $result->insert($current->value);
            $current = $current->next;
        }

        return $result;
    }

    /**
    * @param self<T> $other
    * @phpstan-return self<T>
    */
    public function merge(SortedListInterface $other): self
    {
        if ($this->type !== null && $other->getType() !== null && $this->type !== $other->getType()) {
            throw new ValueTypeException('Cannot merge lists of different types');
        }

        $result = $this->createEmpty();
        
        foreach ($this as $value) {
            $result->insert($value);
        }
        
        foreach ($other as $value) {
            $result->insert($value);
        }

        return $result;
    }

    /**
    * @param callable(T): bool $callback
    * @phpstan-return self<T>
    */
    public function filter(callable $callback): self
    {
        $result = $this->createEmpty();
        
        foreach ($this as $value) {
            if ($callback($value)) {
                $result->insert($value);
            }
        }

        return $result;
    }

    /**
     * @param callable(T): T $callback
     * @phpstan-return self<T>
     */
    public function map(callable $callback): self
    {
        $result = $this->createEmpty();
        
        foreach ($this as $value) {
            $result->insert($callback($value));
        }

        return $result;
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

    /**
     * Create an empty list with the same configuration as this one.
     * @phpstan-return self<T>
     */
    private function createEmpty(): static
    {
        return new self(
            $this->cmp,
            $this->order,
            $this->allowDuplicates,
            $this->duplicatesPolicy,
            $this->stringOptions
        );
    }
}
