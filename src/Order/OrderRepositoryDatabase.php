<?php

namespace CleanArch\Order;

use CleanArch\Checkout\Items;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class OrderRepositoryDatabase implements OrderRepository
{
    private Connection $connection;

    public function __construct()
    {
        $this->connection = $this->createConnection();
    }

    public function save(Order $order): void
    {
        $connection = $this->createConnection();
        $this->insertOrder($order);
        $this->insertItems($order->items, $order->idOrder);

        $connection->close();
    }

    public function getById(string $id): Order
    {
        $connection = $this->createConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $order = $queryBuilder
            ->select('*')
            ->from('cccat10.order')
            ->where('id_order = ?')
            ->setParameter(0, $id)
            ->executeQuery()
            ->fetchAssociative();

        return new Order(
            $order['id_order'],
            $order['total'],
            $order['freight'],
            $order['cpf'],
            $order['code'],
            new Items()
        );
    }

    private function createConnection(): Connection
    {
        $connectionParams = [
            'dbname' => 'app',
            'user' => 'postgres',
            'password' => '123abc',
            'host' => 'localhost',
            'driver' => 'pdo_pgsql',
        ];

        return DriverManager::getConnection($connectionParams);
    }

    private function insertOrder(Order $order): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->insert('cccat10.order')
            ->values(
                [
                    'id_order' => '?',
                    'cpf' => '?',
                    'code' => '?',
                    'total' => '?',
                    'freight' => '?',
                ]
            )
            ->setParameter(0, $order->idOrder)
            ->setParameter(1, $order->cpf)
            ->setParameter(2, $order->code)
            ->setParameter(3, $order->total)
            ->setParameter(4, $order->freight);

        $queryBuilder->executeQuery();
    }

    private function insertItems(Items $items, string $idOrder)
    {
        foreach ($items as $item) {
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder->insert('cccat10.item')
                ->values(
                    [
                        'id_order' => '?',
                        'id_product' => '?',
                        'price' => '?',
                        'quantity' => '?',
                    ]
                )
                ->setParameter(0, $idOrder)
                ->setParameter(1, $item->idProduct)
                ->setParameter(2, $item->price)
                ->setParameter(3, $item->quantity);

            $queryBuilder->executeQuery();
        }
    }

    public function count(): int
    {
        $connection = $this->createConnection();
        $queryBuilder = $connection->createQueryBuilder();
        return $queryBuilder
            ->select('*')
            ->from('cccat10.order')
            ->executeQuery()
            ->rowCount();
    }
}