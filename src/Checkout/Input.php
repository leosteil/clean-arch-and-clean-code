<?php

namespace CleanArch\Checkout;

readonly class Input
{
    public function __construct(
        public string $id,
        public string $cpf,
        public Items $items,
        public ?string $coupon = null,
        public ?string $from = null,
        public ?string $to = null
    ) {
    }
}