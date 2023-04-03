<?php

use CleanArch\Checkout\Checkout;
use CleanArch\Checkout\InputBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/cpf_validator.php';


// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

$app->post('/checkout', function (Request $request, Response $response, $args) {

    $body = json_decode($request->getBody()->getContents());

    $checkoutInputBuilder = new InputBuilder();
    $checkoutInput = $checkoutInputBuilder->withCpf($body->cpf)
        ->withItems($body->items)
        ->withCoupon($body->coupon)
        ->withFrom($body->from)
        ->withTo($body->to)
        ->build();

    try {
        $checkout = new Checkout();
        $output = $checkout->execute($checkoutInput);

        $response->getBody()->write($output->__toString());
        $response = $response->withStatus(200);
        return $response->withHeader('Content-type', 'application/json');
    } catch (\Exception $exception) {
        $data = array('message' => $exception->getMessage());
        $response->getBody()->write(json_encode($data));
        $response = $response->withStatus(422);
        return $response->withHeader('Content-type', 'application/json');
    }
});

$app->run();
