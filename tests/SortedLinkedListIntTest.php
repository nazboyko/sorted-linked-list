<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Tests;

use NazBoyko\SortedLinkedList\Exceptions\EmptyListException;
use NazBoyko\SortedLinkedList\SortedLinkedList;
use PHPUnit\Framework\TestCase;

final class SortedLinkedListIntTest extends TestCase
{
    public function testInsertAndOrderAsc(): void
    {
        $list = SortedLinkedList::forInts(order: 'asc', allowDuplicates: true);
        $list->addAll([5, 2, 7, 3, 3, 1]);
        self::assertSame([1, 2, 3, 3, 5, 7], $list->toArray());
        self::assertSame(1, $list->first());
        self::assertSame(7, $list->last());
        self::assertSame(6, $list->count());
    }

    public function testInsertAndOrderDesc(): void
    {
        $list = SortedLinkedList::forInts(order: 'desc');
        $list->addAll([1, 5, 2, 7, 3]);
        self::assertSame([7, 5, 3, 2, 1], $list->toArray());
    }

    public function testRemoveFirst(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([2, 1, 3, 2]);
        self::assertTrue($list->remove(2));
        self::assertSame([1, 2, 3], $list->toArray());
        self::assertFalse($list->remove(9));
    }

    public function testRemoveAll(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([2, 2, 2, 3, 1, 2]);
        self::assertSame(4, $list->removeAll(2));
        self::assertSame([1, 3], $list->toArray());
    }

    public function testFirstLastEmpty(): void
    {
        $list = SortedLinkedList::forInts();
        $this->expectException(EmptyListException::class);
        $list->first();
    }
}
