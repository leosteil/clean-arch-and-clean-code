<?php

namespace CleanArch\Checkout;

use CleanArch\CurrencyGatewayHttp;

require __DIR__ . '/../../cpf_validator.php';

class Checkout
{
    public function execute(Input $input): Output
    {
        $isCpfValid = validaCPF($input->cpf);

        if (!$isCpfValid) {
            throw new \InvalidArgumentException('cpf invalido');
        }

        $total = 0;
        $freight = 0;

        $itemsId = array();

        $currencyGateway = new CurrencyGatewayHttp();
        $currencies = $currencyGateway->getCurrencies();

        foreach ($input->items as $item) {
            if ($item->quantity < 0) {
                throw new \InvalidArgumentException('quantidade de itens invalida');
            }

            if (in_array($item->idProduct, $itemsId, true)) {
                throw new \InvalidArgumentException('item duplicado');
            }

            $productRepository = new ProductRepositoryDatabase();
            $product = $productRepository->getProduct($item->idProduct);

            if ($product['width'] < 0 || $product['height'] < 0 || $product['length'] < 0) {
                throw new \InvalidArgumentException('item com dimensões negativas');
            }

            if ($product['weight'] < 0) {
                throw new \InvalidArgumentException('item com peso negativo');
            }

            if ($product['currency'] === 'USD') {
                $total += $product['price'] * $item->quantity * $currencies['usd'];
            } else {
                $total += $product['price'] * $item->quantity;
            }

            $volume = $product['width'] / 100 * $product['height'] / 100 * $product['length'] / 100;
            $density = floatval($product['weight']) / $volume;
            $itemFreight = 1000 * $volume * ($density / 100);

            $freight += max($itemFreight, 10) * $item->quantity;
            $itemsId[] = $item->idProduct;
        }

        if ($input->coupon) {
            $couponRepository = new CouponRepositoryDatabase();
            $coupon = $couponRepository->getCoupon($input->coupon);

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