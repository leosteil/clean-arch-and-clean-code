<?php

namespace CleanArch\Checkout;

interface CouponRepository
{
    public function getCoupon(string $code);
}