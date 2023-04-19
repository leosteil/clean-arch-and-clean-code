<?php

namespace CleanArch\Checkout;

use Doctrine\DBAL\DriverManager;

class CouponRepositoryDatabase implements CouponRepository
{
    public function getCoupon(string $code)
    {
        $connectionParams = [
            'dbname' => 'app',
            'user' => 'postgres',
            'password' => '123abc',
            'host' => 'localhost',
            'driver' => 'pdo_pgsql',
        ];

        $conn = DriverManager::getConnection($connectionParams);

        $sql = "SELECT * FROM cccat10.coupon where coupon_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $code);
        $resultSet = $stmt->executeQuery();
        $coupon = $resultSet->fetchAssociative();
        $conn->close();

        return $coupon;
    }
}