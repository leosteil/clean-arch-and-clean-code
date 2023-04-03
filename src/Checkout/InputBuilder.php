<?php

namespace CleanArch\Checkout;

class InputBuilder
{
    private string $cpf;
    private Items $items;
    private ?string $coupon = null;
    private ?string $from = null;
    private ?string $to = null;

    public function withCpf(string $cpf): self
    {
        $this->cpf = $cpf;
        return $this;
    }

    public function withItems(array $items): self
    {
        $itemsCollection = new Items();

        foreach ($items as $item) {
            $itemForCollection = new Item($item->idProduct, $item->quantity);
            $itemsCollection->add($itemForCollection);
        }

        $this->items = $itemsCollection;
        return $this;
    }

    public function withCoupon(?string $coupon): self
    {
        $this->coupon = $coupon;
        return $this;
    }

    public function withFrom(?string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function withTo(?string $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function build(): Input
    {
        return new Input(
            $this->cpf,
            $this->items,
            $this->coupon,
            $this->from,
            $this->to
        );
    }
}