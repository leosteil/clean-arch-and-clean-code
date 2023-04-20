<?php

namespace CleanArch;

interface OrderRepository
{
    public function save(Order $order): void;
    public function getById(string $id);
}