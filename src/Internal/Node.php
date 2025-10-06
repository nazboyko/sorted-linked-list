<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Internal;

/** @internal */
final class Node
{
    public function __construct(
        public int|string $value,
        public ?Node $next = null
    ) {}
}
