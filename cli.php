<?php

use Doctrine\DBAL\DriverManager;
use Ds\Map;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/cpf_validator.php';

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

class Items implements \Countable
{
    private Map $items;

    public function __construct()
    {
        $this->items = new Map();
    }

    public function add(int $idItem, Item $item)
    {
        $this->items->put($idItem, $item);
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function toArray(): array
    {
        return $this->items->toArray();
    }
}

$items = new Items();

while (true) {

    $stdin = fopen('php://stdin', 'r');
    $response = fgets($stdin);
    $command = preg_replace('/\n/', "", $response);

    if (str_starts_with($command, "set-cpf")) {
        $cpf =  str_replace("set-cpf ", "", $command);
    }

    if (str_starts_with($command, "add-item")) {
        [$idItem, $quantity] = explode(" ", str_replace("add-item ", "", $command)) ;

        $item = new Item($idItem, $quantity);
        $items->add($idItem, $item);
    }

    if (str_starts_with($command, "checkout")) {
        $input = new Input($cpf, $items);

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
            $output = new Output(0, 'cpf invalido');

           echo $output->message;
        }

        $total = 0;
        $freight = 0;

        if (count($input->items)) {
            $itemsId = array();

            foreach ($input->items->toArray() as $item) {
                if ($item->quantity < 0) {
                    $output = new Output(0, 'quantidade de itens invalida');
                    echo $output->message;
                }

                if (in_array($item->idProduct, $itemsId, true)) {
                    $output = new Output(0, 'item duplicado');
                    echo $output->message;
                }


                $sql = "SELECT * FROM cccat10.product where id_product = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue(1, $item->idProduct);
                $resultSet = $stmt->executeQuery();
                $product = $resultSet->fetchAssociative();

                if ($product['width'] < 0 || $product['height'] < 0 || $product['length'] < 0 ) {
                    $output = new Output(0, 'item com dimensões negativas');

                    echo $output->message;
                }

                if ($product['weight'] < 0) {
                    $output = new Output(0, 'item com peso negativo');
                    echo $output->message;
                }

                $total += $product['price'] * $item->quantity;

                $volume = $product['width']/100 * $product['height']/100 * $product['length']/100;
                $density = floatval($product['weight'])/$volume;
                $itemFreight = 1000 * $volume * ($density/100);

                $freight +=  max($itemFreight, 10) * $item->quantity;
                $itemsId[] = $item->idProduct;
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
        echo $output->message . PHP_EOL;
        echo $output->total . PHP_EOL;;
        echo $output->freight . PHP_EOL;;
    }
}


readonly class Input
{
    public function __construct(
        public string $cpf,
        public Items $items,
        public ?string $coupon = null,
        public ?string $from = null,
        public ?string $to = null
    ) {
    }
}

readonly class Item
{
    public function __construct(public int $idProduct, public int $quantity)
    {
    }
}
