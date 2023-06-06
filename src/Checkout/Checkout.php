<?php

namespace CleanArch\Checkout;

use CleanArch\CPFValidator;
use CleanArch\CurrencyGateway;
use CleanArch\CurrencyGatewayHttp;
use CleanArch\Order\Order;
use CleanArch\Order\OrderRepository;
use CleanArch\Order\OrderRepositoryDatabase;
use InvalidArgumentException;

require __DIR__ . '/../../cpf_validator.php';

class Checkout
{
    public function __construct(
        private readonly CurrencyGateway   $currencyGateway = new CurrencyGatewayHttp(),
        private readonly ProductRepository $productRepository = new ProductRepositoryDatabase(),
        private readonly CouponRepository  $couponRepository = new CouponRepositoryDatabase(),
        private readonly OrderRepository $orderRepository = new OrderRepositoryDatabase()
    ) {
    }

    public function testing(){
        
    }

    public function execute(Input $input): Output
    {
        $cpfValidator = new CPFValidator();
        $isCpfValid = $cpfValidator->validate($input->cpf);

        if (!$isCpfValid) {
            throw new InvalidArgumentException('cpf inválido');
        }

        $total = 0;
        $freight = 0;

        $itemsId = array();

        $currencies = $this->currencyGateway->getCurrencies();

        foreach ($input->items as $item) {
            if ($item->quantity < 0) {
                throw new InvalidArgumentException('quantidade de itens invalida');
            }

            if (in_array($item->idProduct, $itemsId, true)) {
                throw new InvalidArgumentException('item duplicado');
            }

            $product = $this->productRepository->getProduct($item->idProduct);

            if ($product['width'] < 0 || $product['height'] < 0 || $product['length'] < 0) {
                throw new InvalidArgumentException('item com dimensões negativas');
            }

            if ($product['weight'] < 0) {
                throw new InvalidArgumentException('item com peso negativo');
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
            $item->price = $product['price'];
            $itemsId[] = $item->idProduct;
        }

        if ($input->coupon) {
            $coupon = $this->couponRepository->getCoupon($input->coupon);

            if (date($coupon['expires_at']) > date("Y-m-d H:i:s")) {
                $total = $total - ($total * ($coupon['discount']) / 100);
            }
        }

        if ($input->from && $input->to) {
            $total += $freight;
        }

        $year = date('Y');
        $sequence = str_pad(strval($this->orderRepository->count()), 8, "0", STR_PAD_LEFT);
        $code = "$year$sequence";

        $order = new Order(
            $input->id,
            $total,
            $freight,
            $input->cpf,
            $code,
            $input->items
        );

        $this->orderRepository->save($order);

        return new Output($total, $freight);
    }
}
