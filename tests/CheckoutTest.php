<?php

namespace Tests;

use CleanArch\Checkout\Checkout;
use CleanArch\Checkout\Input;
use CleanArch\Checkout\Item;
use CleanArch\Checkout\Items;
use CleanArch\CurrencyGateway;
use PHPUnit\Framework\TestCase;

final class CheckoutTest extends TestCase
{
    private Checkout $checkout;

    public function setUp(): void
    {
        $this->checkout = new Checkout();
    }

    public function testNaoDeveCriarUmPedidoComCPFInvalido()
    {
        $input = new Input(1234, new Items());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cpf invalido');

        $this->checkout->execute($input);
    }

    public function testDeveCriarUmPedidoVazioComCPFValido()
    {
        $input = new Input('03411287080', new Items());
        $output = $this->checkout->execute($input);

        $this->assertEquals(0, $output->total);
        $this->assertEquals(0, $output->freight);
    }

    public function testDeveCriarUmPedidoCom3ProdutosECalcularOValorTotal()
    {
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

        $input = new Input('03411287080', $itemsCollection);
        $output = $this->checkout->execute($input);

        $this->assertEquals(6090, $output->total);
    }

    public function testDeveCriarUmPedidoCom3ProdutosECalcularOValorTotalComDesconto()
    {
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

        $input = new Input('03411287080', $itemsCollection, '20off');
        $output = $this->checkout->execute($input);

        $this->assertEquals(4872, $output->total);
    }

    public function testNaoDeveAplicarCupomDeDiscontoExpirado()
    {
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

        $input = new Input('03411287080', $itemsCollection, '10off');
        $output = $this->checkout->execute($input);

        $this->assertEquals(6090, $output->total);
    }

    public function testNaoDeveCriarUmPedidoComQuantidadeDeItensNegativos()
    {
        $items = [
            ['idProduct' => 1, 'quantity' => -1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('quantidade de itens invalida');

        $this->checkout->execute($input);
    }

    public function testNaoDeveCriarUmPedidoComItensDuplicados()
    {
        $items = [
            ['idProduct' => 1, 'quantity' => 1],
            ['idProduct' => 1, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item duplicado');

        $this->checkout->execute($input);
    }

    public function testNaoDeveCriarUmPedidoComItensComDimensoesNegativas()
    {
        $items = [
            ['idProduct' => 4, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item com dimensões negativas');

        $this->checkout->execute($input);
    }

    public function testNaoDeveCriarUmPedidoComItensComPesoNegativo()
    {
        $items = [
            ['idProduct' => 5, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('item com peso negativo');

        $this->checkout->execute($input);
    }

    public function testDeveCriarUmPedidoCom1ProdutoCalculandoOFrete()
    {
        $items = [
            ['idProduct' => 1, 'quantity' => 3],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection, null, '123', 456);
        $output = $this->checkout->execute($input);

        $this->assertEquals(90, $output->freight);
        $this->assertEquals(3090, $output->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoCalculandoOFreteComFreteMinimo()
    {
        $items = [
            ['idProduct' => 3, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection, null, '123', 456);
        $output = $this->checkout->execute($input);

        $this->assertEquals(10, $output->freight);
        $this->assertEquals(40, $output->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoEmDolar()
    {
        $items = [
            ['idProduct' => 6, 'quantity' => 1],
        ];

        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item['idProduct'], $item['quantity']);
            $itemsCollection->add($itemForCollection);
        }

        $input = new Input('03411287080', $itemsCollection);
        $output = $this->checkout->execute($input);

        $this->assertEquals(3000, $output->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoEmDolarUsandoUmFake(): void
    {
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

        $input = new Input('03411287080', $itemsCollection);

        $checkout = new Checkout($currencyGateway);

        $output = $checkout->execute($input);
        $this->assertEquals(3000, $output->total);
    }
}