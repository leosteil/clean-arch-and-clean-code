<?php

namespace CleanArch\Order;


class GetOrder
{
    public function __construct(
        private readonly OrderRepository $orderRepository = new OrderRepositoryDatabase(),
    ) {
    }

    public function execute(string $id): Output
    {
        $orderData = $this->orderRepository->getById($id);

        return new Output($orderData->total, $orderData->freight, $orderData->code);
    }
}