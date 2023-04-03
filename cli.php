<?php

use CleanArch\Checkout\Checkout;
use CleanArch\Checkout\Input;
use CleanArch\Checkout\Item;
use CleanArch\Checkout\Items;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/cpf_validator.php';

$items = new Items();

while (true) {

    $stdin = fopen('php://stdin', 'r');
    $response = fgets($stdin);
    $command = preg_replace('/\n/', "", $response);

    if (str_starts_with($command, "set-cpf")) {
        $cpf = str_replace("set-cpf ", "", $command);
    }

    if (str_starts_with($command, "add-item")) {
        [$idItem, $quantity] = explode(" ", str_replace("add-item ", "", $command));

        $item = new Item($idItem, $quantity);
        $items->add($item);
    }

    if (str_starts_with($command, "checkout")) {
        $input = new Input($cpf, $items);

        try {
            $checkout = new Checkout();
            $output = $checkout->execute($input);

            echo $output->total . PHP_EOL;
            echo $output->freight . PHP_EOL;
        } catch (\Exception $exception) {
           echo $exception->getMessage();
        }
    }
}
