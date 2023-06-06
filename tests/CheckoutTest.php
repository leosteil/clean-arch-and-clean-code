<?php

namespace Tests;

use CleanArch\Checkout\Checkout;
use CleanArch\Checkout\CouponRepositoryDatabase;
use CleanArch\Checkout\Input;
use CleanArch\Checkout\Item;
use CleanArch\Checkout\Items;
use CleanArch\Checkout\ProductRepositoryDatabase;
use CleanArch\CurrencyGateway;
use CleanArch\CurrencyGatewayHttp;
use CleanArch\Order\GetOrder;
use CleanArch\Order\Order;
use CleanArch\Order\OrderRepository;
use PHPUnit\Framework\TestCase;

final class CheckoutTest extends TestCase
{
    private Checkout $checkout;
    private GetOrder $getOrder;

    public function setUp(): void
    {
        $this->checkout = new Checkout();
        $this->getOrder = new GetOrder();
    }

    public function testNaoDeveCriarUmPedidoComCPFInvalido(): void
    {
        $uuid = uniqid();

        $input = new Input($uuid, 1234, new Items());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cpf invalido');

        $this->checkout->execute($input);
    }

    public function testDeveCriarUmPedidoVazioComCPFValido(): void
    {
        $uuid = uniqid();

        $input = new Input($uuid, '03411287080', new Items());
        $output = $this->checkout->execute($input);

        $this->assertEquals(0, $output->total);
        $this->assertEquals(0, $output->freight);
    }

    public function testDeveCriarUmPedidoCom3ProdutosECalcularOValorTotal(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => 1],
            ['idProduct' => 2, 'quantity' => 1],
            ['idProduct' => 3, 'quantity' => 3],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);
        $this->checkout->execute($input);

        $output = $this->getOrder->execute($uuid);

        $this->assertEquals(6090, $output->total);
    }

    public function testDeveCriarUmPedidoCom3ProdutosECalcularOValorTotalComDesconto(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => 1],
            ['idProduct' => 2, 'quantity' => 1],
            ['idProduct' => 3, 'quantity' => 3],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection, '20off');
        $output = $this->checkout->execute($input);

        $this->assertEquals(4872, $output->total);
    }

    public function testNaoDeveAplicarCupomDeDiscontoExpirado(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => 1],
            ['idProduct' => 2, 'quantity' => 1],
            ['idProduct' => 3, 'quantity' => 3],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection, '10off');
        $output = $this->checkout->execute($input);

        $this->assertEquals(6090, $output->total);
    }

    public function testNaoDeveCriarUmPedidoComQuantidadeDeItensNegativos(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => -1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('quantidade de itens invalida');

        $this->checkout->execute($input);
    }

    public function testNaoDeveCriarUmPedidoComItensDuplicados(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => 1],
            ['idProduct' => 1, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item duplicado');

        $this->checkout->execute($input);
    }

    public function testNaoDeveCriarUmPedidoComItensComDimensoesNegativas(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 4, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item com dimensões negativas');

        $this->checkout->execute($input);
    }

    public function testNaoDeveCriarUmPedidoComItensComPesoNegativo(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 5, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item com peso negativo');

        $this->checkout->execute($input);
    }

    public function testDeveCriarUmPedidoCom1ProdutoCalculandoOFrete(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => 3],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection, null, '123', 456);
        $output = $this->checkout->execute($input);

        $this->assertEquals(90, $output->freight);
        $this->assertEquals(3090, $output->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoCalculandoOFreteComFreteMinimo(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 3, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection, null, '123', 456);
        $output = $this->checkout->execute($input);

        $this->assertEquals(10, $output->freight);
        $this->assertEquals(40, $output->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoEmDolar(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 6, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);
        $output = $this->checkout->execute($input);

        $this->assertEquals(3000, $output->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoEmDolarUsandoUmFake(): void
    {
        $uuid = uniqid();

        $currencyGateway = $this->createMock(CurrencyGateway::class);
        $currencyGateway->expects($this->once())
            ->method('getCurrencies')
            ->willReturn([
                'usd' => 3
            ]);

        $items = [
            ['idProduct' => 6, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);

        $checkout = new Checkout($currencyGateway);

        $output = $checkout->execute($input);
        $this->assertEquals(3000, $output->total);
    }

    public function testDeveCriarUmPedidoEVerificarONumeroDeSerie(): void
    {
        $uuid = uniqid();

        $items = [
            ['idProduct' => 1, 'quantity' => 1],
            ['idProduct' => 2, 'quantity' => 1],
            ['idProduct' => 3, 'quantity' => 3],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input($uuid, '03411287080', $itemsCollection);

        $orderRepositoryStub = $this->createMock(OrderRepository::class);
        $orderRepositoryStub->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $orderRepositoryStub->expects($this->once())
            ->method('getById')
            ->willReturn(new Order(
                'abc',
                123,
                15,
                '03411287080',
                '12345',
                new Items()
            ));

        $checkout = new Checkout(
            new CurrencyGatewayHttp(),
            new ProductRepositoryDatabase(),
            new CouponRepositoryDatabase(),
            $orderRepositoryStub
        );

        $checkout->execute($input);

        $getOrder = new GetOrder(
            $orderRepositoryStub
        );

        $output = $getOrder->execute($uuid);

        $this->assertEquals('12345', $output->code);
    }
}