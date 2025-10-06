<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Tests;

use NazBoyko\SortedLinkedList\Exceptions\ValueTypeException;
use NazBoyko\SortedLinkedList\Options\SortedStringOptions;
use NazBoyko\SortedLinkedList\SortedLinkedList;
use PHPUnit\Framework\TestCase;

final class SortedLinkedListEnhancedTest extends TestCase
{
    public function testConstants(): void
    {
        self::assertSame('asc', SortedLinkedList::ORDER_ASC);
        self::assertSame('desc', SortedLinkedList::ORDER_DESC);
        self::assertSame('int', SortedLinkedList::TYPE_INT);
        self::assertSame('string', SortedLinkedList::TYPE_STRING);
    }

    public function testIndexOf(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([5, 2, 8, 2, 1]);
        
        self::assertSame(0, $list->indexOf(1));
        self::assertSame(1, $list->indexOf(2));
        self::assertSame(3, $list->indexOf(5));
        self::assertSame(4, $list->indexOf(8));
        self::assertNull($list->indexOf(99));
        self::assertNull($list->indexOf('wrong type'));
    }

    public function testGet(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([5, 2, 8, 1]);
        
        self::assertSame(1, $list->get(0));
        self::assertSame(2, $list->get(1));
        self::assertSame(5, $list->get(2));
        self::assertSame(8, $list->get(3));
    }

    public function testGetOutOfBounds(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([1, 2, 3]);
        
        $this->expectException(\OutOfBoundsException::class);
        $list->get(5);
    }

    public function testSlice(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([1, 2, 3, 4, 5]);
        
        $slice = $list->slice(1, 3);
        self::assertSame([2, 3, 4], $slice->toArray());
        
        $slice = $list->slice(2);
        self::assertSame([3, 4, 5], $slice->toArray());
        
        $slice = $list->slice(-2);
        self::assertSame([4, 5], $slice->toArray());
        
        $slice = $list->slice(10);
        self::assertTrue($slice->isEmpty());
    }

    public function testMerge(): void
    {
        $list1 = SortedLinkedList::forInts();
        $list1->addAll([1, 3, 5]);
        
        $list2 = SortedLinkedList::forInts();
        $list2->addAll([2, 4, 6]);
        
        $merged = $list1->merge($list2);
        self::assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    public function testMergeTypeMismatch(): void
    {
        $intList = SortedLinkedList::forInts();
        $intList->insert(1);
        
        $stringList = SortedLinkedList::forStrings();
        $stringList->insert('a');
        
        $this->expectException(ValueTypeException::class);
        $intList->merge($stringList);
    }

    public function testFilter(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([1, 2, 3, 4, 5, 6]);
        
        $evens = $list->filter(fn($x) => $x % 2 === 0);
        self::assertSame([2, 4, 6], $evens->toArray());
        
        $greaterThan3 = $list->filter(fn($x) => $x > 3);
        self::assertSame([4, 5, 6], $greaterThan3->toArray());
    }

    public function testMap(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([1, 2, 3]);
        
        $doubled = $list->map(fn($x) => $x * 2);
        self::assertSame([2, 4, 6], $doubled->toArray());
        
        $stringList = SortedLinkedList::forStrings();
        $stringList->addAll(['a', 'b', 'c']);
        
        $uppercased = $stringList->map(fn($x) => strtoupper($x));
        self::assertSame(['A', 'B', 'C'], $uppercased->toArray());
    }

    public function testFromSortedArray(): void
    {
        $list = SortedLinkedList::fromSortedArray([1, 2, 3, 4, 5]);
        self::assertSame([1, 2, 3, 4, 5], $list->toArray());
        self::assertSame(5, $list->count());
    }

    public function testFromRange(): void
    {
        $list = SortedLinkedList::fromRange(1, 5);
        self::assertSame([1, 2, 3, 4, 5], $list->toArray());
        
        $list = SortedLinkedList::fromRange(0, 10, 2);
        self::assertSame([0, 2, 4, 6, 8, 10], $list->toArray());
        
        $list = SortedLinkedList::fromRange(5, 1, -1);
        self::assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function testFromRangeZeroStep(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SortedLinkedList::fromRange(1, 5, 0);
    }

    public function testStringOptionsWithNewMethods(): void
    {
        $options = new SortedStringOptions(caseInsensitive: true);
        $list = SortedLinkedList::forStrings($options);
        $list->addAll(['apple', 'Banana', 'cherry']);
        
        self::assertSame(0, $list->indexOf('Apple')); // case insensitive
        self::assertSame('apple', $list->get(0));
        
        $filtered = $list->filter(fn($x) => strlen($x) > 5);
        self::assertSame(['Banana', 'cherry'], $filtered->toArray());
    }

    public function testChainedOperations(): void
    {
        $list = SortedLinkedList::fromRange(1, 10)
            ->filter(fn($x) => $x % 2 === 0)
            ->map(fn($x) => $x * 2)
            ->slice(1, 2);
            
        self::assertSame([8, 12], $list->toArray());
    }

    public function testEmptyListOperations(): void
    {
        $empty = SortedLinkedList::forInts();
        
        self::assertNull($empty->indexOf(1));
        self::assertSame([], $empty->slice(0, 5)->toArray());
        self::assertSame([], $empty->filter(fn($x) => true)->toArray());
        self::assertSame([], $empty->map(fn($x) => $x * 2)->toArray());
        
        $other = SortedLinkedList::forInts();
        $other->insert(1);
        $merged = $empty->merge($other);
        self::assertSame([1], $merged->toArray());
    }

    public function testLargeDatasetPerformance(): void
    {
        $list = SortedLinkedList::forInts();
        
        // Add 1000 elements
        for ($i = 1000; $i >= 1; $i--) {
            $list->insert($i);
        }
        
        self::assertSame(1000, $list->count());
        self::assertSame(1, $list->first());
        self::assertSame(1000, $list->last());
        
        // Test optimized contains - should find early elements quickly
        self::assertTrue($list->contains(1));
        self::assertTrue($list->contains(500));
        self::assertFalse($list->contains(1001));
    }
}
