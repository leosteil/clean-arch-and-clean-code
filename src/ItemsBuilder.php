<?php

namespace CleanArch;

use CleanArch\Checkout\Item;
use CleanArch\Checkout\Items;

class ItemsBuilder
{
    private array $items;

    public function withItem(array $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function build(): Items
    {
        if (empty($this->items)) {
            throw new \LogicException('Cannot build items without item');
        }

        $items = new Items();

        foreach ($this->items as $rawItem) {
            $item = new Item($rawItem['idProduct'], $rawItem['quantity']);
            $items->add($item);
        }

        return $items;
    }
}