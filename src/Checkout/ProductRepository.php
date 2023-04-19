<?php

namespace CleanArch\Checkout;

interface ProductRepository
{
    public function getProduct(int $idProduct);
}