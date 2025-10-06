<?php

declare(strict_types=1);

namespace NazBoyko\SortedLinkedList\Options;

final class SortedStringOptions
{
    public function __construct(
        public bool $caseInsensitive = false,
        public bool $naturalOrder = false,
    ) {
    }
}
