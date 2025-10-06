# Sorted Linked List for PHP (int|string)

A tiny, type-safe **sorted linked list** for PHP **8.2–8.4** that stores **either all `int` or all `string` values** (never both) and keeps them sorted on every insertion.

- ⚖️ **Type-locked** on first insert (`int` or `string`)
- 🧭 **Ordering**: ascending/descending, binary or natural string order, optional case-insensitive
- 🔁 **Duplicates**: allow/forbid + policy to insert at the **head**/**tail** of equal blocks
- 🧰 **Nice DX**: `Countable`, `IteratorAggregate`, `JsonSerializable`
- 🚀 **Rich API**: Collection methods like `filter()`, `map()`, `slice()`, `merge()`
- ⚡ **Performance**: Optimized algorithms with early termination
- ✅ Fully unit tested

---

## Requirements

- PHP **8.2 / 8.3 / 8.4**
- Composer 2.2

---

## Install

```bash
composer require nazboyko/sorted-linked-list
```

---

## Quick Start

```php
<?php
use NazBoyko\SortedLinkedList\SortedLinkedList;
use NazBoyko\SortedLinkedList\Options\SortedStringOptions;

// Integers (ascending; duplicates allowed)
$ints = SortedLinkedList::forInts(order: 'asc', allowDuplicates: true);
$ints->addAll([5, 2, 7, 3, 3, 1]);
// [1, 2, 3, 3, 5, 7]
var_dump($ints->toArray());

// Strings: natural order + case-insensitive
$strs = SortedLinkedList::forStrings(
    new SortedStringOptions(caseInsensitive: true, naturalOrder: true)
);
$strs->addAll(['file9', 'File10', 'file2']);
// ['file2', 'file9', 'File10']
var_dump($strs->toArray());
```

---

## API Overview

### Construction / Factories

```php
// Type-specific factories
SortedLinkedList::forInts(string $order = 'asc', bool $allowDuplicates = true): self;
SortedLinkedList::forStrings(?SortedStringOptions $options = null, string $order = 'asc', bool $allowDuplicates = true): self;

// Generic constructor (type determined on first insert)
new SortedLinkedList(
    ?callable $comparator = null,     // fn(int|string $a, int|string $b): int
    string $order = 'asc',            // 'asc' | 'desc'
    bool $allowDuplicates = true,
    string $duplicatesPolicy = 'tail',// 'head' | 'tail'
    ?SortedStringOptions $stringOptions = null
);

// From array
SortedLinkedList::fromArray(
    array $values,
    ?callable $comparator = null,
    string $order = 'asc',
    bool $allowDuplicates = true,
    string $duplicatesPolicy = 'tail',
    ?SortedStringOptions $stringOptions = null
): self;

// From already sorted array (optimized O(n))
SortedLinkedList::fromSortedArray(
    array $sortedValues,
    // ... same parameters as fromArray
): self;

// Generate numeric range
SortedLinkedList::fromRange(int $start, int $end, int $step = 1): self;
```

### Core Methods

```php
$list->insert(int|string $value): void;
$list->addAll(iterable $values): void;

$list->remove(int|string $value): bool;    // removes first occurrence
$list->removeAll(int|string $value): int;  // removes all, returns count
$list->contains(int|string $value): bool;  // optimized with early termination

$list->first(): int|string;  // O(1), throws EmptyListException if empty
$list->last(): int|string;   // O(1), throws EmptyListException if empty

$list->isEmpty(): bool;
$list->clear(): void;

$list->toArray(): array;     // values only
$list->getType(): ?string;   // 'int' | 'string' | null

count($list);                // Countable
foreach ($list as $v) {}     // IteratorAggregate (sorted order)
json_encode($list);          // JsonSerializable (values only)
```

### Collection Methods

```php
// Access by index
$list->indexOf(int|string $value): ?int;  // find position of value
$list->get(int $index): int|string;       // get value at index

// Slice operations
$list->slice(int $start, ?int $length = null): self;  // extract portion

// Functional operations (return new instances)
$list->filter(callable $callback): self;   // filter elements
$list->map(callable $callback): self;      // transform elements
$list->merge(SortedListInterface $other): self;  // combine lists
```

### Constants

```php
SortedLinkedList::ORDER_ASC;    // 'asc'
SortedLinkedList::ORDER_DESC;   // 'desc'
SortedLinkedList::TYPE_INT;     // 'int'
SortedLinkedList::TYPE_STRING;  // 'string'
```

---

## Duplicates Policy

**`'tail'` (default)**: insert new equal values **after** existing equals

**`'head'`**: insert new equal values **before** existing equals

```php
// 'tail' example
$tail = new SortedLinkedList(duplicatesPolicy: 'tail');
$tail->addAll(['a','a','b']);
$tail->insert('a'); // → ['a','a','a','b']

// 'head' example
$head = new SortedLinkedList(duplicatesPolicy: 'head');
$head->addAll(['b','a','a']);
$head->insert('a'); // → ['a','a','a','b']
```

---

## Custom Comparator

Provide your own comparator for unusual orders (still type-locked to int|string):

```php
$cmp = static fn(int|string $a, int|string $b) => strlen((string)$a) <=> strlen((string)$b);

$list = new SortedLinkedList(comparator: $cmp, order: 'asc', allowDuplicates: true);
$list->addAll(['bbb', 'a', 'cc']); // ['a','cc','bbb']
```

---

## Method Chaining

Collection methods return new instances, enabling fluent operations:

```php
$result = SortedLinkedList::fromRange(1, 20)
    ->filter(fn($x) => $x % 2 === 0)    // Keep even numbers: [2,4,6,8,10,12,14,16,18,20]
    ->map(fn($x) => $x * 3)             // Multiply by 3: [6,12,18,24,30,36,42,48,54,60]
    ->slice(2, 4)                       // Take elements 2-5: [18,24,30,36]
    ->toArray();

echo json_encode($result); // [18,24,30,36]
```

---

## Advanced Examples

### String Options

```php
use NazBoyko\SortedLinkedList\Options\SortedStringOptions;

// Case-insensitive sorting
$options = new SortedStringOptions(caseInsensitive: true);
$list = SortedLinkedList::forStrings($options);
$list->addAll(['Banana', 'apple', 'Cherry']);
// Result: ["apple", "Banana", "Cherry"]

// Natural order (for strings with numbers)
$options = new SortedStringOptions(naturalOrder: true);
$list = SortedLinkedList::forStrings($options);
$list->addAll(['file10.txt', 'file2.txt', 'file1.txt']);
// Result: ["file1.txt", "file2.txt", "file10.txt"]
```

### Working with Ranges

```php
// Basic range
$range = SortedLinkedList::fromRange(1, 10); // [1,2,3,4,5,6,7,8,9,10]

// With step
$evens = SortedLinkedList::fromRange(0, 10, 2); // [0,2,4,6,8,10]

// Descending
$countdown = SortedLinkedList::fromRange(10, 1, -2); // [2,4,6,8,10]
```

### Data Processing Pipeline

```php
$words = ['apple', 'Banana', 'cherry', 'DATE', 'elderberry'];

$processed = SortedLinkedList::fromArray($words)
    ->filter(fn($word) => strlen($word) > 4)     // Words longer than 4 chars
    ->map(fn($word) => strtoupper($word))        // Convert to uppercase
    ->slice(0, 3);                              // Take first 3

echo json_encode($processed); // ["APPLE","BANANA","CHERRY"]
```

---

## Complexity

| Operation | Time Complexity | Space | Notes |
|-----------|----------------|-------|-------|
| `insert()` | O(n) | O(1) | Maintains sorted order |
| `contains()` | O(n) | O(1) | Early termination optimization |
| `remove()` | O(n) | O(1) | Single occurrence |
| `removeAll()` | O(n) | O(1) | All occurrences |
| `get()` | O(n) | O(1) | Index-based access |
| `indexOf()` | O(n) | O(1) | First occurrence |
| `slice()` | O(k) | O(k) | k = slice length |
| `filter()` | O(n) | O(k) | k = filtered elements |
| `map()` | O(n log n) | O(n) | Re-sorts after transformation |
| `merge()` | O(n + m) | O(n + m) | n, m = list sizes |
| `first()`, `last()` | O(1) | O(1) | Head/tail access |
| `count()` | O(1) | O(1) | Cached size |
| `fromSortedArray()` | O(n) | O(n) | Optimized bulk insert |

---

## Exception Handling

```php
use NazBoyko\SortedLinkedList\Exceptions\{
    EmptyListException,
    ValueTypeException,
    DuplicateNotAllowedException
};

try {
    $list = SortedLinkedList::forInts(allowDuplicates: false);
    $list->insert(5);
    $list->insert(5); // Throws DuplicateNotAllowedException
} catch (DuplicateNotAllowedException $e) {
    echo "Duplicate not allowed: " . $e->getMessage();
}

try {
    $list = SortedLinkedList::forInts();
    $list->insert(1);
    $list->insert('string'); // Throws ValueTypeException
} catch (ValueTypeException $e) {
    echo "Type mismatch: " . $e->getMessage();
}

try {
    $empty = SortedLinkedList::forInts();
    $empty->first(); // Throws EmptyListException
} catch (EmptyListException $e) {
    echo "List is empty: " . $e->getMessage();
}
```

---

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Run Psalm
composer psalm

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run all quality checks
composer quality
```

---

## Testing

The library includes comprehensive tests covering:

- ✅ **Core functionality** - insertion, removal, searching
- ✅ **Type safety** - int/string enforcement
- ✅ **Edge cases** - empty lists, bounds checking
- ✅ **Collection methods** - filter, map, slice, merge
- ✅ **Performance** - large dataset handling
- ✅ **String options** - case sensitivity, natural order
- ✅ **Method chaining** - fluent operations

```bash
composer test
# PHPUnit 10.5.58 by Sebastian Bergmann and contributors.
# ...........................                                       27 / 27 (100%)
# OK (27 tests, 67 assertions)
```

---

## License

MIT License. See [LICENSE](LICENSE) file for details.

---

## Changelog

### v2.0.0 (Latest)
- ✨ **New collection methods**: `indexOf()`, `get()`, `slice()`, `merge()`, `filter()`, `map()`
- ✨ **New factory methods**: `fromSortedArray()`, `fromRange()`
- ⚡ **Performance**: Optimized `contains()` method with early termination
- 🔧 **Constants**: Added public constants (`ORDER_ASC`, `ORDER_DESC`, `TYPE_INT`, `TYPE_STRING`)
- 🔗 **Method chaining**: Collection methods return new instances for fluent operations
- 📚 **Documentation**: Enhanced with comprehensive examples and complexity analysis
- 🧪 **Testing**: Expanded test coverage with 17 additional test methods
- 🛠️ **Tooling**: Added PHPStan, Psalm, and PHP-CS-Fixer configurations

### v1.0.0
- 🎉 Initial release with core sorted linked list functionality
- ⚖️ Type-safe operations for int|string values
- 🔁 Configurable duplicate handling policies
- 🧭 Ascending/descending order support
- 🧰 Standard PHP interfaces (Countable, IteratorAggregate, JsonSerializable)
