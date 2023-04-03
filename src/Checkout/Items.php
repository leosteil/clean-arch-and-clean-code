<?php

namespace CleanArch\Checkout;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class Items implements Countable, IteratorAggregate
{
    /** @var Item[]  */
    private array $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function add(Item $item)
    {
        $this->items[] = $item;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}