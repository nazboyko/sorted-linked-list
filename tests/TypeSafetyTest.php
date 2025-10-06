<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Tests;

use NazBoyko\SortedLinkedList\Exceptions\DuplicateNotAllowedException;
use NazBoyko\SortedLinkedList\Exceptions\ValueTypeException;
use NazBoyko\SortedLinkedList\SortedLinkedList;
use PHPUnit\Framework\TestCase;

final class TypeSafetyTest extends TestCase
{
    public function testTypeLocking(): void
    {
        $list = new SortedLinkedList();
        $list->insert(1);
        $this->expectException(ValueTypeException::class);
        $list->insert("a");
    }

    public function testNoDuplicates(): void
    {
        $list = SortedLinkedList::forInts(order: 'asc', allowDuplicates: false);
        $list->insert(2);
        $this->expectException(DuplicateNotAllowedException::class);
        $list->insert(2);
    }

    public function testContainsRespectsType(): void
    {
        $list = SortedLinkedList::forInts();
        $list->addAll([1, 2, 3]);

        // string "2" should not match int 2 in a typed list
        self::assertFalse($list->contains("2"));
        self::assertTrue($list->contains(2));

        // remove also respects type
        self::assertFalse($list->remove("2"));
        self::assertTrue($list->remove(2));
    }
}
