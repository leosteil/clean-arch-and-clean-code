<?php

namespace CleanArch\Order;

use CleanArch\Checkout\Items;

readonly class Order
{
    public function __construct(
        public string $idOrder,
        public string $total,
        public string $freight,
        public string $cpf,
        public string $code,
        public Items $items
    ) {
    }
}