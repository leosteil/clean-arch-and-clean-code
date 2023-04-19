<?php

namespace CleanArch;

interface CurrencyGateway
{
    public function getCurrencies(): array;
}