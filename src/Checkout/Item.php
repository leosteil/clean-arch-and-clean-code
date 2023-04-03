<?php

namespace CleanArch\Checkout;

readonly class Item
{
    public function __construct(public int $idProduct, public int $quantity)
    {
    }
}