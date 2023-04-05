<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

final class IndexTest extends TestCase
{
    public function testNaoDeveCriarUmPedidoComCPFInvalido()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '1234',
                'items' => [],
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals('cpf invalido', $responseBody->message);
    }

    public function testDeveCriarUmPedidoVazioComCPFValido()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [],
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals(0, $responseBody->total);
    }

    public function testDeveCriarUmPedidoCom3ProdutosECalcularOValorTotal()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 1, 'quantity' => 1],
                    ['idProduct' => 2, 'quantity' => 1],
                    ['idProduct' => 3, 'quantity' => 3],
                ]
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals(6090, $responseBody->total);
    }

    public function testDeveCriarUmPedidoCom3ProdutosECalcularOValorTotalComDesconto()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 1, 'quantity' => 1],
                    ['idProduct' => 2, 'quantity' => 1],
                    ['idProduct' => 3, 'quantity' => 3],
                ],
                'coupon' => '20off'
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals(4872, $responseBody->total);
    }

    public function testNaoDeveAplicarCupomDeDiscontoExpirado()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 1, 'quantity' => 1],
                    ['idProduct' => 2, 'quantity' => 1],
                    ['idProduct' => 3, 'quantity' => 3],
                ],
                'coupon' => '10off'
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals(6090, $responseBody->total);
    }

    public function testNaoDeveCriarUmPedidoComQuantidadeDeItensNegativos()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 1, 'quantity' => -1],
                ],
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals('quantidade de itens invalida', $responseBody->message);
    }

    public function testNaoDeveCriarUmPedidoComItensDuplicados()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 1, 'quantity' => 1],
                    ['idProduct' => 1, 'quantity' => 1],
                ],
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals('item duplicado', $responseBody->message);
    }

    public function testNaoDeveCriarUmPedidoComItensComDimensoesNegativas()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 4, 'quantity' => 1],
                ],
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals('item com dimensões negativas', $responseBody->message);
    }

    public function testNaoDeveCriarUmPedidoComItensComPesoNegativo()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 5, 'quantity' => 1],
                ],
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals('item com peso negativo', $responseBody->message);
    }

    public function testDeveCriarUmPedidoCom1ProdutoCalculandoOFrete()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 1, 'quantity' => 3],
                ],
                'to' => '123',
                'from' => '456'
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals(90, $responseBody->freight);
        $this->assertEquals(3090, $responseBody->total);
    }

    public function testDeveCriarUmPedidoCom1ProdutoCalculandoOFreteComFreteMinimo()
    {
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/checkout', [
            'json' => [
                'cpf' => '03411287080',
                'items' => [
                    ['idProduct' => 3, 'quantity' => 1],
                ],
                'to' => '123',
                'from' => '456'
            ],
            'http_errors' => false
        ]);

        $responseBody = json_decode($response->getBody()->getContents());

        $this->assertEquals(10, $responseBody->freight);
        $this->assertEquals(40, $responseBody->total);
    }
}