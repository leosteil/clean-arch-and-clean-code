<?php

namespace CleanArch\Order;

use CleanArch\Checkout\Output;
use CleanArch\OrderRepository;

class GetOrder
{
    public function __construct(
        private readonly OrderRepository $orderRepository = new OrderRepositoryDatabase(),
    ) {
    }

    public function execute(string $id): Output
    {
        $orderData = $this->orderRepository->getById($id);

        var_dump($orderData->fetchAllKeyValue());

        return new Output($orderData->total, $orderData->freight);
    }
}