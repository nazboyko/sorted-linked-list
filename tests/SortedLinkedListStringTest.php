<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Tests;

use NazBoyko\SortedLinkedList\Options\SortedStringOptions;
use NazBoyko\SortedLinkedList\SortedLinkedList;
use PHPUnit\Framework\TestCase;

final class SortedLinkedListStringTest extends TestCase
{
    public function testBinaryLexicographic(): void
    {
        $list = SortedLinkedList::forStrings(new SortedStringOptions());
        $list->addAll(['10', '2', 'a', 'A']);
        // strcmp puts '10' < '2'; 'A' < 'a'
        self::assertSame(['10', '2', 'A', 'a'], $list->toArray());
    }

    public function testCaseInsensitiveNatural(): void
    {
        $list = SortedLinkedList::forStrings(new SortedStringOptions(caseInsensitive: true, naturalOrder: true));
        $list->addAll(['file9', 'File10', 'file2']);
        self::assertSame(['file2', 'file9', 'File10'], $list->toArray());
    }

    public function testDuplicatesPolicies(): void
    {
        // DUP_TAIL (default): new equals go AFTER the equals block
        $tail = new SortedLinkedList(duplicatesPolicy: 'tail');
        $tail->addAll(['a', 'a', 'b']); // establishes string type
        $tail->insert('a');
        self::assertSame(['a', 'a', 'a', 'b'], $tail->toArray());

        // DUP_HEAD: new equals go BEFORE the equals block
        $head = new SortedLinkedList(duplicatesPolicy: 'head');
        $head->addAll(['b', 'a', 'a']);
        $head->insert('a');
        self::assertSame(['a', 'a', 'a', 'b'], $head->toArray());
    }
}
