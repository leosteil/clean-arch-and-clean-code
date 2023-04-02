<?php

use Doctrine\DBAL\DriverManager;
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

    $connectionParams = [
        'dbname' => 'app',
        'user' => 'postgres',
        'password' => '123abc',
        'host' => 'localhost',
        'driver' => 'pdo_pgsql',
    ];

    $conn = DriverManager::getConnection($connectionParams);

    $body = json_decode($request->getBody()->getContents(), true);

    $isCpfValid = validaCPF($body['cpf']);

    if (!$isCpfValid) {
        $output = new Output(0, 'cpf invalido');

        $response->getBody()->write($output->__toString());
        $response = $response->withStatus(422);
        return $response->withHeader('Content-type', 'application/json');
    }

    $total = 0;
    $freight = 0;

    if (!empty($body['items'])) {
        $itemsId = array();

        foreach ($body['items'] as $item) {

            if ($item['quantity'] < 0) {
                $output = new Output(0, 'quantidade de itens invalida');

                $response->getBody()->write($output->__toString());
                $response = $response->withStatus(422);
                return $response->withHeader('Content-type', 'application/json');
            }

            if (in_array($item['idProduct'], $itemsId, true)) {
                $output = new Output(0, 'item duplicado');

                $response->getBody()->write($output->__toString());
                $response = $response->withStatus(422);
                return $response->withHeader('Content-type', 'application/json');
            }


            $sql = "SELECT * FROM cccat10.product where id_product = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(1, $item['idProduct']);
            $resultSet = $stmt->executeQuery();
            $product = $resultSet->fetchAssociative();

            if ($product['width'] < 0 || $product['height'] < 0 || $product['length'] < 0 ) {
                $output = new Output(0, 'item com dimensões negativas');

                $response->getBody()->write($output->__toString());
                $response = $response->withStatus(422);
                return $response->withHeader('Content-type', 'application/json');
            }

            if ($product['weight'] < 0) {
                $output = new Output(0, 'item com peso negativo');
                $response->getBody()->write($output->__toString());
                $response = $response->withStatus(422);
                return $response->withHeader('Content-type', 'application/json');
            }

            $total += $product['price'] * $item['quantity'];

            $volume = $product['width']/100 * $product['height']/100 * $product['length']/100;
            $density = floatval($product['weight'])/$volume;
            $itemFreight = 1000 * $volume * ($density/100);

            $freight +=  max($itemFreight, 10) * $item['quantity'];
            $itemsId[] = $item['idProduct'];
        }
    }

    if (!empty($body['coupon_code'])) {
        $sql = "SELECT * FROM cccat10.coupon where coupon_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $body['coupon_code']);
        $productSet = $stmt->executeQuery();
        $product = $productSet->fetchAssociative();

        if (date($product['expires_at']) > date("Y-m-d H:i:s")) {
            $total = $total - ($total * ($product['discount']) / 100);
        }
    }

    if (!empty($body['from']) && !empty($body['to'])) {
        $total += $freight;
    }
    $output = new Output($total, 'total', $freight);

    $response->getBody()->write($output->__toString());
    $response = $response->withStatus(200);
    return $response->withHeader('Content-type', 'application/json');
});

readonly class Output
{
    public function __construct(public float $total, public string $message, public float $freight = 0)
    {
    }

    public function __toString(): string
    {
        return json_encode($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            'message' => $this->message,
            'total' => $this->total,
            'freight' => $this->freight
        ];
    }
}

$app->run();
