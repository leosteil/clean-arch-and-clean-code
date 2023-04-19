<?php

namespace CleanArch;

use GuzzleHttp\Client;

class CurrencyGatewayHttp implements CurrencyGateway
{
    public function getCurrencies(): array
    {
        $client = new Client();
        $response = $client->request('GET', 'http://localhost:8001/currency');
        return json_decode($response->getBody()->getContents(), true);
    }
}