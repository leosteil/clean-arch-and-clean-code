<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

$app->get('/currency', function (Request $request, Response $response, $args) {
    $data = array('usd' => 3);
    $response->getBody()->write(json_encode($data));
    $response = $response->withStatus(200);
    return $response->withHeader('Content-type', 'application/json');
});

$app->run();
