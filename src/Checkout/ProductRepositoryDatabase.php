<?php

namespace CleanArch\Checkout;

use Doctrine\DBAL\DriverManager;

class ProductRepositoryDatabase implements ProductRepository
{
    public function getProduct(int $idProduct)
    {
        $connectionParams = [
            'dbname' => 'app',
            'user' => 'postgres',
            'password' => '123abc',
            'host' => 'localhost',
            'driver' => 'pdo_pgsql',
        ];

        $conn = DriverManager::getConnection($connectionParams);

        $sql = "SELECT * FROM cccat10.product where id_product = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $idProduct);
        $resultSet = $stmt->executeQuery();
        $product = $resultSet->fetchAssociative();
        $conn->close();

        return $product;
    }
}