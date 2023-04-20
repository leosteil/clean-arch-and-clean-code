<?php

namespace CleanArch;

use CleanArch\Checkout\Items;

readonly class Order
{
    public function __construct(
        public string $idOrder,
        public string $total,
        public string $freight,
        public string $cpf,
        public Items $items
    ) {
    }
}