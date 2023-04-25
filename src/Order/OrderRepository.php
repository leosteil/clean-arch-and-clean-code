<?php

namespace CleanArch\Order;

interface OrderRepository
{
    public function save(Order $order): void;
    public function getById(string $id);
    public function count(): int;
}