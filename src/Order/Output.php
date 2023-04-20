<?php

namespace CleanArch\Order;

class Output
{
    public function __construct(public float $total, public float $freight)
    {
    }

    public function __toString(): string
    {
        return json_encode($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            'total' => $this->total,
            'freight' => $this->freight
        ];
    }
}