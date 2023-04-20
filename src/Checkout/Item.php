<?php

namespace CleanArch\Checkout;

class Item
{
    public function __construct(readonly public int $idProduct, readonly public int $quantity, public ?float $price = null)
    {
    }
}