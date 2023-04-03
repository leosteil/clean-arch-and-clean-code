<?php

namespace CleanArch\Checkout;

use Doctrine\DBAL\DriverManager;

class Checkout
{
    public function execute(Input $input): Output
    {
        $connectionParams = [
            'dbname' => 'app',
            'user' => 'postgres',
            'password' => '123abc',
            'host' => 'localhost',
            'driver' => 'pdo_pgsql',
        ];

        $conn = DriverManager::getConnection($connectionParams);

        $isCpfValid = validaCPF($input->cpf);

        if (!$isCpfValid) {
            throw new \InvalidArgumentException('cpf invalido');
        }

        $total = 0;
        $freight = 0;

        $itemsId = array();

        foreach ($input->items as $item) {
            if ($item->quantity < 0) {
                throw new \InvalidArgumentException('quantidade de itens invalida');
            }

            if (in_array($item->idProduct, $itemsId, true)) {
                throw new \InvalidArgumentException('item duplicado');
            }

            $sql = "SELECT * FROM cccat10.product where id_product = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $item->idProduct);
            $resultSet = $stmt->executeQuery();
            $product = $resultSet->fetchAssociative();

            if ($product['width'] < 0 || $product['height'] < 0 || $product['length'] < 0) {
                throw new \InvalidArgumentException('item com dimensões negativas');
            }

            if ($product['weight'] < 0) {
                throw new \InvalidArgumentException('item com peso negativo');
            }

            $total += $product['price'] * $item->quantity;

            $volume = $product['width'] / 100 * $product['height'] / 100 * $product['length'] / 100;
            $density = floatval($product['weight']) / $volume;
            $itemFreight = 1000 * $volume * ($density / 100);

            $freight += max($itemFreight, 10) * $item->quantity;
            $itemsId[] = $item->idProduct;
        }

        if ($input->coupon) {
            $sql = "SELECT * FROM cccat10.coupon where coupon_code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $input->coupon);
            $resultSer = $stmt->executeQuery();
            $coupon = $resultSer->fetchAssociative();

            if (date($coupon['expires_at']) > date("Y-m-d H:i:s")) {
                $total = $total - ($total * ($coupon['discount']) / 100);
            }
        }

        if ($input->from && $input->to) {
            $total += $freight;
        }

        return new Output($total, $freight);
    }
}